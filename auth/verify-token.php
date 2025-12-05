<?php
require_once '../conexion.php';
require_once '../src/Auth/JWTAuth.php';

use POS\Auth\JWTAuth;

header('Content-Type: application/json');

$token = $_GET['token'] ?? $_POST['token'] ?? $_COOKIE['jwt_token'] ?? null;

if (!$token) {
    // Verificar Authorization header
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }
}

if (!$token) {
    http_response_code(400);
    echo json_encode([
        'valid' => false,
        'message' => 'Token no proporcionado'
    ]);
    exit;
}

try {
    $pdo = conexion();
    $jwtAuth = new JWTAuth($pdo);
    
    $result = $jwtAuth->validateToken($token);
    
    if ($result['valid']) {
        // Obtener información completa del usuario
        $user = $jwtAuth->getCurrentUser($token);
        
        echo json_encode([
            'valid' => true,
            'user' => $user ? [
                'id' => $user['id'],
                'username' => $user['username'],
                'nombre_completo' => $user['nombre_completo'],
                'email' => $user['email'],
                'rol' => $user['rol_nombre'],
                'permisos' => json_decode($user['permisos'], true),
                'ultimo_login' => $user['ultimo_login']
            ] : null,
            'expires_at' => $result['data']['exp'] ?? null
        ]);
    } else {
        http_response_code(401);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'message' => 'Error interno del servidor'
    ]);
    error_log("Error en verify-token: " . $e->getMessage());
}
