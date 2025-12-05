<?php
require_once '../conexion.php';

header('Content-Type: application/json');

try {
    $pdo = conexion();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'eliminar':
            $mesa_id = intval($_POST['mesa_id'] ?? 0);
            
            if ($mesa_id <= 0) {
                throw new Exception('ID de mesa inválido');
            }
            
            // Verificar si la mesa tiene órdenes abiertas
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM ordenes 
                WHERE mesa_id = ? AND estado NOT IN ('completada', 'cancelada')
            ");
            $stmt->execute([$mesa_id]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('No se puede eliminar una mesa con órdenes abiertas');
            }
            
            // Eliminar layout de la mesa si existe
            $stmt = $pdo->prepare("DELETE FROM mesa_layouts WHERE mesa_id = ?");
            $stmt->execute([$mesa_id]);
            
            // Eliminar la mesa
            $stmt = $pdo->prepare("DELETE FROM mesas WHERE id = ?");
            $stmt->execute([$mesa_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Mesa no encontrada');
            }
            
            echo json_encode(['success' => true, 'message' => 'Mesa eliminada correctamente']);
            break;
            
        default:
            // Crear mesa (acción por defecto)
            $nombre = trim($_POST['nombre'] ?? '');
            
            if (empty($nombre)) {
                throw new Exception('El nombre de la mesa es requerido');
            }
            
            // Verificar que no exista una mesa con el mismo nombre
            $stmt = $pdo->prepare("SELECT id FROM mesas WHERE nombre = ?");
            $stmt->execute([$nombre]);
            if ($stmt->fetch()) {
                throw new Exception('Ya existe una mesa con ese nombre');
            }
            
            // Crear la mesa solo con el campo nombre
            $stmt = $pdo->prepare("INSERT INTO mesas (nombre) VALUES (?)");
            $stmt->execute([$nombre]);
            $mesa_id = $pdo->lastInsertId();
            
            // Crear layout inicial con dimensiones estándar (igual que las existentes)
            // Buscar una posición libre en el layout
            $stmt = $pdo->query("SELECT MAX(posicion_x) as max_x, MAX(posicion_y) as max_y FROM mesa_layouts");
            $layout_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $pos_x = ($layout_info['max_x'] ?? 200) + 150; // Nueva posición a la derecha
            $pos_y = $layout_info['max_y'] ?? 200;
            
            // Si se sale del área visible, empezar nueva fila
            if ($pos_x > 800) {
                $pos_x = 300;
                $pos_y = ($layout_info['max_y'] ?? 200) + 150;
            }
            
            // Insertar layout inicial con las mismas dimensiones que las mesas existentes
            $stmt = $pdo->prepare("
                INSERT INTO mesa_layouts (mesa_id, posicion_x, posicion_y, ancho, alto, rotacion, tipo_visual) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $mesa_id,
                $pos_x,     // Posición X calculada
                $pos_y,     // Posición Y calculada
                120,        // Ancho estándar
                100,        // Alto estándar
                0,          // Sin rotación inicial
                'rectangular' // Tipo visual estándar
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Mesa creada correctamente',
                'mesa_id' => $mesa_id,
                'nombre' => $nombre,
                'redirect' => true // Para forzar recarga de la página
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>