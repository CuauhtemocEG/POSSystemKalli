<?php
require_once '../../auth-check.php';
require_once '../../conexion.php';
$pdo = conexion();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$orden_producto_ids = $_POST['orden_producto_ids'] ?? '';

if (empty($action) || empty($orden_producto_ids)) {
    echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
    exit;
}

// Convertir IDs a array
$ids = is_array($orden_producto_ids) ? $orden_producto_ids : explode(',', $orden_producto_ids);
$ids = array_filter(array_map('intval', $ids));

if (empty($ids)) {
    echo json_encode(['status' => 'error', 'msg' => 'No se proporcionaron IDs válidos']);
    exit;
}

try {
    if ($action === 'confirmar') {
        // Confirmar productos - enviar a cocina/bar
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE orden_productos SET confirmado = 1 WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        // Obtener información de los productos confirmados para notificación
        $stmt = $pdo->prepare("
            SELECT op.id, p.nombre, p.categoria, m.nombre as mesa_nombre, o.id as orden_id
            FROM orden_productos op
            JOIN productos p ON op.producto_id = p.id
            JOIN ordenes o ON op.orden_id = o.id
            JOIN mesas m ON o.mesa_id = m.id
            WHERE op.id IN ($placeholders)
        ");
        $stmt->execute($ids);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'ok', 
            'msg' => 'Productos confirmados y enviados a ' . (count($productos) > 0 && $productos[0]['categoria'] === 'bebidas' ? 'bar' : 'cocina'),
            'productos' => $productos,
            'count' => count($ids)
        ]);
        
    } elseif ($action === 'cancelar') {
        // Cancelar productos sin confirmación - eliminar directamente
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Primero obtener información antes de eliminar
        $stmt = $pdo->prepare("
            SELECT op.id, op.orden_id, op.producto_id, op.cantidad, op.item_index, p.precio
            FROM orden_productos op
            JOIN productos p ON op.producto_id = p.id
            WHERE op.id IN ($placeholders) AND op.confirmado = 0
        ");
        $stmt->execute($ids);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($productos)) {
            echo json_encode(['status' => 'error', 'msg' => 'No se encontraron productos pendientes de confirmación']);
            exit;
        }
        
        // Eliminar productos no confirmados
        $stmt = $pdo->prepare("DELETE FROM orden_productos WHERE id IN ($placeholders) AND confirmado = 0");
        $stmt->execute($ids);
        
        // También eliminar sus variedades asociadas usando orden_id, producto_id e item_index
        foreach ($productos as $prod) {
            $stmt = $pdo->prepare("
                DELETE FROM orden_producto_variedades 
                WHERE orden_id = ? AND producto_id = ? AND item_index = ?
            ");
            $stmt->execute([$prod['orden_id'], $prod['producto_id'], $prod['item_index']]);
        }
        
        // Actualizar total de la orden
        foreach ($productos as $prod) {
            $total_query = $pdo->prepare("
                SELECT SUM(op.cantidad * p.precio) as total
                FROM orden_productos op 
                JOIN productos p ON op.producto_id = p.id 
                WHERE op.orden_id = ? AND op.cancelado = 0
            ");
            $total_query->execute([$prod['orden_id']]);
            $total = $total_query->fetchColumn() ?? 0;
            
            $update_orden = $pdo->prepare("UPDATE ordenes SET total = ? WHERE id = ?");
            $update_orden->execute([$total, $prod['orden_id']]);
        }
        
        echo json_encode([
            'status' => 'ok', 
            'msg' => 'Productos cancelados sin enviar a preparación',
            'count' => count($ids)
        ]);
        
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
}
