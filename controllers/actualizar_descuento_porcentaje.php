<?php
session_start();
require_once '../conexion.php';

header('Content-Type: application/json');

try {
    // Verificar sesión
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('No autorizado');
    }
    
    $pdo = conexion();
    
    // Obtener datos
    $orden_id = intval($_POST['orden_id'] ?? 0);
    $aplicar = isset($_POST['aplicar']) ? intval($_POST['aplicar']) : 0;
    $porcentaje = isset($_POST['porcentaje']) ? floatval($_POST['porcentaje']) : 0;
    
    if (!$orden_id) {
        throw new Exception('ID de orden requerido');
    }
    
    // Validar porcentaje (0-100)
    if ($porcentaje < 0 || $porcentaje > 100) {
        throw new Exception('Porcentaje debe estar entre 0 y 100');
    }
    
    // Verificar que la orden existe y está abierta
    $stmt = $pdo->prepare("SELECT id, estado FROM ordenes WHERE id = ?");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch();
    
    if (!$orden) {
        throw new Exception('Orden no encontrada');
    }
    
    if ($orden['estado'] !== 'abierta') {
        throw new Exception('Solo se puede modificar órdenes abiertas');
    }
    
    // Actualizar la orden
    $update = $pdo->prepare("
        UPDATE ordenes 
        SET aplicar_descuento_porcentaje = ?,
            descuento_porcentaje_valor = ?
        WHERE id = ?
    ");
    
    $update->execute([$aplicar, $porcentaje, $orden_id]);
    
    // Registrar en historial
    $usuario_id = $_SESSION['user_id'];
    $detalle = $aplicar 
        ? "Descuento manual activado: {$porcentaje}%" 
        : "Descuento manual desactivado";
    
    $stmt_historial = $pdo->prepare("
        INSERT INTO historial_ordenes (orden_id, accion, detalle, usuario_id) 
        VALUES (?, 'DESCUENTO_MANUAL', ?, ?)
    ");
    $stmt_historial->execute([$orden_id, $detalle, $usuario_id]);
    
    echo json_encode([
        'success' => true,
        'message' => $aplicar 
            ? "Descuento de {$porcentaje}% activado correctamente" 
            : 'Descuento desactivado correctamente',
        'aplicar' => $aplicar,
        'porcentaje' => $porcentaje
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
