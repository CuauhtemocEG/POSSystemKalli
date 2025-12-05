<?php
require_once '../auth-check.php';
require_once '../conexion.php';

header('Content-Type: application/json');

try {
    $pdo = conexion();
    $mesa_id = intval($_GET['mesa_id'] ?? 0);

    if ($mesa_id <= 0) {
        throw new Exception('ID de mesa inválido');
    }

    // Verificar si hay órdenes abiertas para esta mesa
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ordenes WHERE mesa_id = ? AND estado = 'abierta'");
    $stmt->execute([$mesa_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $orden_abierta = $result['count'] > 0;

    echo json_encode([
        'success' => true,
        'orden_abierta' => $orden_abierta
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
