<?php
header('Content-Type: application/json');
require_once '../conexion.php';
$pdo = conexion();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$codigo = trim($_GET['codigo'] ?? '');

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT o.id, o.codigo, o.estado, o.creada_en, m.nombre AS mesa
        FROM ordenes o
        JOIN mesas m ON m.id = o.mesa_id
        WHERE o.id = ?");
    $stmt->execute([$id]);
} elseif ($codigo !== '') {
    $stmt = $pdo->prepare("SELECT o.id, o.codigo, o.estado, o.creada_en, m.nombre AS mesa
        FROM ordenes o
        JOIN mesas m ON m.id = o.mesa_id
        WHERE o.codigo = ?");
    $stmt->execute([$codigo]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Se requiere id o codigo']);
    exit;
}

$orden = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    http_response_code(404);
    echo json_encode(['error' => 'No encontrada']);
    exit;
}

$productos = $pdo->prepare("SELECT p.nombre, op.cantidad, op.preparado, op.cancelado, p.precio
    FROM orden_productos op
    JOIN productos p ON op.producto_id = p.id
    WHERE op.orden_id = ?");
$productos->execute([$orden['id']]);
$orden['productos'] = $productos->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($orden['productos'] as $prod) {
    $subtotal += $prod['precio'] * $prod['cantidad'];
}
$orden['subtotal'] = $subtotal;
$orden['descuento'] = 0;
$orden['impuestos'] = 0;
$orden['total'] = $subtotal;

echo json_encode($orden);