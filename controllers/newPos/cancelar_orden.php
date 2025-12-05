<?php
require_once '../../conexion.php';
$pdo = conexion();

$orden_id = intval($_POST['orden_id'] ?? 0);
if (!$orden_id) {
    echo json_encode(['status'=>'error', 'msg'=>'Orden no válida']);
    exit;
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Obtener la mesa de la orden
    $mesa_query = $pdo->prepare("SELECT mesa_id FROM ordenes WHERE id = ?");
    $mesa_query->execute([$orden_id]);
    $mesa_id = $mesa_query->fetchColumn();
    
    // Elimina productos de la orden
    $pdo->prepare("DELETE FROM orden_productos WHERE orden_id=?")->execute([$orden_id]);
    
    // Marcar la orden como cancelada
    $pdo->prepare("UPDATE ordenes SET estado='cancelada', total = 0 WHERE id=?")->execute([$orden_id]);
    
    // Liberar la mesa
    if ($mesa_id) {
        $pdo->prepare("UPDATE mesas SET estado='disponible' WHERE id=?")->execute([$mesa_id]);
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    echo json_encode(['status'=>'ok', 'msg'=>'Orden cancelada exitosamente']);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $pdo->rollBack();
    echo json_encode(['status'=>'error', 'msg'=>'Error al cancelar orden: ' . $e->getMessage()]);
}
?>