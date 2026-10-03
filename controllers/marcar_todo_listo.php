<?php
require_once '../auth-check.php';
require_once '../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = conexion();
$userInfo = getUserInfo();
$usuario_id = $userInfo['id'] ?? 1;

$orden_id = intval($_POST['orden_id'] ?? 0);
$categoria = $_POST['categoria'] ?? '';

if ($orden_id <= 0 || !in_array($categoria, ['comidas', 'bebidas', 'desayunos'], true)) {
    echo json_encode(["status" => "error", "msg" => "Datos inválidos"]);
    exit;
}

try {
    // Mismas condiciones que las comanderas: confirmados, activos y sin unidades pendientes de cancelación
    $stmt = $pdo->prepare("
        UPDATE orden_productos op
        JOIN ordenes o ON op.orden_id = o.id
        JOIN productos p ON op.producto_id = p.id
        SET op.preparado = op.cantidad - COALESCE(op.cancelado, 0) - COALESCE(op.pendiente_cancelacion, 0),
            op.preparado_por_usuario_id = ?
        WHERE op.orden_id = ?
          AND o.estado = 'abierta'
          AND p.categoria = ?
          AND op.estado != 'eliminado'
          AND COALESCE(op.confirmado, 1) = 1
          AND (COALESCE(op.cantidad, 0) - COALESCE(op.preparado, 0) - COALESCE(op.cancelado, 0) - COALESCE(op.pendiente_cancelacion, 0)) > 0
    ");
    $stmt->execute([$usuario_id, $orden_id, $categoria]);
    $afectados = $stmt->rowCount();

    if ($afectados === 0) {
        echo json_encode(["status" => "error", "msg" => "No hay productos pendientes para marcar"]);
        exit;
    }

    echo json_encode(["status" => "ok", "msg" => "Se marcaron $afectados productos como listos"]);
} catch (Exception $e) {
    error_log('marcar_todo_listo: ' . $e->getMessage());
    echo json_encode(["status" => "error", "msg" => "Error al marcar los productos"]);
}
