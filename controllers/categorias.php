<?php
// Headers anti-caché
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json; charset=utf-8');

require_once '../conexion.php';
$pdo = conexion();
$stmt = $pdo->query("SELECT id, nombre FROM type ORDER BY nombre");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>