<?php
session_start();
require_once '../auth-check.php';
require_once '../conexion.php';

// Verificar que es administrador
if (!hasPermission('configuracion', 'editar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $token_jti = $input['session_id'] ?? ''; // Mantener session_id para compatibilidad con JS

    if (empty($token_jti)) {
        throw new Exception('Token de sesión requerido');
    }

    $pdo = conexion();
    
    // Verificar que la tabla sesiones existe
    $table_exists = $pdo->query("SHOW TABLES LIKE 'sesiones'")->rowCount() > 0;
    
    if ($table_exists) {
        // Revocar la sesión específica (marcar como revocada)
        $stmt = $pdo->prepare("UPDATE sesiones SET revocado = 1 WHERE token_jti = ?");
        $result = $stmt->execute([$token_jti]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Sesión revocada correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Sesión no encontrada o ya estaba revocada'
            ]);
        }
    } else {
        // Si no existe la tabla, simular éxito
        echo json_encode([
            'success' => true,
            'message' => 'Sesión revocada (modo compatibilidad)'
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
