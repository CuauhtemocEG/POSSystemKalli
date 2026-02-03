<?php
// Headers anti-caché
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json; charset=utf-8');

require_once '../../conexion.php';
$pdo = conexion();

$orden_id = intval($_GET['orden_id'] ?? 0);
$es_personal = isset($_GET['es_personal']) ? (bool)$_GET['es_personal'] : false;

if (!$orden_id) {
    echo json_encode([
        'items'=>[], 
        'subtotal'=>0, 
        'descuento'=>0, 
        'impuestos'=>0, 
        'total'=>0,
        'mesero_nombre' => null
    ]);
    exit;
}

// Obtener información de la orden y el mesero, incluyendo configuración de promociones de la mesa
$stmtOrden = $pdo->prepare("
    SELECT o.*, u.nombre_completo as mesero_nombre,
           m.aplicar_promociones, m.es_para_llevar,
           COALESCE(o.aplicar_descuento_porcentaje, 0) as aplicar_descuento_porcentaje,
           COALESCE(o.descuento_porcentaje_valor, 0) as descuento_porcentaje_valor
    FROM ordenes o
    LEFT JOIN usuarios u ON o.usuario_id = u.id
    LEFT JOIN mesas m ON o.mesa_id = m.id
    WHERE o.id = ?
");
$stmtOrden->execute([$orden_id]);
$ordenInfo = $stmtOrden->fetch(PDO::FETCH_ASSOC);

// Verificar si la orden existe
if (!$ordenInfo) {
    echo json_encode([
        'error' => 'orden_no_encontrada',
        'orden_cerrada' => true,
        'items' => [],
        'subtotal' => 0,
        'total' => 0
    ]);
    exit;
}

// Verificar si la orden está cerrada
if ($ordenInfo['estado'] !== 'abierta') {
    echo json_encode([
        'orden_cerrada' => true,
        'estado' => $ordenInfo['estado'],
        'items' => [],
        'subtotal' => 0,
        'total' => 0
    ]);
    exit;
}

$mesero_nombre = 'Sin asignar';
if ($ordenInfo && !empty($ordenInfo['mesero_nombre'])) {
    $mesero_nombre = trim($ordenInfo['mesero_nombre']);
}

$stmt = $pdo->prepare("
    SELECT 
        op.id,
        op.producto_id, 
        p.nombre, 
        op.cantidad,
        COALESCE(op.preparado, 0) as preparado,
        COALESCE(op.cancelado, 0) as cancelado,
        COALESCE(op.pendiente_cancelacion, 0) as pendiente_cancelacion,
        COALESCE(op.confirmado, 1) as confirmado,
        COALESCE(op.item_index, 1) as item_index,
        COALESCE(op.nota_adicional, '') as nota_adicional,
        p.precio,
        p.categoria,
        (op.cantidad * p.precio) as subtotal_item
    FROM orden_productos op
    JOIN productos p ON op.producto_id = p.id
    WHERE op.orden_id = ? AND op.estado != 'eliminado'
    ORDER BY op.confirmado ASC, op.id DESC
");
$stmt->execute([$orden_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
$subtotal = 0;
$total_cancelado = 0;

foreach ($productos as $producto) {
    $cantidad = intval($producto['cantidad']);
    $preparado = intval($producto['preparado']);
    $cancelado = intval($producto['cancelado']);
    $pendiente_cancelacion = intval($producto['pendiente_cancelacion']);
    $precio = floatval($producto['precio']);
    
    // Calcular totales
    $cantidad_activa = $cantidad - $cancelado - $pendiente_cancelacion;
    $subtotal_producto_activo = $cantidad_activa * $precio;
    $cancelado_monto = $cancelado * $precio;
    
    if ($cancelado > 0) {
        $total_cancelado += $cancelado_monto;
    }
    
    // Solo agregar al subtotal los productos activos (no cancelados ni pendientes de cancelación)
    if ($cantidad_activa > 0) {
        $subtotal += $subtotal_producto_activo;
    }
    
    // Obtener variedades de este producto específico usando item_index
    $variedades = [];
    $item_index = $producto['item_index'] ?? 1; // Usar item_index del producto
    
    $stmtVariedades = $pdo->prepare("
        SELECT grupo_nombre, opcion_nombre, precio_adicional
        FROM orden_producto_variedades
        WHERE orden_id = ? AND producto_id = ? AND item_index = ?
        ORDER BY id
    ");
    $stmtVariedades->execute([$orden_id, $producto['producto_id'], $item_index]);
    $variedades = $stmtVariedades->fetchAll(PDO::FETCH_ASSOC);
    
    $items[] = [
        'id' => $producto['id'],
        'producto_id' => $producto['producto_id'],
        'nombre' => $producto['nombre'],
        'cantidad' => $cantidad,
        'preparado' => $preparado,
        'cancelado' => $cancelado,
        'pendiente_cancelacion' => $pendiente_cancelacion,
        'confirmado' => intval($producto['confirmado'] ?? 1),
        'precio' => $precio,
        'subtotal' => $subtotal_producto_activo, // Solo el subtotal de productos activos
        'item_index' => $item_index, // Incluir item_index
        'variedades' => $variedades, // Incluir variedades específicas de este item
        'nota_adicional' => $producto['nota_adicional'] ?? '',
        'categoria' => $producto['categoria'] ?? ''
    ];
}

$descuento = 0;
$impuestos = 0;

// 🎁 Calcular promociones aplicables
$promociones = [];
$total_descuentos_promociones = 0;

// Verificar si esta mesa tiene promociones activadas
$aplicar_promociones = isset($ordenInfo['aplicar_promociones']) ? (bool)$ordenInfo['aplicar_promociones'] : true;
$es_para_llevar = isset($ordenInfo['es_para_llevar']) ? (bool)$ordenInfo['es_para_llevar'] : false;

// No aplicar promociones si:
// 1. La mesa tiene promociones desactivadas
// 2. Es una orden para llevar
if (!$aplicar_promociones || $es_para_llevar) {
    // Log informativo
    if ($es_para_llevar) {
        error_log("Orden {$orden_id}: Promociones omitidas (orden para llevar)");
    } else {
        error_log("Orden {$orden_id}: Promociones desactivadas para esta mesa");
    }
} else {
    // Aplicar promociones normalmente
    try {
    // Preparar productos para calcular promociones
    $productosParaPromociones = [];
    foreach ($items as $item) {
        $cantidad_activa = $item['cantidad'] - $item['cancelado'] - $item['pendiente_cancelacion'];
        if ($cantidad_activa > 0 && $item['confirmado'] == 1) {
            $productosParaPromociones[] = [
                'orden_producto_id' => $item['id'],
                'producto_id' => $item['producto_id'],
                'cantidad' => $cantidad_activa,
                'preparado' => $item['preparado'],
                'nombre' => $item['nombre'],
                'precio' => $item['precio'],
                'categoria' => $item['categoria']
            ];
        }
    }
    
    // Obtener promociones activas
    $stmtPromo = $pdo->query("
        SELECT p.*
        FROM promociones p
        WHERE p.activa = 1
        AND (p.fecha_inicio IS NULL OR p.fecha_inicio <= NOW())
        AND (p.fecha_fin IS NULL OR p.fecha_fin >= NOW())
        ORDER BY p.prioridad DESC, p.id DESC
    ");
    $promociones_activas = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);
    
    $productos_usados_ids = [];
    
    foreach ($promociones_activas as $promo) {
        // Verificar si es promoción de personal y requiere activación
        if ($promo['tipo'] === 'descuento_personal' && !$es_personal) {
            continue; // Saltar si no está activado el flag de personal
        }
        
        // Filtrar productos elegibles
        $productos_elegibles = [];
        
        if ($promo['aplica_a'] === 'todos') {
            foreach ($productosParaPromociones as $prod) {
                if (!in_array($prod['orden_producto_id'], $productos_usados_ids)) {
                    for ($i = 0; $i < $prod['cantidad']; $i++) {
                        $productos_elegibles[] = $prod;
                    }
                }
            }
        } elseif ($promo['aplica_a'] === 'productos') {
            $stmtPromoProd = $pdo->prepare("SELECT producto_id FROM promocion_productos WHERE promocion_id = ?");
            $stmtPromoProd->execute([$promo['id']]);
            $productos_promo = $stmtPromoProd->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($productosParaPromociones as $prod) {
                if (in_array($prod['producto_id'], $productos_promo) && !in_array($prod['orden_producto_id'], $productos_usados_ids)) {
                    for ($i = 0; $i < $prod['cantidad']; $i++) {
                        $productos_elegibles[] = $prod;
                    }
                }
            }
        } elseif ($promo['aplica_a'] === 'categorias') {
            $stmtPromoCat = $pdo->prepare("SELECT categoria FROM promocion_categorias WHERE promocion_id = ?");
            $stmtPromoCat->execute([$promo['id']]);
            $categorias_promo = $stmtPromoCat->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($productosParaPromociones as $prod) {
                if (in_array($prod['categoria'], $categorias_promo) && !in_array($prod['orden_producto_id'], $productos_usados_ids)) {
                    for ($i = 0; $i < $prod['cantidad']; $i++) {
                        $productos_elegibles[] = $prod;
                    }
                }
            }
        }
        
        if (count($productos_elegibles) < $promo['minimo_productos']) {
            continue;
        }
        
        // SIEMPRE ordenar por precio ascendente para aplicar descuento al más barato
        usort($productos_elegibles, function($a, $b) {
            return $a['precio'] <=> $b['precio'];
        });
        
        // Calcular descuento según tipo
        $monto_descuento = 0;
        $detalle = '';
        $productos_afectados = [];
        
        switch ($promo['tipo']) {
            case '2x1':
                // LÓGICA: Descontar los N productos MÁS BARATOS globalmente (donde N = número de grupos)
                // Ya están ordenados de menor a mayor precio
                $num_grupos = floor(count($productos_elegibles) / 2);
                
                // Descontar los N productos más baratos (los primeros N elementos)
                for ($i = 0; $i < $num_grupos; $i++) {
                    $monto_descuento += $productos_elegibles[$i]['precio'];
                }
                
                // Marcar TODOS los productos como afectados
                foreach ($productos_elegibles as $prod) {
                    $productos_afectados[] = $prod['orden_producto_id'];
                }
                
                $detalle = '2x1 aplicado';
                break;
                
            case '3x2':
                // LÓGICA: Descontar los N productos MÁS BARATOS globalmente (donde N = número de grupos)
                // Ya están ordenados de menor a mayor precio
                $num_grupos = floor(count($productos_elegibles) / 3);
                
                // Descontar los N productos más baratos (los primeros N elementos)
                for ($i = 0; $i < $num_grupos; $i++) {
                    $monto_descuento += $productos_elegibles[$i]['precio'];
                }
                
                // Marcar TODOS los productos como afectados
                foreach ($productos_elegibles as $prod) {
                    $productos_afectados[] = $prod['orden_producto_id'];
                }
                
                $detalle = '3x2 aplicado';
                break;
                
            case 'descuento_porcentaje':
            case 'descuento_personal':
                $porcentaje = floatval($promo['valor']);
                $subtotal_promo = 0;
                foreach ($productos_elegibles as $prod) {
                    $descuento_item = $prod['precio'] * $porcentaje / 100;
                    $monto_descuento += $descuento_item;
                    $productos_afectados[] = $prod['orden_producto_id'];
                    $subtotal_promo += $prod['precio'];
                }
                $detalle = sprintf('%d%% de descuento', $porcentaje);
                break;
                
            case 'descuento_fijo':
                $monto_descuento = floatval($promo['valor']);
                foreach ($productos_elegibles as $prod) {
                    $productos_afectados[] = $prod['orden_producto_id'];
                }
                $detalle = sprintf('Descuento fijo de $%.2f', $monto_descuento);
                break;
        }
        
        if ($monto_descuento > 0) {
            $promociones[] = [
                'id' => $promo['id'],
                'nombre' => $promo['nombre'],
                'tipo' => $promo['tipo'],
                'monto' => round($monto_descuento, 2),
                'detalle' => $detalle
            ];
            
            $total_descuentos_promociones += $monto_descuento;
            
            // Marcar productos como usados
            foreach (array_unique($productos_afectados) as $prod_id) {
                if (!in_array($prod_id, $productos_usados_ids)) {
                    $productos_usados_ids[] = $prod_id;
                }
            }
        }
    }
    } catch (Exception $e) {
        // Si hay error en promociones, continuar sin ellas
        error_log("Error calculando promociones: " . $e->getMessage());
    }
}

// 💰 Aplicar descuento porcentaje manual si está activado
$descuento_porcentaje_aplicado = 0;
if (isset($ordenInfo['aplicar_descuento_porcentaje']) && 
    $ordenInfo['aplicar_descuento_porcentaje'] == 1 && 
    isset($ordenInfo['descuento_porcentaje_valor']) && 
    $ordenInfo['descuento_porcentaje_valor'] > 0) {
    
    $porcentaje = floatval($ordenInfo['descuento_porcentaje_valor']);
    // Aplicar descuento al subtotal (antes de promociones)
    $descuento_porcentaje_aplicado = ($subtotal * $porcentaje) / 100;
    
    error_log("Descuento % manual aplicado: {$porcentaje}% sobre ${subtotal} = ${descuento_porcentaje_aplicado}");
}

$total = $subtotal - $descuento - $total_descuentos_promociones - $descuento_porcentaje_aplicado + $impuestos;

echo json_encode([
    'items' => $items,
    'subtotal' => $subtotal,
    'descuento' => $descuento,
    'impuestos' => $impuestos,
    'total' => $total,
    'total_cancelado' => $total_cancelado,
    'mesero_nombre' => $mesero_nombre,
    'productos_cancelados' => array_filter($items, function($item) { 
        return intval($item['cancelado']) > 0; 
    }),
    'promociones' => $promociones,
    'total_descuentos_promociones' => round($total_descuentos_promociones, 2),
    'tiene_promociones' => count($promociones) > 0,
    'descuento_porcentaje' => [
        'aplicado' => isset($ordenInfo['aplicar_descuento_porcentaje']) && $ordenInfo['aplicar_descuento_porcentaje'] == 1,
        'porcentaje' => floatval($ordenInfo['descuento_porcentaje_valor'] ?? 0),
        'monto' => round($descuento_porcentaje_aplicado, 2)
    ]
]);
?>