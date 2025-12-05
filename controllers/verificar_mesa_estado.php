<?php
require_once '../auth-check.php';
require_once '../conexion.php';

// Crear conexión usando la función del archivo de conexión
try {
    $pdo = conexion();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $mesa_id = intval($_GET['mesa_id'] ?? 0);

    if ($mesa_id <= 0) {
        throw new Exception('ID de mesa inválido');
    }

    // Verificar que la mesa existe
    $stmt = $pdo->prepare("SELECT id, nombre, estado FROM mesas WHERE id = ?");
    $stmt->execute([$mesa_id]);
    $mesa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mesa) {
        throw new Exception('Mesa no encontrada');
    }

    // Verificar si hay órdenes abiertas para esta mesa
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as orden_abierta 
        FROM ordenes 
        WHERE mesa_id = ? AND estado = 'abierta'
    ");
    $stmt->execute([$mesa_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $orden_abierta = $result['orden_abierta'] > 0;

    echo json_encode([
        'success' => true,
        'mesa_id' => $mesa_id,
        'mesa_nombre' => $mesa['nombre'],
        'mesa_estado' => $mesa['estado'],
        'orden_abierta' => $orden_abierta,
        'ordenes_activas' => $result['orden_abierta']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
