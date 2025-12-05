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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }

    $nombre = trim($input['nombre'] ?? '');
    $posicion_x = intval($input['posicion_x'] ?? 0);
    $posicion_y = intval($input['posicion_y'] ?? 0);
    $ancho = intval($input['ancho'] ?? 80);
    $alto = intval($input['alto'] ?? 80);
    $rotacion = intval($input['rotacion'] ?? 0);
    $tipo_visual = $input['tipo_visual'] ?? 'square';

    if (empty($nombre)) {
        throw new Exception('El nombre de la mesa es requerido');
    }

    // Verificar que no exista una mesa con ese nombre
    $stmt = $pdo->prepare("SELECT id FROM mesas WHERE nombre = ?");
    $stmt->execute([$nombre]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe una mesa con ese nombre');
    }

    // Crear tabla mesa_layouts si no existe (fuera de transacción)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mesa_layouts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            mesa_id INT UNIQUE,
            posicion_x INT DEFAULT 0,
            posicion_y INT DEFAULT 0,
            ancho INT DEFAULT 80,
            alto INT DEFAULT 80,
            rotacion INT DEFAULT 0,
            tipo_visual VARCHAR(20) DEFAULT 'square',
            FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE
        )
    ");

    // Iniciar transacción después de crear tabla
    $pdo->beginTransaction();

    // Insertar la mesa
    $stmt = $pdo->prepare("
        INSERT INTO mesas (nombre, estado) 
        VALUES (?, 'disponible')
    ");
    $stmt->execute([$nombre]);
    
    $mesa_id = $pdo->lastInsertId();

    // Insertar posición en layout
    $stmt = $pdo->prepare("
        INSERT INTO mesa_layouts (mesa_id, posicion_x, posicion_y, ancho, alto, rotacion, tipo_visual) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$mesa_id, $posicion_x, $posicion_y, $ancho, $alto, $rotacion, $tipo_visual]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'mesa_id' => $mesa_id,
        'nombre' => $nombre,
        'message' => 'Mesa creada exitosamente'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
