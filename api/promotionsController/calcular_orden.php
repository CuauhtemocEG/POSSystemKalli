<?php
require_once '../../conexion.php';
header('Content-Type: application/json');

/**
 * Calcula y aplica las promociones disponibles a una orden
 * 
 * Recibe: orden_id, es_personal (opcional)
 * Retorna: descuentos aplicables con detalle
 */

$pdo = conexion();

try {
    $orden_id = intval($_POST['orden_id'] ?? $_GET['orden_id'] ?? 0);
    $es_personal = isset($_POST['es_personal']) ? boolval($_POST['es_personal']) : false;
    
    if (!$orden_id) {
        throw new Exception('ID de orden requerido');
    }
    
    // Obtener productos de la orden con sus detalles
    $stmt = $pdo->prepare("
        SELECT 
            op.id as orden_producto_id,
            op.producto_id,
            op.cantidad,
            op.preparado,
            p.nombre as producto_nombre,
            p.precio,
            p.categoria,
            p.tiene_variedades,
            (op.cantidad * p.precio) as subtotal
        FROM orden_productos op
        JOIN productos p ON op.producto_id = p.id
        WHERE op.orden_id = ?
        ORDER BY p.precio DESC
    ");
    $stmt->execute([$orden_id]);
    $productos_orden = $stmt->fetchAll();
    
    if (empty($productos_orden)) {
        echo json_encode([
            'status' => 'ok',
            'promociones' => [],
            'total_descuentos' => 0,
            'mensaje' => 'No hay productos en la orden'
        ]);
        exit;
    }
    
    // Obtener promociones activas
    $stmt = $pdo->query("
        SELECT p.*
        FROM promociones p
        WHERE p.activa = 1
        AND (p.fecha_inicio IS NULL OR p.fecha_inicio <= NOW())
        AND (p.fecha_fin IS NULL OR p.fecha_fin >= NOW())
        ORDER BY p.prioridad DESC, p.id DESC
    ");
    $promociones_activas = $stmt->fetchAll();
    
    $promociones_aplicadas = [];
    $productos_usados_ids = [];
    
    foreach ($promociones_activas as $promo) {
        // Verificar si es promoción de personal
        if ($promo['tipo'] === 'descuento_personal' && !$es_personal) {
            continue;
        }
        
        // Filtrar productos elegibles para esta promoción
        $productos_elegibles = filtrarProductosElegibles($pdo, $productos_orden, $promo, $productos_usados_ids);
        
        if (count($productos_elegibles) < $promo['minimo_productos']) {
            continue;
        }
        
        // Calcular descuento
        $resultado = calcularDescuento($promo, $productos_elegibles);
        
        if ($resultado['monto'] > 0) {
            $promociones_aplicadas[] = [
                'id' => $promo['id'],
                'nombre' => $promo['nombre'],
                'descripcion' => $promo['descripcion'],
                'tipo' => $promo['tipo'],
                'monto' => round($resultado['monto'], 2),
                'productos_afectados' => $resultado['productos_afectados'],
                'detalle' => $resultado['detalle']
            ];
            
            // Marcar productos como usados si la promoción no es acumulable
            foreach ($resultado['productos_afectados'] as $prod_id) {
                if (!in_array($prod_id, $productos_usados_ids)) {
                    $productos_usados_ids[] = $prod_id;
                }
            }
        }
    }
    
    $total_descuentos = array_sum(array_column($promociones_aplicadas, 'monto'));
    
    echo json_encode([
        'status' => 'ok',
        'promociones' => $promociones_aplicadas,
        'total_descuentos' => round($total_descuentos, 2),
        'tiene_promociones' => count($promociones_aplicadas) > 0
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
}

/**
 * Filtra los productos de la orden que son elegibles para una promoción
 */
function filtrarProductosElegibles($pdo, $productos_orden, $promo, $productos_usados) {
    $elegibles = [];
    
    if ($promo['aplica_a'] === 'todos') {
        // Aplica a todos los productos no usados
        foreach ($productos_orden as $prod) {
            if (!in_array($prod['orden_producto_id'], $productos_usados)) {
                // Expandir por cantidad
                for ($i = 0; $i < $prod['cantidad']; $i++) {
                    $elegibles[] = [
                        'orden_producto_id' => $prod['orden_producto_id'],
                        'producto_id' => $prod['producto_id'],
                        'nombre' => $prod['producto_nombre'],
                        'precio' => floatval($prod['precio']),
                        'categoria' => $prod['categoria']
                    ];
                }
            }
        }
    } elseif ($promo['aplica_a'] === 'productos') {
        // Obtener productos específicos de la promoción
        $stmt = $pdo->prepare("SELECT producto_id FROM promocion_productos WHERE promocion_id = ?");
        $stmt->execute([$promo['id']]);
        $productos_promo = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($productos_orden as $prod) {
            if (in_array($prod['producto_id'], $productos_promo) && !in_array($prod['orden_producto_id'], $productos_usados)) {
                for ($i = 0; $i < $prod['cantidad']; $i++) {
                    $elegibles[] = [
                        'orden_producto_id' => $prod['orden_producto_id'],
                        'producto_id' => $prod['producto_id'],
                        'nombre' => $prod['producto_nombre'],
                        'precio' => floatval($prod['precio']),
                        'categoria' => $prod['categoria']
                    ];
                }
            }
        }
    } elseif ($promo['aplica_a'] === 'categorias') {
        // Obtener categorías de la promoción
        $stmt = $pdo->prepare("SELECT categoria FROM promocion_categorias WHERE promocion_id = ?");
        $stmt->execute([$promo['id']]);
        $categorias_promo = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($productos_orden as $prod) {
            if (in_array($prod['categoria'], $categorias_promo) && !in_array($prod['orden_producto_id'], $productos_usados)) {
                for ($i = 0; $i < $prod['cantidad']; $i++) {
                    $elegibles[] = [
                        'orden_producto_id' => $prod['orden_producto_id'],
                        'producto_id' => $prod['producto_id'],
                        'nombre' => $prod['producto_nombre'],
                        'precio' => floatval($prod['precio']),
                        'categoria' => $prod['categoria']
                    ];
                }
            }
        }
    }
    
    return $elegibles;
}

/**
 * Calcula el descuento según el tipo de promoción
 */
function calcularDescuento($promo, $productos) {
    $resultado = [
        'monto' => 0,
        'productos_afectados' => [],
        'detalle' => ''
    ];
    
    // Ordenar por precio descendente si aplica tomar el mayor valor
    if ($promo['aplicar_mayor_valor']) {
        usort($productos, function($a, $b) {
            return $b['precio'] <=> $a['precio'];
        });
    }
    
    switch ($promo['tipo']) {
        case '2x1':
            // LÓGICA: De todos los productos elegibles, descontar los más baratos globalmente
            // Por cada 2 productos = 1 gratis (el más barato de TODOS)
            // Ejemplo: 4 productos = 2 grupos = descuentan los 2 MÁS BARATOS de los 4
            
            $num_grupos = floor(count($productos) / 2);
            
            // DEBUG: Log de productos antes de ordenar
            error_log("=== DEBUG 2x1 ===");
            error_log("Productos ANTES de ordenar: " . json_encode(array_map(function($p) {
                return ['nombre' => $p['nombre'], 'precio' => $p['precio']];
            }, $productos)));
            error_log("Número de grupos: " . $num_grupos);
            
            if ($num_grupos > 0) {
                // Ordenar de MENOR a MAYOR para tomar los más baratos al inicio
                usort($productos, function($a, $b) {
                    return $a['precio'] <=> $b['precio']; // Ascendente
                });
                
                // DEBUG: Log de productos después de ordenar
                error_log("Productos DESPUÉS de ordenar: " . json_encode(array_map(function($p) {
                    return ['nombre' => $p['nombre'], 'precio' => $p['precio']];
                }, $productos)));
                
                $productos_desc = [];
                
                // Descontar los N productos más baratos (donde N = número de grupos)
                for ($i = 0; $i < $num_grupos; $i++) {
                    $producto_desc = $productos[$i];
                    $resultado['monto'] += $producto_desc['precio'];
                    $productos_desc[] = $producto_desc['nombre'];
                    
                    // DEBUG: Log cada producto descontado
                    error_log("Descontando producto #" . ($i+1) . ": " . $producto_desc['nombre'] . " - $" . $producto_desc['precio']);
                }
                
                // DEBUG: Log del total de descuento
                error_log("Total de descuento 2x1: $" . $resultado['monto']);
                error_log("Productos gratis: " . implode(', ', $productos_desc));
                
                // Añadir TODOS los productos al array de afectados
                foreach ($productos as $prod) {
                    $resultado['productos_afectados'][] = $prod['orden_producto_id'];
                }
                
                $resultado['detalle'] = '2x1 aplicado: ' . implode(', ', array_unique($productos_desc)) . ' gratis';
            }
            break;
            
        case '3x2':
            // LÓGICA: De todos los productos elegibles, descontar los más baratos globalmente
            // Por cada 3 productos = 1 gratis (el más barato de TODOS)
            // Ejemplo: 6 productos = 2 grupos = descuentan los 2 MÁS BARATOS de los 6
            
            $num_grupos = floor(count($productos) / 3);
            
            if ($num_grupos > 0) {
                // Ordenar de MENOR a MAYOR para tomar los más baratos al inicio
                usort($productos, function($a, $b) {
                    return $a['precio'] <=> $b['precio']; // Ascendente
                });
                
                $productos_desc = [];
                
                // Descontar los N productos más baratos (donde N = número de grupos)
                for ($i = 0; $i < $num_grupos; $i++) {
                    $producto_desc = $productos[$i];
                    $resultado['monto'] += $producto_desc['precio'];
                    $productos_desc[] = $producto_desc['nombre'];
                }
                
                // Añadir TODOS los productos al array de afectados
                foreach ($productos as $prod) {
                    $resultado['productos_afectados'][] = $prod['orden_producto_id'];
                }
                
                $resultado['detalle'] = '3x2 aplicado: ' . implode(', ', array_unique($productos_desc)) . ' gratis';
            }
            break;
            
        case 'descuento_porcentaje':
        case 'descuento_personal':
            // Aplicar porcentaje a todos los productos elegibles
            $porcentaje = floatval($promo['valor']);
            $subtotal = 0;
            
            foreach ($productos as $prod) {
                $descuento_item = $prod['precio'] * $porcentaje / 100;
                $resultado['monto'] += $descuento_item;
                $resultado['productos_afectados'][] = $prod['orden_producto_id'];
                $subtotal += $prod['precio'];
            }
            
            $resultado['detalle'] = sprintf('%d%% de descuento sobre $%.2f', $porcentaje, $subtotal);
            break;
            
        case 'descuento_fijo':
            // Descuento fijo total
            $resultado['monto'] = floatval($promo['valor']);
            foreach ($productos as $prod) {
                $resultado['productos_afectados'][] = $prod['orden_producto_id'];
            }
            $resultado['detalle'] = sprintf('Descuento fijo de $%.2f', $resultado['monto']);
            break;
    }
    
    // Eliminar duplicados en productos afectados
    $resultado['productos_afectados'] = array_unique($resultado['productos_afectados']);
    
    return $resultado;
}
?>
