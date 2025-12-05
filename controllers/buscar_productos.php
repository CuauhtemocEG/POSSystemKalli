<?php
// Headers anti-caché
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json; charset=utf-8');

require_once '../conexion.php';
$pdo = conexion();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

$sql = "SELECT * FROM productos WHERE 1";
$params = [];

if ($cat_id) {
    $sql .= " AND type = ?";
    $params[] = $cat_id;
}
if ($q !== '') {
    $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($cat_id == 0) {
    $sql .= " ORDER BY nombre";
} else {
    $sql .= " ORDER BY nombre LIMIT 20";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>