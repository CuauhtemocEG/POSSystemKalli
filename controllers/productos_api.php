<?php
require_once '../conexion.php';

header('Content-Type: application/json');

$pdo = conexion();

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

switch ($action) {
    case 'get_product':
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
