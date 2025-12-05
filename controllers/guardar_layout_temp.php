<?php
// Controlador temporal SIN auth-check para debug
require_once __DIR__ . '/../conexion.php';

// Configurar respuesta JSON y headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log de debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Crear conexión
    $pdo = conexion();
    
    // Log de debugging
    error_log("TEMP Guardar layout - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("TEMP Guardar layout - Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'no set'));
    error_log("TEMP Guardar layout - POST data: " . print_r($_POST, true));
    
    // Verificar si es una actualización individual o múltiple
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Guardar múltiples mesas (layout completo)
        $input = json_decode(file_get_contents('php://input'), true);
        
        error_log("TEMP JSON recibido: " . print_r($input, true));
        
        if (!$input || !isset($input['layouts']) || !is_array($input['layouts'])) {
            throw new Exception('Datos de layouts inválidos');
        }
        
        $pdo->beginTransaction();
        $count = 0;
        
        foreach ($input['layouts'] as $layout) {
            $mesa_id = intval($layout['mesa_id'] ?? 0);
            $pos_x = intval($layout['pos_x'] ?? 0);
            $pos_y = intval($layout['pos_y'] ?? 0);
            $width = intval($layout['width'] ?? 120);
            $height = intval($layout['height'] ?? 80);
            $rotation = intval($layout['rotation'] ?? 0);
            $tipo_visual = $layout['tipo_visual'] ?? 'rectangular';
            
            if ($mesa_id <= 0) continue;
            
            // Verificar que la mesa existe
            $stmt = $pdo->prepare("SELECT id FROM mesas WHERE id = ?");
            $stmt->execute([$mesa_id]);
            if (!$stmt->fetch()) continue;
            
            // Insertar o actualizar layout
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
            
            $stmt->execute([$mesa_id, $pos_x, $pos_y, $width, $height, $rotation, $tipo_visual]);
            $count++;
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Layout completo guardado correctamente ($count mesas)"]);
        
    } else {
        // Actualización individual (POST form data)
        $mesa_id = intval($_POST['mesa_id'] ?? 0);
        $pos_x = intval($_POST['pos_x'] ?? 0);
        $pos_y = intval($_POST['pos_y'] ?? 0);
        $width = intval($_POST['width'] ?? 120);
        $height = intval($_POST['height'] ?? 80);
        $rotation = intval($_POST['rotation'] ?? 0);
        $tipo_visual = $_POST['tipo_visual'] ?? 'rectangular';
        
        if ($mesa_id <= 0) {
            throw new Exception('ID de mesa inválido');
        }
        
        // Para test de conexión, permitir mesa_id = 999
        if ($mesa_id === 999) {
            echo json_encode([
                'success' => true, 
                'message' => 'Test de conexión exitoso - Controlador temporal funcionando',
                'data' => [
                    'mesa_id' => $mesa_id,
                    'pos_x' => $pos_x,
                    'pos_y' => $pos_y,
                    'width' => $width,
                    'height' => $height,
                    'rotation' => $rotation,
                    'tipo_visual' => $tipo_visual
                ]
            ]);
            exit;
        }
        
        // Verificar que la mesa existe
        $stmt = $pdo->prepare("SELECT id FROM mesas WHERE id = ?");
        $stmt->execute([$mesa_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Mesa no encontrada');
        }
        
        // Insertar o actualizar layout individual
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
        
        $result = $stmt->execute([$mesa_id, $pos_x, $pos_y, $width, $height, $rotation, $tipo_visual]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Posición actualizada correctamente (controlador temporal)',
                'data' => [
                    'mesa_id' => $mesa_id,
                    'pos_x' => $pos_x,
                    'pos_y' => $pos_y,
                    'width' => $width,
                    'height' => $height,
                    'rotation' => $rotation,
                    'tipo_visual' => $tipo_visual
                ]
            ]);
        } else {
            throw new Exception('Error al ejecutar la consulta');
        }
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("TEMP Error en guardar_layout: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
