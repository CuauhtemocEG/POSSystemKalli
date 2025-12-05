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
    // Cargar todos los layouts guardados
    $stmt = $pdo->prepare("
        SELECT 
            elemento_id,
            elemento_tipo,
            mesa_id,
            pos_x,
            pos_y,
            width,
            height,
            rotation,
            tipo_visual
        FROM mesa_layouts
        ORDER BY elemento_tipo DESC, elemento_id ASC
    ");
    
    $stmt->execute();
    $layouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'layouts' => $layouts
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
