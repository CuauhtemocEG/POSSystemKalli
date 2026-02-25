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
    $es_personal = isset($_POST['es_personal']) ? intval($_POST['es_personal']) : 0;
    
    if (!$orden_id) {
        throw new Exception('ID de orden requerido');
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
        SET es_personal = ?
        WHERE id = ?
    ");
    
    $update->execute([$es_personal, $orden_id]);
    
    if (!$update) {
        throw new Exception('Error al actualizar la orden');
    }
    
    // Registrar en historial
    $accion = $es_personal ? 'activó' : 'desactivó';
    $detalle = "Usuario {$accion} descuento personal para la orden";
    
    $stmt_historial = $pdo->prepare("
        INSERT INTO historial_ordenes (orden_id, accion, detalle, usuario_id) 
        VALUES (?, 'MODIFICAR_ES_PERSONAL', ?, ?)
    ");
    $stmt_historial->execute([$orden_id, $detalle, $_SESSION['user_id']]);
    
    echo json_encode([
        'status' => 'ok',
        'message' => "Descuento personal {$accion} correctamente",
        'es_personal' => (bool)$es_personal,
        'orden_id' => $orden_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
