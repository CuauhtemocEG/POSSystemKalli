<?php
require_once '../conexion.php';
$pdo = conexion();

// Obtener productos normales (disponibles para preparar)
$detalles = $pdo->query(
    "SELECT m.nombre AS mesa, m.id AS mesa_id, op.id AS op_id, p.id AS producto_id, p.nombre AS producto, 
            o.id AS orden_id, COALESCE(op.item_index, 1) as item_index,
            COALESCE(op.cantidad, 0) as cantidad, 
            COALESCE(op.preparado, 0) as preparado, 
            COALESCE(op.cancelado, 0) as cancelado, 
            COALESCE(op.pendiente_cancelacion, 0) as pendiente_cancelacion,
            COALESCE(op.nota_adicional, '') as nota_adicional,
            (COALESCE(op.cantidad, 0) - COALESCE(op.preparado, 0) - COALESCE(op.cancelado, 0) - COALESCE(op.pendiente_cancelacion, 0)) AS faltan,
            'normal' as tipo
     FROM orden_productos op
     JOIN ordenes o ON op.orden_id = o.id
     JOIN mesas m ON o.mesa_id = m.id
     JOIN productos p ON op.producto_id = p.id
     WHERE p.categoria = 'bebidas' AND op.estado != 'eliminado'
     AND (COALESCE(op.cantidad, 0) - COALESCE(op.preparado, 0) - COALESCE(op.cancelado, 0) - COALESCE(op.pendiente_cancelacion, 0)) > 0
     AND o.estado='abierta'
     
     UNION ALL
     
     SELECT m.nombre AS mesa, m.id AS mesa_id, op.id AS op_id, p.id AS producto_id, p.nombre AS producto,
            o.id AS orden_id, COALESCE(op.item_index, 1) as item_index,
            COALESCE(op.cantidad, 0) as cantidad, 
            COALESCE(op.preparado, 0) as preparado, 
            COALESCE(op.cancelado, 0) as cancelado, 
            COALESCE(op.pendiente_cancelacion, 0) as pendiente_cancelacion,
            COALESCE(op.nota_adicional, '') as nota_adicional,
            COALESCE(op.pendiente_cancelacion, 0) AS faltan,
            'pendiente_cancelacion' as tipo
     FROM orden_productos op
     JOIN ordenes o ON op.orden_id = o.id
     JOIN mesas m ON o.mesa_id = m.id
     JOIN productos p ON op.producto_id = p.id
     WHERE p.categoria = 'bebidas' AND op.estado != 'eliminado'
     AND COALESCE(op.pendiente_cancelacion, 0) > 0
     AND o.estado='abierta'
     
     ORDER BY mesa, tipo DESC, producto"
)->fetchAll(PDO::FETCH_ASSOC);

// Agregar variedades a cada producto usando item_index
foreach ($detalles as &$item) {
    $stmtVariedades = $pdo->prepare("
        SELECT grupo_nombre, opcion_nombre, precio_adicional
        FROM orden_producto_variedades
        WHERE orden_id = ? AND producto_id = ? AND item_index = ?
        ORDER BY id
    ");
    $stmtVariedades->execute([$item['orden_id'], $item['producto_id'], $item['item_index']]);
    $item['variedades'] = $stmtVariedades->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode($detalles);
?>