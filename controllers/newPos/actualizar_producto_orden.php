<?php
require_once '../../conexion.php';
$pdo = conexion();

$producto_id = intval($_POST['producto_id'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 1);
$orden_id = intval($_POST['orden_id'] ?? 0);

if (!$producto_id || !$orden_id) {
    echo json_encode(['status'=>'error', 'msg'=>'Datos incompletos']);
    exit;
}

// Función para actualizar el total de la orden
function actualizarTotalOrden($pdo, $orden_id) {
    try {
        // Intentar con campo cancelado
        $total_query = $pdo->prepare("
            SELECT SUM(op.cantidad * p.precio) as total
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            WHERE op.orden_id = ? AND op.cancelado = 0
        ");
        $total_query->execute([$orden_id]);
        $total = $total_query->fetchColumn() ?? 0;
    } catch (Exception $e) {
        // Sin campo cancelado
        $total_query = $pdo->prepare("
            SELECT SUM(op.cantidad * p.precio) as total
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            WHERE op.orden_id = ?
        ");
        $total_query->execute([$orden_id]);
        $total = $total_query->fetchColumn() ?? 0;
    }
    
    $update_orden = $pdo->prepare("UPDATE ordenes SET total = ? WHERE id = ?");
    $update_orden->execute([$total, $orden_id]);
    
    return $total;
}

try {
    if ($cantidad <= 0) {
        // Eliminar producto de la orden
        $stmt = $pdo->prepare("DELETE FROM orden_productos WHERE orden_id=? AND producto_id=?");
        $stmt->execute([$orden_id, $producto_id]);
    } else {
        // Actualizar cantidad
        $stmt = $pdo->prepare("UPDATE orden_productos SET cantidad=? WHERE orden_id=? AND producto_id=?");
        $stmt->execute([$cantidad, $orden_id, $producto_id]);
    }

    // Actualizar el total de la orden
    $total = actualizarTotalOrden($pdo, $orden_id);

    echo json_encode(['status'=>'ok', 'success' => true, 'total' => $total]);
    
} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'msg'=>'Error al actualizar producto: ' . $e->getMessage()]);
}
?>