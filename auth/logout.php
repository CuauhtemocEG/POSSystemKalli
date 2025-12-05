<?php
// Cerrar sesión - Limpieza completa de sesión y cookies
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    // 1. Destruir todas las variables de sesión
    $_SESSION = array();

    // 2. Eliminar cookie de sesión de PHP
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // 3. IMPORTANTE: Eliminar token JWT
    setcookie('jwt_token', '', time() - 3600, '/', '', false, true);
    
    // 4. Eliminar cualquier otra cookie del sistema
    setcookie('user_role', '', time() - 3600, '/', '', false, false);
    setcookie('user_id', '', time() - 3600, '/', '', false, false);
    setcookie('remember_me', '', time() - 3600, '/', '', false, true);

    // 5. Finalmente, destruir la sesión
    session_destroy();
    
    // 6. Respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Sesión cerrada exitosamente',
        'redirect' => 'index.php'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cerrar sesión'
    ]);
    error_log("Error en logout: " . $e->getMessage());
}

