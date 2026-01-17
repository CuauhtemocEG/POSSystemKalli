<?php
require_once '../../conexion.php';
header('Content-Type: application/json');

$pdo = conexion();

try {
    // Obtener todas las categorías únicas de productos
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM productos ORDER BY categoria");
    $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'status' => 'ok',
        'data' => $categorias
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
