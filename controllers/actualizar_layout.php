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
    // Crear tabla mesa_layouts si no existe con la nueva estructura
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mesa_layouts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            mesa_id INT UNIQUE,
            pos_x INT DEFAULT 0,
            pos_y INT DEFAULT 0,
            width INT DEFAULT 120,
            height INT DEFAULT 100,
            rotation FLOAT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (mesa_id) REFERENCES mesas(id) ON DELETE CASCADE
        )
    ");
    
    // Verificar si es una actualización individual o masiva
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Guardar layout completo (JSON)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['action']) || $input['action'] !== 'guardar_todo') {
            throw new Exception('Acción JSON inválida');
        }
        
        if (!isset($input['layouts']) || !is_array($input['layouts'])) {
            throw new Exception('Datos de layouts inválidos');
        }
        
        $pdo->beginTransaction();
        
        foreach ($input['layouts'] as $layout) {
            $mesa_id = intval($layout['mesa_id'] ?? 0);
            $pos_x = intval($layout['pos_x'] ?? 0);
            $pos_y = intval($layout['pos_y'] ?? 0);
            $width = intval($layout['width'] ?? 120);
            $height = intval($layout['height'] ?? 100);
            $rotation = floatval($layout['rotation'] ?? 0);
            
            if ($mesa_id <= 0) continue;
            
            // Insertar o actualizar layout
            $stmt = $pdo->prepare("
                INSERT INTO mesa_layouts (mesa_id, pos_x, pos_y, width, height, rotation) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                pos_x = VALUES(pos_x), 
                pos_y = VALUES(pos_y), 
                width = VALUES(width), 
                height = VALUES(height), 
                rotation = VALUES(rotation),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([$mesa_id, $pos_x, $pos_y, $width, $height, $rotation]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Layout completo guardado correctamente']);
        
    } else {
        // Actualización individual (POST form data)
        $mesa_id = intval($_POST['mesa_id'] ?? 0);
        $pos_x = intval($_POST['pos_x'] ?? 0);
        $pos_y = intval($_POST['pos_y'] ?? 0);
        $width = intval($_POST['width'] ?? 120);
        $height = intval($_POST['height'] ?? 100);
        $rotation = floatval($_POST['rotation'] ?? 0);
        
        if ($mesa_id <= 0) {
            throw new Exception('ID de mesa inválido');
        }
        
        // Verificar que la mesa existe
        $stmt = $pdo->prepare("SELECT id FROM mesas WHERE id = ?");
        $stmt->execute([$mesa_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Mesa no encontrada');
        }
        
        // Insertar o actualizar layout individual
        $stmt = $pdo->prepare("
            INSERT INTO mesa_layouts (mesa_id, pos_x, pos_y, width, height, rotation) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            pos_x = VALUES(pos_x), 
            pos_y = VALUES(pos_y), 
            width = VALUES(width), 
            height = VALUES(height), 
            rotation = VALUES(rotation),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([$mesa_id, $pos_x, $pos_y, $width, $height, $rotation]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Posición actualizada correctamente',
            'data' => [
                'mesa_id' => $mesa_id,
                'pos_x' => $pos_x,
                'pos_y' => $pos_y,
                'width' => $width,
                'height' => $height,
                'rotation' => $rotation
            ]
        ]);
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
        $alto = intval($mesa_data['alto'] ?? 80);
        $rotacion = intval($mesa_data['rotacion'] ?? 0);
        $tipo_visual = $mesa_data['tipo_visual'] ?? 'square';

        if ($mesa_id <= 0) continue;

        // Verificar que la mesa existe
        $stmt = $pdo->prepare("SELECT id FROM mesas WHERE id = ?");
        $stmt->execute([$mesa_id]);
        if (!$stmt->fetch()) continue;

        // Actualizar o insertar posición
        $stmt = $pdo->prepare("
            INSERT INTO mesa_layouts (mesa_id, posicion_x, posicion_y, ancho, alto, rotacion, tipo_visual) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                posicion_x = VALUES(posicion_x),
                posicion_y = VALUES(posicion_y),
                ancho = VALUES(ancho),
                alto = VALUES(alto),
                rotacion = VALUES(rotacion),
                tipo_visual = VALUES(tipo_visual)
        ");
        $stmt->execute([$mesa_id, $posicion_x, $posicion_y, $ancho, $alto, $rotacion, $tipo_visual]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Layout actualizado exitosamente']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
