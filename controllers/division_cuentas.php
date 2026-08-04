<?php
/**
 * Controlador para División de Cuentas
 * Maneja los pagos parciales cuando una cuenta se divide entre varios pagadores
 */

// Evitar warnings y notices que rompen el JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Buffer de salida para capturar cualquier output no deseado
ob_start();

// Incluir conexión
require_once __DIR__ . '/../conexion.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar cualquier output buffer previo
ob_clean();

// Establecer header JSON
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo = conexion();

try {
    switch ($action) {
        
        /**
         * Iniciar división de cuenta
         * POST: orden_id, numero_divisiones
         */
        case 'iniciar_division':
            $orden_id = intval($_POST['orden_id'] ?? 0);
            $numero_divisiones = intval($_POST['numero_divisiones'] ?? 2);
            $usuario_id = $_SESSION['user_id'] ?? null;
            
            if ($orden_id <= 0) {
                throw new Exception('ID de orden inválido');
            }
            
            if ($numero_divisiones < 2) {
                throw new Exception('Debe dividir la cuenta en al menos 2 partes');
            }
            
            // Verificar que la orden existe y está abierta
            $stmt = $pdo->prepare("SELECT id, total, subtotal FROM ordenes WHERE id = ? AND estado = 'abierta'");
            $stmt->execute([$orden_id]);
            $orden = $stmt->fetch();
            
            if (!$orden) {
                throw new Exception('Orden no encontrada o ya está cerrada');
            }
            
            // 🎁 Recalcular total incluyendo promociones/descuentos antes de iniciar la división
            // El total en ordenes puede ser el subtotal bruto; debemos usar el total real con descuentos
            $total_con_promo = calcularTotalFinalOrden($pdo, $orden_id);
            if ($total_con_promo !== null && $total_con_promo > 0 && abs($total_con_promo - $orden['total']) > 0.01) {
                $stmt_upd = $pdo->prepare("UPDATE ordenes SET total = ? WHERE id = ?");
                $stmt_upd->execute([$total_con_promo, $orden_id]);
                $orden['total'] = $total_con_promo;
            }
            
            if ($orden['total'] <= 0) {
                throw new Exception('No se puede dividir una orden con total $0.00');
            }
            
            // 🔒 CRÍTICO: Guardar el total exacto al momento de iniciar la división
            // Las promociones ya están incluidas en $orden['total'] gracias al paso anterior
            $total_a_pagar = $orden['total'];
            
            // Inicializar división de cuenta
            $stmt = $pdo->prepare("
                UPDATE ordenes 
                SET division_cuenta = 1, 
                    numero_divisiones = ?,
                    estado_division = 'pendiente'
                WHERE id = ?
            ");
            $stmt->execute([$numero_divisiones, $orden_id]);
            
            // Registrar en historial con el total exacto
            $stmt_historial = $pdo->prepare("
                INSERT INTO historial_ordenes (orden_id, accion, detalle, usuario_id) 
                VALUES (?, 'DIVISION_INICIADA', ?, ?)
            ");
            $detalle = "División de cuenta iniciada en {$numero_divisiones} partes. Total a pagar: $" . number_format($total_a_pagar, 2);
            $stmt_historial->execute([$orden_id, $detalle, $usuario_id]);
            
            // Limpiar buffer
            ob_clean();
            
            echo json_encode([
                'success' => true,
                'message' => "División de cuenta iniciada en {$numero_divisiones} partes",
                'total_orden' => $orden['total']
            ]);
            break;
        
        /**
         * Registrar un pago parcial
         * POST: orden_id, numero_pago, monto, metodo_pago, dinero_recibido, cambio
         */
        case 'registrar_pago':
            $orden_id = intval($_POST['orden_id'] ?? 0);
            $numero_pago = intval($_POST['numero_pago'] ?? 1);
            $monto = floatval($_POST['monto'] ?? 0);
            $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
            $dinero_recibido = isset($_POST['dinero_recibido']) ? floatval($_POST['dinero_recibido']) : null;
            $cambio = isset($_POST['cambio']) ? floatval($_POST['cambio']) : null;
            $usuario_id = $_SESSION['user_id'] ?? null;
            
            if ($orden_id <= 0 || $monto <= 0) {
                throw new Exception('Datos de pago inválidos');
            }
            
            // Validar método de pago
            if (!in_array($metodo_pago, ['efectivo', 'debito', 'credito', 'transferencia'])) {
                throw new Exception('Método de pago inválido');
            }
            
            // Obtener información de la orden
            $stmt = $pdo->prepare("
                SELECT id, total, division_cuenta, numero_divisiones, estado_division
                FROM ordenes 
                WHERE id = ? AND estado = 'abierta'
            ");
            $stmt->execute([$orden_id]);
            $orden = $stmt->fetch();
            
            if (!$orden) {
                throw new Exception('Orden no encontrada o ya está cerrada');
            }
            
            if (!$orden['division_cuenta']) {
                throw new Exception('Esta orden no tiene división de cuenta activada');
            }
            
            // Calcular cuánto se ha pagado hasta ahora
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto), 0) as total_pagado FROM pagos_parciales WHERE orden_id = ?");
            $stmt->execute([$orden_id]);
            $total_pagado = $stmt->fetchColumn();
            
            $restante = $orden['total'] - $total_pagado;
            
            // 💰 Validar que el monto no exceda lo restante
            // Aumentamos la tolerancia a $1.00 para manejar redondeos y diferencias mínimas
            if ($monto > $restante + 1.00) {
                throw new Exception("El monto ($" . number_format($monto, 2) . ") excede lo restante por pagar ($" . number_format($restante, 2) . ")");
            }
            
            // Si el monto es muy cercano al restante, ajustar al restante exacto
            if ($monto >= $restante - 1.00 && $monto <= $restante + 1.00) {
                $monto = $restante;
            }
            
            // Registrar el pago parcial
            $stmt = $pdo->prepare("
                INSERT INTO pagos_parciales (
                    orden_id, numero_pago, monto, metodo_pago, 
                    dinero_recibido, cambio, usuario_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orden_id, $numero_pago, $monto, $metodo_pago,
                $dinero_recibido, $cambio, $usuario_id
            ]);
            
            // Recalcular total pagado
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto), 0) as total_pagado FROM pagos_parciales WHERE orden_id = ?");
            $stmt->execute([$orden_id]);
            $total_pagado_nuevo = $stmt->fetchColumn();
            
            // Determinar nuevo estado con tolerancia mejorada
            $nuevo_estado_division = 'parcial';
            $diferencia = abs($total_pagado_nuevo - $orden['total']);
            
            // ✅ Marcar como completada si la diferencia es menor a $1.00
            if ($diferencia < 1.00) {
                $nuevo_estado_division = 'completada';
            }
            
            // Actualizar estado de división
            $stmt = $pdo->prepare("UPDATE ordenes SET estado_division = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado_division, $orden_id]);
            
            // Registrar en historial
            $metodos_nombres = [
                'efectivo' => 'Efectivo',
                'debito' => 'Débito',
                'credito' => 'Crédito',
                'transferencia' => 'Transferencia'
            ];
            $nombre_metodo = $metodos_nombres[$metodo_pago] ?? ucfirst($metodo_pago);
            
            $detalle_historial = "Pago parcial #{$numero_pago} de {$orden['numero_divisiones']}: $" . 
                                number_format($monto, 2) . " ({$nombre_metodo})";
            
            $stmt_historial = $pdo->prepare("
                INSERT INTO historial_ordenes (orden_id, accion, detalle, usuario_id) 
                VALUES (?, 'PAGO_PARCIAL', ?, ?)
            ");
            $stmt_historial->execute([$orden_id, $detalle_historial, $usuario_id]);
            
            // Limpiar buffer
            ob_clean();
            
            echo json_encode([
                'success' => true,
                'message' => "Pago #{$numero_pago} registrado exitosamente",
                'monto_pagado' => $monto,
                'total_pagado' => $total_pagado_nuevo,
                'restante' => $orden['total'] - $total_pagado_nuevo,
                'estado_division' => $nuevo_estado_division,
                'completada' => $nuevo_estado_division === 'completada'
            ]);
            break;
        
        /**
         * Obtener estado de pagos de una orden
         * GET: orden_id
         */
        case 'estado_pagos':
            $orden_id = intval($_GET['orden_id'] ?? 0);
            
            if ($orden_id <= 0) {
                throw new Exception('ID de orden inválido');
            }
            
            // Obtener información de la orden
            $stmt = $pdo->prepare("
                SELECT id, total, division_cuenta, numero_divisiones, estado_division
                FROM ordenes 
                WHERE id = ?
            ");
            $stmt->execute([$orden_id]);
            $orden = $stmt->fetch();
            
            if (!$orden) {
                throw new Exception('Orden no encontrada');
            }
            
            // Obtener pagos realizados
            $stmt = $pdo->prepare("
                SELECT pp.*, u.nombre_completo as usuario_nombre
                FROM pagos_parciales pp
                LEFT JOIN usuarios u ON pp.usuario_id = u.id
                WHERE pp.orden_id = ?
                ORDER BY pp.numero_pago ASC
            ");
            $stmt->execute([$orden_id]);
            $pagos = $stmt->fetchAll();
            
            // Calcular total pagado
            $total_pagado = 0;
            foreach ($pagos as $pago) {
                $total_pagado += $pago['monto'];
            }
            
            $restante = $orden['total'] - $total_pagado;
            
            // Limpiar buffer
            ob_clean();
            
            echo json_encode([
                'success' => true,
                'orden' => $orden,
                'pagos' => $pagos,
                'total_pagado' => $total_pagado,
                'restante' => max(0, $restante),
                'proximo_numero' => count($pagos) + 1
            ]);
            break;
        
        /**
         * Cancelar división de cuenta (volver a pago único)
         * POST: orden_id
         */
        case 'cancelar_division':
            $orden_id = intval($_POST['orden_id'] ?? 0);
            
            if ($orden_id <= 0) {
                throw new Exception('ID de orden inválido');
            }
            
            $pdo->beginTransaction();
            
            try {
                // Verificar que no haya pagos ya realizados
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pagos_parciales WHERE orden_id = ?");
                $stmt->execute([$orden_id]);
                $tiene_pagos = $stmt->fetchColumn() > 0;
                
                if ($tiene_pagos) {
                    throw new Exception('No se puede cancelar la división porque ya hay pagos registrados');
                }
                
                // Restablecer estado de división
                $stmt = $pdo->prepare("
                    UPDATE ordenes 
                    SET division_cuenta = 0,
                        numero_divisiones = 1,
                        estado_division = 'sin_division'
                    WHERE id = ?
                ");
                $stmt->execute([$orden_id]);
                
                $pdo->commit();
                
                // Limpiar buffer
                ob_clean();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'División de cuenta cancelada'
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
        
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    // Limpiar buffer
    ob_clean();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Forzar envío del buffer
ob_end_flush();

/**
 * Calcula el total final de una orden aplicando promociones activas y descuentos.
 * Replica la misma lógica de orden_actual.php para mantener consistencia.
 */
function calcularTotalFinalOrden($pdo, $orden_id) {
    try {
        // Obtener configuración de la orden y su mesa
        $stmt = $pdo->prepare("
            SELECT o.aplicar_descuento_porcentaje, o.descuento_porcentaje_valor,
                   COALESCE(o.es_personal, 0) as es_personal,
                   COALESCE(m.aplicar_promociones, 1) as aplicar_promociones,
                   COALESCE(m.es_para_llevar, 0) as es_para_llevar
            FROM ordenes o
            LEFT JOIN mesas m ON o.mesa_id = m.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orden_id]);
        $info = $stmt->fetch();
        if (!$info) return null;

        // Obtener productos activos (no eliminados)
        $stmt = $pdo->prepare("
            SELECT op.id as orden_producto_id, op.producto_id,
                   (op.cantidad - COALESCE(op.cancelado, 0) - COALESCE(op.pendiente_cancelacion, 0)) as cantidad_activa,
                   p.precio, p.nombre, p.categoria,
                   COALESCE(op.confirmado, 1) as confirmado
            FROM orden_productos op
            JOIN productos p ON op.producto_id = p.id
            WHERE op.orden_id = ? AND op.estado != 'eliminado'
        ");
        $stmt->execute([$orden_id]);
        $productos = $stmt->fetchAll();

        $subtotal = 0;
        $productosExpandidos = [];
        foreach ($productos as $prod) {
            $cant_activa = intval($prod['cantidad_activa']);
            if ($cant_activa <= 0) continue;
            $subtotal += $cant_activa * floatval($prod['precio']);
            if ($prod['confirmado'] == 1) {
                for ($i = 0; $i < $cant_activa; $i++) {
                    $productosExpandidos[] = [
                        'orden_producto_id' => $prod['orden_producto_id'],
                        'producto_id'       => $prod['producto_id'],
                        'precio'            => floatval($prod['precio']),
                        'nombre'            => $prod['nombre'],
                        'categoria'         => $prod['categoria'],
                    ];
                }
            }
        }

        $total_descuentos_promociones = 0;
        $aplicar_promos = $info['aplicar_promociones'] && !$info['es_para_llevar'];

        if ($aplicar_promos && !empty($productosExpandidos)) {
            $stmtPromos = $pdo->query("
                SELECT * FROM promociones
                WHERE activa = 1
                  AND (fecha_inicio IS NULL OR fecha_inicio <= NOW())
                  AND (fecha_fin IS NULL OR fecha_fin >= NOW())
                ORDER BY prioridad DESC, id DESC
            ");
            $promociones_activas = $stmtPromos->fetchAll();

            $productos_usados_ids = [];
            $es_personal = (bool)$info['es_personal'];

            foreach ($promociones_activas as $promo) {
                if ($promo['tipo'] === 'descuento_personal' && !$es_personal) continue;

                // Filtrar elegibles
                $elegibles = [];
                foreach ($productosExpandidos as $prod) {
                    if (in_array($prod['orden_producto_id'], $productos_usados_ids)) continue;
                    if ($promo['aplica_a'] === 'todos') {
                        $elegibles[] = $prod;
                    } elseif ($promo['aplica_a'] === 'productos') {
                        $s2 = $pdo->prepare("SELECT 1 FROM promocion_productos WHERE promocion_id = ? AND producto_id = ?");
                        $s2->execute([$promo['id'], $prod['producto_id']]);
                        if ($s2->fetchColumn()) $elegibles[] = $prod;
                    } elseif ($promo['aplica_a'] === 'categorias') {
                        $s2 = $pdo->prepare("SELECT 1 FROM promocion_categorias WHERE promocion_id = ? AND categoria = ?");
                        $s2->execute([$promo['id'], $prod['categoria']]);
                        if ($s2->fetchColumn()) $elegibles[] = $prod;
                    }
                }

                if (count($elegibles) < $promo['minimo_productos']) continue;

                // Ordenar ascendente por precio (para 2x1/3x2 se descuenta el más barato)
                usort($elegibles, fn($a, $b) => $a['precio'] <=> $b['precio']);

                $monto_descuento = 0;
                $afectados = [];

                switch ($promo['tipo']) {
                    case '2x1':
                        $grupos = floor(count($elegibles) / 2);
                        for ($i = 0; $i < $grupos; $i++) {
                            $monto_descuento += $elegibles[$i]['precio'];
                        }
                        $afectados = array_column($elegibles, 'orden_producto_id');
                        break;
                    case '3x2':
                        $grupos = floor(count($elegibles) / 3);
                        for ($i = 0; $i < $grupos; $i++) {
                            $monto_descuento += $elegibles[$i]['precio'];
                        }
                        $afectados = array_column($elegibles, 'orden_producto_id');
                        break;
                    case 'descuento_porcentaje':
                    case 'descuento_personal':
                        $pct = floatval($promo['valor']);
                        foreach ($elegibles as $prod) {
                            $monto_descuento += $prod['precio'] * $pct / 100;
                            $afectados[] = $prod['orden_producto_id'];
                        }
                        break;
                    case 'descuento_fijo':
                        $monto_descuento = floatval($promo['valor']);
                        $afectados = array_column($elegibles, 'orden_producto_id');
                        break;
                }

                if ($monto_descuento > 0) {
                    $total_descuentos_promociones += $monto_descuento;
                    foreach (array_unique($afectados) as $id) {
                        if (!in_array($id, $productos_usados_ids)) {
                            $productos_usados_ids[] = $id;
                        }
                    }
                }
            }
        }

        // Descuento porcentaje manual
        $descuento_pct = 0;
        if (!empty($info['aplicar_descuento_porcentaje']) && !empty($info['descuento_porcentaje_valor'])) {
            $descuento_pct = $subtotal * floatval($info['descuento_porcentaje_valor']) / 100;
        }

        return max(0, round($subtotal - $total_descuentos_promociones - $descuento_pct, 2));

    } catch (Exception $e) {
        error_log("Error en calcularTotalFinalOrden (orden {$orden_id}): " . $e->getMessage());
        return null;
    }
}
