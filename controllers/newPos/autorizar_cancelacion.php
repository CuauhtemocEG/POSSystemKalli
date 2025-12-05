<?php
// Limpiar cualquier output previo
ob_clean();

require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../auth-check.php';

// Establecer header JSON inmediatamente
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$pdo = conexion();

// Obtener información del usuario actual
$userInfo = getUserInfo();
$admin_user_id = $userInfo['id'] ?? 1;

$codigo_pin = trim($_POST['pin'] ?? $_POST['codigo_pin'] ?? '');

if (!$codigo_pin) {
    echo json_encode(['success' => false, 'message' => 'Código PIN requerido']);
    exit;
}

try {
    // DEBUG: Log del código recibido
    error_log("DEBUG Autorización: PIN recibido = $codigo_pin, Usuario = $admin_user_id");
    
    // Buscar el código PIN válido
    $stmt = $pdo->prepare("
        SELECT id, orden_id, producto_id, cantidad_solicitada, solicitado_por, razon, usado, fecha_expiracion
        FROM codigos_cancelacion 
        WHERE codigo = ? AND usado = 0 AND fecha_expiracion > NOW()
    ");
    $stmt->execute([$codigo_pin]);
    $cancelacion = $stmt->fetch();
    
    if (!$cancelacion) {
        error_log("DEBUG Autorización: PIN no válido o expirado");
        echo json_encode(['success' => false, 'message' => 'Código PIN inválido, usado o expirado']);
        exit;
    }
    
    error_log("DEBUG Autorización: PIN válido encontrado, ID = " . $cancelacion['id']);
    
    // Obtener información del producto
    $stmt = $pdo->prepare("
        SELECT op.id, op.cantidad, op.preparado, op.cancelado, op.pendiente_cancelacion, p.nombre
        FROM orden_productos op
        JOIN productos p ON op.producto_id = p.id
        WHERE op.orden_id = ? AND op.producto_id = ? AND COALESCE(op.cancelado, 0) = 0
        ORDER BY op.id DESC
        LIMIT 1
    ");
    $stmt->execute([$cancelacion['orden_id'], $cancelacion['producto_id']]);
    $producto_orden = $stmt->fetch();
    
    if (!$producto_orden) {
        error_log("DEBUG Autorización: Producto no encontrado en orden");
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado en la orden o ya está cancelado']);
        exit;
    }
    
    error_log("DEBUG Autorización: Producto encontrado, cantidad actual = " . $producto_orden['cantidad'] . ", pendiente_cancelacion = " . $producto_orden['pendiente_cancelacion']);
    
    $pdo->beginTransaction();
    
    // Marcar el PIN como usado y autorizado
    $stmt = $pdo->prepare("
        UPDATE codigos_cancelacion 
        SET usado = 1, autorizado_por = ?, fecha_autorizacion = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$admin_user_id, $cancelacion['id']]);
    
    // Procesar la cancelación: mover de pendiente_cancelacion a cancelado
    $cantidad_cancelar = min($cancelacion['cantidad_solicitada'], $producto_orden['pendiente_cancelacion']);
    $nuevo_cancelado = $producto_orden['cancelado'] + $cantidad_cancelar;
    $nuevo_pendiente_cancelacion = $producto_orden['pendiente_cancelacion'] - $cantidad_cancelar;
    
    // Actualizar los campos pendiente_cancelacion y cancelado
    $stmt = $pdo->prepare("
        UPDATE orden_productos 
        SET cancelado = ?, pendiente_cancelacion = ?
        WHERE id = ?
    ");
    $stmt->execute([$nuevo_cancelado, $nuevo_pendiente_cancelacion, $producto_orden['id']]);
    error_log("DEBUG Autorización: Actualizado - cancelado: $nuevo_cancelado, pendiente_cancelacion: $nuevo_pendiente_cancelacion");
    
    // Actualizar el total de la orden
    $total_query = $pdo->prepare("
        SELECT SUM(op.cantidad * p.precio) as total
        FROM orden_productos op 
        JOIN productos p ON op.producto_id = p.id 
        WHERE op.orden_id = ? AND COALESCE(op.cancelado, 0) = 0
    ");
    $total_query->execute([$cancelacion['orden_id']]);
    $total = $total_query->fetchColumn() ?? 0;
    
    $update_orden = $pdo->prepare("UPDATE ordenes SET total = ? WHERE id = ?");
    $update_orden->execute([$total, $cancelacion['orden_id']]);
    
    $pdo->commit();
    
    error_log("DEBUG Autorización: Cancelación autorizada exitosamente");
    
    // Obtener información adicional para la respuesta
    $stmt = $pdo->prepare("
        SELECT o.codigo as orden_codigo, m.nombre as mesa_nombre, o.total as nuevo_total,
               u.nombre_completo as solicitante_nombre
        FROM ordenes o
        JOIN mesas m ON o.mesa_id = m.id
        LEFT JOIN usuarios u ON u.id = ?
        WHERE o.id = ?
    ");
    $stmt->execute([$cancelacion['solicitado_por'], $cancelacion['orden_id']]);
    $info_orden = $stmt->fetch();
    
    echo json_encode([
        'success' => true, 
        'message' => "Cancelación autorizada: $cantidad_cancelar unidad(es) de {$producto_orden['nombre']}",
        'data' => [
            'detalles' => [
                'mesa' => $info_orden['mesa_nombre'] ?? 'Mesa desconocida',
                'producto' => $producto_orden['nombre'],
                'cantidad' => $cantidad_cancelar,
                'solicitante' => $info_orden['solicitante_nombre'] ?? 'Usuario #' . $cancelacion['solicitado_por'],
                'razon' => $cancelacion['razon'],
                'orden' => $info_orden['orden_codigo'] ?? "Orden #{$cancelacion['orden_id']}"
            ]
        ],
        'nuevo_total' => number_format($total, 2)
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log detallado del error
    $error_details = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'pin' => $codigo_pin ?? 'N/A',
        'user_id' => $admin_user_id ?? 'N/A'
    ];
    
    error_log("ERROR AUTORIZACIÓN CANCELACIÓN: " . json_encode($error_details));
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor',
        'debug' => [
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
