<?php
/**
 * Middleware de verificación de autenticación
 * Incluir este archivo en todas las páginas que requieren autenticación
 */

// Headers anti-caché para evitar problemas al cambiar de usuario
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Fecha en el pasado

require_once __DIR__ . '/conexion.php';

// Inicializar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Requiere una sesión real creada por auth/login.php; no se otorga acceso por defecto
if (!isset($_SESSION['user_data']) || !isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    $esPeticionApi = (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
        strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false ||
        strpos($_SERVER['REQUEST_URI'] ?? '', '/controllers/') !== false
    );

    if ($esPeticionApi) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Sesión no válida, inicia sesión nuevamente']);
    } else {
        header('Location: ' . rtrim(APP_URL, '/') . '/login.php');
    }
    exit;
}

// Asegurar que $_SESSION['usuario_id'] esté disponible para compatibilidad con código legacy
if (isset($_SESSION['user_data']['user_id'])) {
    $_SESSION['usuario_id'] = $_SESSION['user_data']['user_id'];
}

// Crear un pseudo AuthMiddleware para compatibilidad
class SimpleAuthMiddleware {
    public function getCurrentUser() {
        return $_SESSION['user_data'] ?? null;
    }
    
    public function checkPermission($module, $action, $redirect = false) {
        return true; // Permitir acceso completo
    }
    
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && isset($user['rol']) && $user['rol'] === $role;
    }
    
    public function authenticate() {
        return true; // Siempre autenticado
    }
}

$authMiddleware = new SimpleAuthMiddleware();

// Función helper para obtener usuario actual
function getCurrentUser() {
    global $authMiddleware;
    return $authMiddleware->getCurrentUser();
}

// Función helper para verificar permisos
function hasPermission($module, $action = 'ver') {
    // Obtener información del usuario actual
    $user = getUserInfo();
    $rol = $user['rol'] ?? 'mesero';
    
    // Definir permisos por rol según especificaciones del cliente
    $permisos = [
        'administrador' => [
            'mesas' => ['ver', 'crear', 'editar', 'eliminar'],
            'ordenes' => ['ver', 'crear', 'editar', 'eliminar'],
            'productos' => ['ver', 'crear', 'editar', 'eliminar'],
            'reportes' => ['ver', 'crear', 'editar', 'eliminar', 'exportar'],
            'cocina' => ['ver', 'crear', 'editar', 'eliminar'],
            'bar' => ['ver', 'crear', 'editar', 'eliminar'],
            'desayunos' => ['ver', 'crear', 'editar', 'eliminar'],
            'configuracion' => ['ver', 'crear', 'editar', 'eliminar'],
            'promociones' => ['ver', 'crear', 'editar', 'eliminar'],
            'usuarios' => ['ver', 'crear', 'editar', 'eliminar']
        ],
        'mesero' => [
            'mesas' => ['ver', 'editar'],
            'ordenes' => ['crear', 'editar'],
            'productos' => [],
            'reportes' => [],
            'cocina' => [],
            'bar' => [],
            'configuracion' => [],
            'usuarios' => []
        ],
        'cocinero' => [
            'mesas' => [],
            'ordenes' => [],
            'productos' => [],
            'reportes' => [],
            'cocina' => ['ver', 'editar'],
            'bar' => [],
            'configuracion' => [],
            'usuarios' => []
        ],
        'bartender' => [
            'mesas' => [],
            'ordenes' => [],
            'productos' => [],
            'reportes' => [],
            'cocina' => [],
            'bar' => ['ver', 'editar'],
            'configuracion' => [],
            'usuarios' => []
        ],
        'cajero' => [
            'mesas' => ['ver'],
            'ordenes' => ['ver'], 
            'productos' => [],
            'reportes' => ['ver', 'exportar'],
            'cocina' => ['ver'],
            'bar' => ['ver'],
            'desayunos' => ['ver', 'editar'],
            'configuracion' => [],
            'usuarios' => []
        ],
        'Antojitos' => [
            'mesas' => [],
            'ordenes' => [],
            'productos' => [],
            'reportes' => [],
            'cocina' => [],
            'desayunos' => ['ver', 'editar'],
            'bar' => [],
            'configuracion' => [],
            'usuarios' => []
        ]
    ];
    
    // Verificar si el rol existe
    if (!isset($permisos[$rol])) {
        return false;
    }
    
    // Verificar si el módulo existe para el rol
    if (!isset($permisos[$rol][$module])) {
        return false;
    }
    
    // Verificar si tiene el permiso específico
    return in_array($action, $permisos[$rol][$module]);
}

// Función helper para verificar rol
function hasRole($role) {
    global $authMiddleware;
    return $authMiddleware->hasRole($role);
}

// Función helper para obtener información del usuario para mostrar en UI
function getUserInfo() {
    $user = getCurrentUser();

    if (!$user) {
        // No debería ocurrir: el bloqueo de arriba ya exige una sesión válida
        return ['id' => null, 'username' => null, 'rol' => null, 'permisos' => []];
    }

    return [
        'id' => $user['user_id'],
        'username' => $user['username'],
        'rol' => $user['rol'],
        'permisos' => $user['permisos']
    ];
}

// Variables de compatibilidad para controladores
$_SESSION['user_id'] = $_SESSION['user_data']['user_id'];
$_SESSION['role'] = $_SESSION['user_data']['rol'];
$_SESSION['username'] = $_SESSION['user_data']['username'];
