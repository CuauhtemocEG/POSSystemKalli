<?php
require_once '../conexion.php';

if (!isset($_POST['orden_id'])) {
    header('Location: ../index.php?page=mesas&error=orden_no_especificada');
    exit;
}

$orden_id = intval($_POST['orden_id']);
$metodo_pago = $_POST['metodo_pago'] ?? 'efectivo'; // Default a efectivo si no se especifica

// Capturar información del pago en efectivo
$dinero_recibido = null;
$cambio = null;

if ($metodo_pago === 'efectivo') {
    $dinero_recibido = isset($_POST['dinero_recibido']) ? floatval($_POST['dinero_recibido']) : null;
    $cambio = isset($_POST['cambio']) ? floatval($_POST['cambio']) : null;
}

// Validar método de pago
if (!in_array($metodo_pago, ['efectivo', 'debito', 'credito', 'transferencia'])) {
    $metodo_pago = 'efectivo'; // Fallback a efectivo si el valor no es válido
}

$pdo = conexion();

try {
    // Validaciones previas (ANTES de iniciar transacción)
    
    // Obtener información de la orden
    $orden = $pdo->prepare("
        SELECT o.mesa_id, m.nombre as mesa_nombre 
        FROM ordenes o 
        JOIN mesas m ON o.mesa_id = m.id 
        WHERE o.id = ? AND o.estado = 'abierta'
    ");
    $orden->execute([$orden_id]);
    $orden_data = $orden->fetch();
    
    if (!$orden_data) {
        throw new Exception('Orden no encontrada o ya está cerrada');
    }
    
    $mesa_id = $orden_data['mesa_id'];
    
    // ✅ VALIDAR QUE NO HAY PRODUCTOS SIN PREPARAR
    $productos_sin_preparar = $pdo->prepare("
        SELECT COUNT(*) as pendientes
        FROM orden_productos op 
        WHERE op.orden_id = ? 
        AND (COALESCE(op.cantidad, 0) - COALESCE(op.preparado, 0) - COALESCE(op.cancelado, 0)) > 0
    ");
    $productos_sin_preparar->execute([$orden_id]);
    $pendientes = $productos_sin_preparar->fetchColumn();
    
    if ($pendientes > 0) {
        throw new Exception("No se puede cerrar la orden. Hay {$pendientes} producto(s) sin preparar completamente. Por favor, complete la preparación o cancele los productos pendientes antes de cerrar.");
    }
    
    // Calcular el subtotal de la orden (antes de promociones)
    $subtotal_query = $pdo->prepare("
        SELECT SUM((op.cantidad - COALESCE(op.cancelado, 0)) * p.precio) as subtotal
        FROM orden_productos op 
        JOIN productos p ON op.producto_id = p.id 
        WHERE op.orden_id = ? AND (op.cantidad - COALESCE(op.cancelado, 0)) > 0
    ");
    $subtotal_query->execute([$orden_id]);
    $subtotal = $subtotal_query->fetchColumn() ?? 0;
    
    // 🎁 Calcular promociones aplicables para esta orden
    $total_descuentos_promociones = 0;
    $promociones_a_guardar = [];
    
    // Verificar si la mesa tiene promociones activadas
    $mesa_config = $pdo->prepare("SELECT aplicar_promociones, es_para_llevar FROM mesas WHERE id = ?");
    $mesa_config->execute([$mesa_id]);
    $mesa_data = $mesa_config->fetch();
    $aplicar_promociones = ($mesa_data['aplicar_promociones'] ?? 1) && !($mesa_data['es_para_llevar'] ?? 0);
    
    // Obtener si la orden tiene activado descuento personal
    $stmt_personal = $pdo->prepare("SELECT COALESCE(es_personal, 0) as es_personal FROM ordenes WHERE id = ?");
    $stmt_personal->execute([$orden_id]);
    $es_personal = (bool)$stmt_personal->fetchColumn();
    
    if ($aplicar_promociones) {
        try {
            // Obtener productos de la orden para calcular promociones
            $productos_orden = $pdo->prepare("
                SELECT 
                    op.id as orden_producto_id,
                    op.producto_id,
                    p.nombre,
                    p.precio,
                    p.categoria,
                    (op.cantidad - COALESCE(op.cancelado, 0)) as cantidad
                FROM orden_productos op
                JOIN productos p ON op.producto_id = p.id
                WHERE op.orden_id = ? 
                AND (op.cantidad - COALESCE(op.cancelado, 0)) > 0
            ");
            $productos_orden->execute([$orden_id]);
            $productos = $productos_orden->fetchAll(PDO::FETCH_ASSOC);
            
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
            
            $productos_usados = [];
            
            foreach ($promociones_activas as $promo) {
                // ⚠️ Saltar promociones de descuento personal si no está activado
                if ($promo['tipo'] === 'descuento_personal' && !$es_personal) {
                    continue;
                }
                
                $productos_elegibles = [];
                
                // Filtrar productos elegibles según el tipo de promoción
                if ($promo['aplica_a'] === 'productos') {
                    $stmtProdPromo = $pdo->prepare("SELECT producto_id FROM promocion_productos WHERE promocion_id = ?");
                    $stmtProdPromo->execute([$promo['id']]);
                    $ids_permitidos = $stmtProdPromo->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($productos as $prod) {
                        if (in_array($prod['producto_id'], $ids_permitidos) && !in_array($prod['orden_producto_id'], $productos_usados)) {
                            $productos_elegibles[] = $prod;
                        }
                    }
                } elseif ($promo['aplica_a'] === 'categorias') {
                    $stmtCatPromo = $pdo->prepare("SELECT categoria FROM promocion_categorias WHERE promocion_id = ?");
                    $stmtCatPromo->execute([$promo['id']]);
                    $cats_permitidas = $stmtCatPromo->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($productos as $prod) {
                        if (in_array($prod['categoria'], $cats_permitidas) && !in_array($prod['orden_producto_id'], $productos_usados)) {
                            $productos_elegibles[] = $prod;
                        }
                    }
                } else {
                    foreach ($productos as $prod) {
                        if (!in_array($prod['orden_producto_id'], $productos_usados)) {
                            $productos_elegibles[] = $prod;
                        }
                    }
                }
                
                if (count($productos_elegibles) < intval($promo['minimo_productos'])) {
                    continue;
                }
                
                $monto_descuento = 0;
                $productos_afectados = [];
                
                // Calcular descuento según tipo de promoción
                switch ($promo['tipo']) {
                    case '2x1':
                        // Expandir productos por cantidad y ordenar por precio ascendente
                        $items_expandidos = [];
                        foreach ($productos_elegibles as $prod) {
                            for ($i = 0; $i < $prod['cantidad']; $i++) {
                                $items_expandidos[] = $prod;
                            }
                        }
                        
                        // Ordenar por precio ascendente (más barato primero)
                        usort($items_expandidos, function($a, $b) {
                            return $a['precio'] <=> $b['precio'];
                        });
                        
                        $total_items = count($items_expandidos);
                        $pares = floor($total_items / 2);
                        
                        // Aplicar descuento: descontar los N productos más baratos (donde N = número de pares)
                        // Ejemplo: [70, 70, 85, 85] -> 2 pares -> descontar índices 0 y 1 (los 2 de $70)
                        for ($i = 0; $i < $pares; $i++) {
                            $item_gratis = $items_expandidos[$i];
                            $monto_descuento += $item_gratis['precio'];
                            $productos_afectados[] = $item_gratis['orden_producto_id'];
                        }
                        
                        // Marcar TODOS los productos como afectados por la promoción
                        foreach ($items_expandidos as $item) {
                            $productos_afectados[] = $item['orden_producto_id'];
                        }
                        break;
                        
                    case '3x2':
                        // Expandir productos por cantidad y ordenar por precio ascendente
                        $items_expandidos = [];
                        foreach ($productos_elegibles as $prod) {
                            for ($i = 0; $i < $prod['cantidad']; $i++) {
                                $items_expandidos[] = $prod;
                            }
                        }
                        
                        // Ordenar por precio ascendente (más barato primero)
                        usort($items_expandidos, function($a, $b) {
                            return $a['precio'] <=> $b['precio'];
                        });
                        
                        $total_items = count($items_expandidos);
                        $grupos = floor($total_items / 3);
                        
                        // Aplicar descuento: descontar los N productos más baratos (donde N = número de grupos)
                        // Ejemplo: [70, 70, 85, 85, 85, 85] -> 2 grupos -> descontar índices 0 y 1 (los 2 de $70)
                        for ($i = 0; $i < $grupos; $i++) {
                            $item_gratis = $items_expandidos[$i];
                            $monto_descuento += $item_gratis['precio'];
                            $productos_afectados[] = $item_gratis['orden_producto_id'];
                        }
                        
                        // Marcar TODOS los productos como afectados por la promoción
                        foreach ($items_expandidos as $item) {
                            $productos_afectados[] = $item['orden_producto_id'];
                        }
                        break;
                        
                    case 'descuento_porcentaje':
                        $porcentaje = floatval($promo['valor']) / 100;
                        foreach ($productos_elegibles as $prod) {
                            $monto_descuento += ($prod['precio'] * $prod['cantidad']) * $porcentaje;
                            $productos_afectados[] = $prod['orden_producto_id'];
                        }
                        break;
                        
                    case 'descuento_personal':
                        // Descuento personal: aplica porcentaje a todos los productos elegibles
                        $porcentaje = floatval($promo['valor']) / 100;
                        foreach ($productos_elegibles as $prod) {
                            $monto_descuento += ($prod['precio'] * $prod['cantidad']) * $porcentaje;
                            $productos_afectados[] = $prod['orden_producto_id'];
                        }
                        break;
                        
                    case 'descuento_fijo':
                        $monto_descuento = floatval($promo['valor']);
                        foreach ($productos_elegibles as $prod) {
                            $productos_afectados[] = $prod['orden_producto_id'];
                        }
                        break;
                }
                
                if ($monto_descuento > 0) {
                    $promociones_a_guardar[] = [
                        'promocion_id' => $promo['id'],
                        'descuento' => round($monto_descuento, 2),
                        'productos' => implode(',', array_unique($productos_afectados))
                    ];
                    $total_descuentos_promociones += $monto_descuento;
                    $productos_usados = array_merge($productos_usados, $productos_afectados);
                }
            }
        } catch (Exception $e) {
            error_log("Error calculando promociones al cerrar orden {$orden_id}: " . $e->getMessage());
        }
    }
    
    // 💰 Aplicar descuento porcentaje manual si está activado
    $descuento_porcentaje_aplicado = 0;
    $stmt_desc = $pdo->prepare("SELECT aplicar_descuento_porcentaje, descuento_porcentaje_valor FROM ordenes WHERE id = ?");
    $stmt_desc->execute([$orden_id]);
    $desc_data = $stmt_desc->fetch();
    
    if ($desc_data && 
        $desc_data['aplicar_descuento_porcentaje'] == 1 && 
        $desc_data['descuento_porcentaje_valor'] > 0) {
        
        $porcentaje = floatval($desc_data['descuento_porcentaje_valor']);
        // Aplicar descuento al subtotal (antes de promociones)
        $descuento_porcentaje_aplicado = ($subtotal * $porcentaje) / 100;
        
        error_log("Descuento % manual aplicado al cerrar orden {$orden_id}: {$porcentaje}% sobre ${subtotal} = ${descuento_porcentaje_aplicado}");
    }
    
    // Calcular el total final (subtotal - promociones - descuento % manual)
    $total = $subtotal - $total_descuentos_promociones - $descuento_porcentaje_aplicado;
    
    // AHORA sí iniciar transacción para las operaciones de escritura
    $pdo->beginTransaction();
    
    // Actualizar la orden con subtotal, total, método de pago, dinero recibido, cambio y fecha de cierre
    if ($metodo_pago === 'efectivo' && $dinero_recibido !== null) {
        $update_orden = $pdo->prepare("
            UPDATE ordenes 
            SET estado = 'cerrada',
                subtotal = ?,
                total = ?,
                metodo_pago = ?,
                dinero_recibido = ?,
                cambio = ?,
                cerrada_en = NOW()
            WHERE id = ?
        ");
        $result = $update_orden->execute([$subtotal, $total, $metodo_pago, $dinero_recibido, $cambio, $orden_id]);
    } else {
        $update_orden = $pdo->prepare("
            UPDATE ordenes 
            SET estado = 'cerrada',
                subtotal = ?,
                total = ?,
                metodo_pago = ?,
                cerrada_en = NOW()
            WHERE id = ?
        ");
        $result = $update_orden->execute([$subtotal, $total, $metodo_pago, $orden_id]);
    }
    
    if (!$result) {
        throw new Exception('Error al actualizar la orden');
    }
    
    // Guardar promociones aplicadas en la tabla de promociones_aplicadas
    if (!empty($promociones_a_guardar)) {
        $stmt_promo = $pdo->prepare("
            INSERT INTO promociones_aplicadas (orden_id, promocion_id, descuento_aplicado, productos_afectados)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($promociones_a_guardar as $promo_data) {
            $stmt_promo->execute([
                $orden_id,
                $promo_data['promocion_id'],
                $promo_data['descuento'],
                $promo_data['productos']
            ]);
        }
    }
    
    // Actualizar estado de la mesa a 'disponible'
    $update_mesa = $pdo->prepare("UPDATE mesas SET estado = 'disponible' WHERE id = ?");
    $result_mesa = $update_mesa->execute([$mesa_id]);
    
    if (!$result_mesa) {
        throw new Exception('Error al actualizar el estado de la mesa');
    }
    
    // Registrar en historial de órdenes
    session_start();
    $usuario_id = $_SESSION['user_id'] ?? null;
    
    $metodos_nombres = [
        'efectivo' => 'Efectivo',
        'debito' => 'Débito', 
        'credito' => 'Crédito',
        'transferencia' => 'Transferencia'
    ];
    $nombre_metodo = $metodos_nombres[$metodo_pago] ?? ucfirst($metodo_pago);
    
    $detalle_historial = "Orden cerrada exitosamente. Total: $" . number_format($total, 2) . ". Método de pago: " . $nombre_metodo;
    
    if ($metodo_pago === 'efectivo' && $dinero_recibido !== null) {
        $detalle_historial .= ". Dinero recibido: $" . number_format($dinero_recibido, 2);
        if ($cambio !== null && $cambio > 0) {
            $detalle_historial .= ". Cambio: $" . number_format($cambio, 2);
        } else {
            $detalle_historial .= ". Pago exacto";
        }
    }
    
    $detalle_historial .= ". Mesa: " . $orden_data['mesa_nombre'];
    
    $stmt_historial = $pdo->prepare("
        INSERT INTO historial_ordenes (orden_id, accion, detalle, usuario_id) 
        VALUES (?, 'ORDEN_CERRADA', ?, ?)
    ");
    $stmt_historial->execute([$orden_id, $detalle_historial, $usuario_id]);
    
    // Confirmar transacción ANTES de intentar impresión
    $pdo->commit();
    
    // Verificar si está configurada la impresión automática
    $config_impresion = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('impresion_automatica', 'nombre_impresora')");
    $config_impresion->execute();
    $config_datos = [];
    while ($row = $config_impresion->fetch()) {
        $config_datos[$row['clave']] = $row['valor'];
    }
    
    $impresion_automatica = ($config_datos['impresion_automatica'] ?? '0') == '1';
    $nombre_impresora = $config_datos['nombre_impresora'] ?? '';
    
    // Preparar parámetros de redirección (usar la variable ya definida)
    $mensajeSuccess = "Orden cerrada exitosamente. Total: $" . number_format($total, 2) . " - Método: " . $nombre_metodo;
    
    if ($metodo_pago === 'efectivo' && $dinero_recibido !== null) {
        $mensajeSuccess .= " - Recibido: $" . number_format($dinero_recibido, 2);
        if ($cambio !== null && $cambio > 0) {
            $mensajeSuccess .= " - Cambio: $" . number_format($cambio, 2);
        } else {
            $mensajeSuccess .= " - Pago exacto";
        }
    }
    
    $params = [
        'success' => $mensajeSuccess
    ];
    
    // Si está configurada la impresión automática, imprimir directamente
    if ($impresion_automatica && !empty($nombre_impresora)) {
        try {
            // Guardar el REQUEST_METHOD original
            $originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
            
            // Temporalmente cambiar REQUEST_METHOD para evitar que imprimir_termica.php procese JSON
            $_SERVER['REQUEST_METHOD'] = 'GET';
            
            // Incluir solo la clase de impresión térmica
            require_once 'imprimir_termica.php';
            
            // Restaurar REQUEST_METHOD original
            $_SERVER['REQUEST_METHOD'] = $originalRequestMethod;
            
            // Obtener datos de la orden para impresión
            $stmt = $pdo->prepare("SELECT * FROM ordenes o JOIN mesas m ON o.mesa_id = m.id WHERE o.id = ?");
            $stmt->execute([$orden_id]);
            $orden_data = $stmt->fetch();
            
            // Obtener productos de la orden AGRUPADOS por producto (igual que imprimir_termica.php)
            // Suma todas las cantidades del mismo producto (confirmado=1) menos las canceladas
            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.nombre, 
                    p.precio,
                    p.categoria,
                    SUM(op.cantidad - COALESCE(op.cancelado, 0)) as cantidad
                FROM orden_productos op 
                JOIN productos p ON op.producto_id = p.id 
                WHERE op.orden_id = ?
                  AND COALESCE(op.confirmado, 1) = 1
                GROUP BY p.id, p.nombre, p.precio, p.categoria
                HAVING SUM(op.cantidad - COALESCE(op.cancelado, 0)) > 0
                ORDER BY p.nombre
            ");
            $stmt->execute([$orden_id]);
            $productos = $stmt->fetchAll();
            
            // Obtener productos cancelados AGRUPADOS (cantidades canceladas)
            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.nombre, 
                    p.precio, 
                    SUM(op.cancelado) as cantidad
                FROM orden_productos op 
                JOIN productos p ON op.producto_id = p.id 
                WHERE op.orden_id = ? 
                  AND op.cancelado > 0
                GROUP BY p.id, p.nombre, p.precio
                HAVING SUM(op.cancelado) > 0
                ORDER BY p.nombre
            ");
            $stmt->execute([$orden_id]);
            $productosCancelados = $stmt->fetchAll();
            
            // Obtener nombre de empresa y dirección
            $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('empresa_nombre', 'empresa_direccion')");
            $stmt->execute();
            $configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $empresaNombre = $configuraciones['empresa_nombre'] ?? 'Kalli Jaguar';
            $empresaDireccion = $configuraciones['empresa_direccion'] ?? '';
            
            // Crear instancia de impresora
            $impresora = new ImpresorTermica();
            
            // Generar ticket
            $impresora->imagenConfigurada();
                        
            // Mostrar dirección si está configurada
            if (!empty($empresaDireccion)) {
                $lineasDireccion = $impresora->dividirTextoParaTicket($empresaDireccion, 32);
                foreach ($lineasDireccion as $linea) {
                    $impresora->texto($linea, 'center');
                }
            }
            $impresora->saltoLinea();
            
            $impresora->texto('Sucursal: ' . $empresaNombre, 'left');
            $impresora->texto('Mesa: ' . $orden_data['nombre'], 'left');
            $impresora->texto('Orden: #' . $orden_data['codigo'], 'left');
            $impresora->texto('Fecha: ' . date('d/m/Y H:i:s', strtotime($orden_data['creada_en'])), 'left');
            $impresora->saltoLinea();
            $impresora->linea('=', 45);
            $impresora->saltoLinea();
            
            // Productos
            if (!empty($productos)) {
                $impresora->tablaProductos($productos);
            }
            
            // ✅ USAR SUBTOTAL YA CALCULADO (no recalcular)
            // El subtotal ($subtotal) ya fue calculado correctamente en la línea 63
            // y guardado en la base de datos
            
            $impresora->saltoLinea();
            $impresora->texto('Subtotal: $' . number_format($subtotal, 2), 'right');
            
            // 🎁 USAR LOS DATOS DE PROMOCIONES YA CALCULADOS (no recalcular)
            // Los valores de $total_descuentos_promociones y $promociones_a_guardar 
            // ya fueron calculados correctamente en las líneas 65-240
            
            // Obtener nombres de las promociones aplicadas para mostrar en el ticket
            $promociones_ticket = [];
            if (!empty($promociones_a_guardar)) {
                foreach ($promociones_a_guardar as $promo_guardada) {
                    $stmtPromoNombre = $pdo->prepare("SELECT nombre, tipo FROM promociones WHERE id = ?");
                    $stmtPromoNombre->execute([$promo_guardada['promocion_id']]);
                    $promo_info = $stmtPromoNombre->fetch(PDO::FETCH_ASSOC);
                    
                    if ($promo_info) {
                        $promociones_ticket[] = [
                            'nombre' => $promo_info['nombre'],
                            'tipo' => $promo_info['tipo'],
                            'monto' => $promo_guardada['descuento'],
                            'detalle' => ''
                        ];
                    }
                }
            }
            
            // Mostrar promociones en el ticket
            if (count($promociones_ticket) > 0) {
                $impresora->saltoLinea();
                $impresora->linea('-', 45);
                $impresora->texto('PROMOCIONES APLICADAS:', 'left', true);
                
                foreach ($promociones_ticket as $promo) {
                    // Limitar nombre a 25 caracteres
                    $nombrePromo = substr($promo['nombre'], 0, 25);
                    $montoPromo = '-$' . number_format($promo['monto'], 2);
                    
                    // Formatear línea: NOMBRE................-$XXX.XX
                    $espacios = 45 - strlen($nombrePromo) - strlen($montoPromo);
                    $linea = $nombrePromo . str_repeat('.', max(1, $espacios)) . $montoPromo;
                    $impresora->texto($linea, 'left');
                }
                
                $impresora->saltoLinea();
                $impresora->texto('Total Descuentos: -$' . number_format($total_descuentos_promociones, 2), 'right', true);
            }
            
            // 💰 Mostrar descuento porcentaje manual si está aplicado
            if ($descuento_porcentaje_aplicado > 0) {
                $impresora->saltoLinea();
                $impresora->linea('-', 45);
                $impresora->texto('DESCUENTO MANUAL APLICADO:', 'left', true);
                
                $porcentaje_str = number_format($desc_data['descuento_porcentaje_valor'], 1) . '%';
                $monto_desc_str = '-$' . number_format($descuento_porcentaje_aplicado, 2);
                
                // Formatear línea: Descuento XX%...........-$XXX.XX
                $espacios = 45 - strlen('Descuento ' . $porcentaje_str) - strlen($monto_desc_str);
                $linea = 'Descuento ' . $porcentaje_str . str_repeat('.', max(1, $espacios)) . $monto_desc_str;
                $impresora->texto($linea, 'left');
                
                $impresora->saltoLinea();
                $impresora->texto('Descuento Manual: -$' . number_format($descuento_porcentaje_aplicado, 2), 'right', true);
            }
            
            // ✅ USAR TOTAL YA CALCULADO (no recalcular)
            // El total ($total) ya fue calculado correctamente en la línea 263
            // como: $total = $subtotal - $total_descuentos_promociones - $descuento_porcentaje_aplicado
            
            // Total
            $impresora->saltoLinea();
            $impresora->linea('=', 45);
            $impresora->texto('TOTAL: $' . number_format($total, 2), 'right', true, 'large');
            $impresora->saltoLinea();
            
            // Total en texto (crear una función temporal aquí)
            $numeroATexto = function($numero) {
                // Función simplificada para convertir números a texto
                $pesos = intval($numero);
                $centavos = intval(($numero - $pesos) * 100);
                
                $unidades = ["", "UNO", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE"];
                $decenas = ["", "", "VEINTE", "TREINTA", "CUARENTA", "CINCUENTA", "SESENTA", "SETENTA", "OCHENTA", "NOVENTA"];
                $especiales = [
                    10 => "DIEZ", 11 => "ONCE", 12 => "DOCE", 13 => "TRECE", 14 => "CATORCE", 15 => "QUINCE",
                    16 => "DIECISEIS", 17 => "DIECISIETE", 18 => "DIECIOCHO", 19 => "DIECINUEVE",
                    21 => "VEINTIUNO", 22 => "VEINTIDOS", 23 => "VEINTITRES", 24 => "VEINTICUATRO", 25 => "VEINTICINCO",
                    26 => "VEINTISEIS", 27 => "VEINTISIETE", 28 => "VEINTIOCHO", 29 => "VEINTINUEVE"
                ];
                $centenas = ["", "CIENTO", "DOSCIENTOS", "TRESCIENTOS", "CUATROCIENTOS", "QUINIENTOS", 
                            "SEISCIENTOS", "SETECIENTOS", "OCHOCIENTOS", "NOVECIENTOS"];
                
                $convertirNumero = function($num) use ($unidades, $decenas, $especiales, $centenas) {
                    if ($num == 0) return "CERO";
                    
                    $texto = "";
                    
                    // Manejar miles
                    if ($num >= 1000) {
                        $miles = intval($num / 1000);
                        if ($miles == 1) {
                            $texto .= "MIL ";
                        } else {
                            // Recursión para convertir los miles
                            $textoMiles = "";
                            if ($miles < 30 && isset($especiales[$miles])) {
                                $textoMiles = $especiales[$miles];
                            } else {
                                // Convertir miles usando la misma lógica
                                if ($miles >= 100) {
                                    $cenMiles = intval($miles / 100);
                                    if ($miles == 100) {
                                        $textoMiles .= "CIEN";
                                    } else {
                                        $textoMiles .= $centenas[$cenMiles];
                                    }
                                    $miles %= 100;
                                    if ($miles > 0) $textoMiles .= " ";
                                }
                                
                                if ($miles >= 30) {
                                    $decMiles = intval($miles / 10);
                                    $uniMiles = $miles % 10;
                                    $textoMiles .= $decenas[$decMiles];
                                    if ($uniMiles > 0) $textoMiles .= " Y " . $unidades[$uniMiles];
                                } elseif ($miles >= 10) {
                                    $textoMiles .= $especiales[$miles] ?? ($decenas[intval($miles/10)] . ($miles%10 > 0 ? " Y " . $unidades[$miles%10] : ""));
                                } elseif ($miles > 0) {
                                    $textoMiles .= $unidades[$miles];
                                }
                            }
                            $texto .= $textoMiles . " MIL ";
                        }
                        $num %= 1000;
                    }
                    
                    // Manejar centenas, decenas y unidades
                    if ($num < 30 && isset($especiales[$num])) {
                        $texto .= $especiales[$num];
                    } else {
                        if ($num >= 100) {
                            $cen = intval($num / 100);
                            if ($num == 100) {
                                $texto .= "CIEN";
                            } elseif ($cen <= 9) {
                                $texto .= $centenas[$cen];
                            }
                            $num %= 100;
                            if ($num > 0) $texto .= " ";
                        }
                        
                        if ($num >= 30) {
                            $dec = intval($num / 10);
                            $uni = $num % 10;
                            $texto .= $decenas[$dec];
                            if ($uni > 0) $texto .= " Y " . $unidades[$uni];
                        } elseif ($num >= 10) {
                            $texto .= $especiales[$num] ?? ($decenas[intval($num/10)] . ($num%10 > 0 ? " Y " . $unidades[$num%10] : ""));
                        } elseif ($num > 0) {
                            $texto .= $unidades[$num];
                        }
                    }
                    
                    return trim($texto);
                };
                
                $resultado = "";
                if ($pesos == 0) {
                    $resultado = "CERO PESOS";
                } elseif ($pesos == 1) {
                    $resultado = "UN PESO";
                } else {
                    $resultado = $convertirNumero($pesos) . " PESOS";
                }
                
                if ($centavos > 0) {
                    if ($centavos == 1) {
                        $resultado .= " CON UN CENTAVO";
                    } else {
                        $resultado .= " CON " . $convertirNumero($centavos) . " CENTAVOS";
                    }
                }
                
                return $resultado . " 00/100 M.N.";
            };
            
            $totalTexto = $numeroATexto($total);
            $impresora->texto($totalTexto, 'center', false, 'normal');
            $impresora->saltoLinea();
            
            // Información del pago
            $impresora->linea('-', 45);
            
            // Formatear método de pago para impresión
            $metodos_formato = [
                'efectivo' => 'EFECTIVO',
                'debito' => 'TARJETA DE DÉBITO',
                'credito' => 'TARJETA DE CRÉDITO',
                'transferencia' => 'TRANSFERENCIA BANCARIA'
            ];
            $metodo_formateado = $metodos_formato[$orden_data['metodo_pago']] ?? strtoupper($orden_data['metodo_pago']);
            
            $impresora->texto('METODO DE PAGO: ' . $metodo_formateado, 'left', true);
            
            if ($orden_data['metodo_pago'] === 'efectivo' && $orden_data['dinero_recibido'] !== null) {
                $impresora->texto('Dinero recibido: $' . number_format($orden_data['dinero_recibido'], 2), 'left');
                if ($orden_data['cambio'] !== null && $orden_data['cambio'] > 0) {
                    $impresora->texto('Cambio: $' . number_format($orden_data['cambio'], 2), 'left', true);
                } else {
                    $impresora->texto('Pago exacto', 'left');
                }
            }
            $impresora->saltoLinea();
            
            // Productos cancelados si existen
            if (!empty($productosCancelados)) {
                $impresora->linea('-', 45);
                $impresora->texto('PRODUCTOS CANCELADOS:', 'left', true);
                $impresora->saltoLinea();
                
                // Usar el mismo formato que imprimir_termica.php: PRODUCTO | P. UNIT | CANT | PRECIO
                foreach ($productosCancelados as $producto) {
                    $nombre = substr($producto['nombre'], 0, 20);
                    $precioUnitario = str_pad('$' . number_format($producto['precio'], 2), 7, ' ', STR_PAD_LEFT);
                    $cantidad = str_pad($producto['cantidad'], 4, ' ', STR_PAD_LEFT);
                    $precioTotal = str_pad('$' . number_format($producto['precio'] * $producto['cantidad'], 2), 10, ' ', STR_PAD_LEFT);
                    
                    $linea = str_pad($nombre, 22) . $precioUnitario . ' ' . $cantidad . ' ' . $precioTotal;
                    $impresora->texto($linea, 'left');
                }
                $impresora->saltoLinea();
            }
            
            $impresora->linea('=', 45);
            $impresora->texto('Gracias por su compra!', 'center');
            $impresora->saltoLinea();
            $impresora->cortar();
            
            // Enviar a impresora
            $resultado = $impresora->imprimir($nombre_impresora);
            
            $params['impresion_exitosa'] = '1';
            $params['mensaje'] = 'Ticket impreso automáticamente en ' . $nombre_impresora;
            
        } catch (Exception $e) {
            // No fallar la orden si la impresión falla
            $params['impresion_error'] = '1';
            $params['mensaje'] = 'Error de impresión: ' . $e->getMessage();
            error_log("Error en impresión automática: " . $e->getMessage());
        }
    }
    
    // Redirección exitosa
    header('Location: ../index.php?page=mesas&' . http_build_query($params));
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error (solo si hay una activa)
    if ($pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Exception $rollbackException) {
            error_log("Error en rollback: " . $rollbackException->getMessage());
        }
    }
    
    // Redirección con error
    header('Location: ../index.php?page=mesas&error=' . urlencode($e->getMessage()));
    exit;
}
?>