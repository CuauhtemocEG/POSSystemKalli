<?php
header('Content-Type: application/json');
require_once '../conexion.php';
$pdo = conexion();

$filtro = $_GET['filtro'] ?? '';
$buscar = $_GET['buscar'] ?? '';

$where = "1";
$params = [];
if ($filtro === 'abiertas') { $where .= " AND o.estado='abierta'"; }
if ($filtro === 'pagadas') { $where .= " AND o.estado='pagada'"; }
if ($filtro === 'canceladas') { $where .= " AND o.estado='cancelada'"; }
if ($buscar) {
    $where .= " AND (o.codigo LIKE ? OR m.nombre LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$stmt = $pdo->prepare("SELECT o.id, o.codigo, o.estado, o.creada_en, m.nombre AS mesa FROM ordenes o JOIN mesas m ON m.id=o.mesa_id WHERE $where ORDER BY o.creada_en DESC LIMIT 100");
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));