<?php
require_once '../../conexion.php';
header('Content-Type: application/json');

$pdo = conexion();

$search = $_GET['search'] ?? '';

try {
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT id, nombre, precio, categoria, imagen 
            FROM productos 
            WHERE nombre LIKE ? 
            ORDER BY nombre ASC
            LIMIT 200
        ");
        $stmt->execute(['%' . $search . '%']);
    } else {
        $stmt = $pdo->query("
            SELECT id, nombre, precio, categoria, imagen 
            FROM productos 
            ORDER BY nombre ASC
            LIMIT 200
        ");
    }
    
    $productos = $stmt->fetchAll();
    
    echo json_encode([
        'status' => 'ok',
        'data' => $productos
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
