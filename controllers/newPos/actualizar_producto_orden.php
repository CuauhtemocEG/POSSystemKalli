<?php
require_once '../../conexion.php';
$pdo = conexion();

$producto_id = intval($_POST['producto_id'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 1);
$orden_id = intval($_POST['orden_id'] ?? 0);
$orden_producto_id = intval($_POST['orden_producto_id'] ?? 0); // ID del registro en orden_productos

if ((!$producto_id && !$orden_producto_id) || !$orden_id) {
    echo json_encode(['status'=>'error', 'msg'=>'Datos incompletos']);
    exit;
}

// Función para actualizar el total de la orden
function actualizarTotalOrden($pdo, $orden_id) {
    // 🔒 PROTECCIÓN: Verificar si la orden tiene división de cuenta con pagos
    $stmt_check = $pdo->prepare("SELECT division_cuenta, estado_division FROM ordenes WHERE id = ?");
    $stmt_check->execute([$orden_id]);
    $orden_info = $stmt_check->fetch();
    
    if ($orden_info && $orden_info['division_cuenta'] == 1) {
        // Verificar si ya hay pagos registrados
        $stmt_pagos = $pdo->prepare("SELECT COUNT(*) FROM pagos_parciales WHERE orden_id = ?");
        $stmt_pagos->execute([$orden_id]);
        $tiene_pagos = $stmt_pagos->fetchColumn() > 0;
        
        if ($tiene_pagos) {
            // ⚠️ NO actualizar el total si ya hay pagos parciales registrados
            // Esto previene desincronización entre pagos y total
            $stmt_total = $pdo->prepare("SELECT total FROM ordenes WHERE id = ?");
            $stmt_total->execute([$orden_id]);
            return $stmt_total->fetchColumn();
        }
    }
    
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
        // Si viene orden_producto_id, usar ese ID específico (más preciso)
        if ($orden_producto_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM orden_productos WHERE id = ? AND orden_id = ?");
            $stmt->execute([$orden_producto_id, $orden_id]);
        } else {
            // Fallback: usar producto_id (modo legacy)
            $stmt = $pdo->prepare("DELETE FROM orden_productos WHERE orden_id=? AND producto_id=?");
            $stmt->execute([$orden_id, $producto_id]);
        }
    } else {
        // Actualizar cantidad
        // Priorizar orden_producto_id si está disponible
        if ($orden_producto_id > 0) {
            $stmt = $pdo->prepare("UPDATE orden_productos SET cantidad=? WHERE id=? AND orden_id=?");
            $stmt->execute([$cantidad, $orden_producto_id, $orden_id]);
        } else {
            // Fallback: usar producto_id
            $stmt = $pdo->prepare("UPDATE orden_productos SET cantidad=? WHERE orden_id=? AND producto_id=?");
            $stmt->execute([$cantidad, $orden_id, $producto_id]);
        }
    }

    // Actualizar el total de la orden
    $total = actualizarTotalOrden($pdo, $orden_id);

    echo json_encode(['status'=>'ok', 'success' => true, 'total' => $total]);
    
} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'msg'=>'Error al actualizar producto: ' . $e->getMessage()]);
}
?>