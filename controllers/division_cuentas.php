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
            
            if ($orden_id <= 0) {
                throw new Exception('ID de orden inválido');
            }
            
            if ($numero_divisiones < 2) {
                throw new Exception('Debe dividir la cuenta en al menos 2 partes');
            }
            
            // Verificar que la orden existe y está abierta
            $stmt = $pdo->prepare("SELECT id, total FROM ordenes WHERE id = ? AND estado = 'abierta'");
            $stmt->execute([$orden_id]);
            $orden = $stmt->fetch();
            
            if (!$orden) {
                throw new Exception('Orden no encontrada o ya está cerrada');
            }
            
            // Inicializar división de cuenta
            $stmt = $pdo->prepare("
                UPDATE ordenes 
                SET division_cuenta = 1, 
                    numero_divisiones = ?,
                    estado_division = 'pendiente'
                WHERE id = ?
            ");
            $stmt->execute([$numero_divisiones, $orden_id]);
            
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
            
            // Validar que el monto no exceda lo restante
            if ($monto > $restante + 0.01) { // +0.01 para tolerar diferencias de redondeo
                throw new Exception("El monto excede lo restante por pagar ($" . number_format($restante, 2) . ")");
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
            
            // Determinar nuevo estado
            $nuevo_estado_division = 'parcial';
            if (abs($total_pagado_nuevo - $orden['total']) < 0.01) {
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
