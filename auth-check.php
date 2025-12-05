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

// Solo crear sesión automática si NO hay datos de usuario ya establecidos
if (!isset($_SESSION['user_data']) && (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated'])) {
    // Esto solo debe pasar en desarrollo o si no hay un login real
    $_SESSION['authenticated'] = true;
    $_SESSION['user_data'] = [
        'user_id' => 1,
        'username' => 'admin',
        'rol' => 'administrador',
        'permisos' => [
            'mesas' => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true],
            'ordenes' => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true],
            'productos' => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true],
            'reportes' => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true, 'exportar' => true],
            'cocina' => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true],
            'bar' => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true],
            'configuracion' => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true],
            'usuarios' => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true]
        ]
    ];
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
            'configuracion' => ['ver', 'crear', 'editar', 'eliminar'],
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
            'productos' => ['ver', 'crear', 'editar', 'eliminar'],
            'reportes' => ['ver', 'exportar'],
            'cocina' => ['ver'],
            'bar' => ['ver'],
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
    
    // Para propósitos de desarrollo, permitir cambiar rol con parámetro GET
    $rol_override = $_GET['test_role'] ?? null;
    $roles_validos = ['administrador', 'gerente', 'cajero', 'mesero', 'cocinero', 'bartender'];
    
    if (!$user) {
        $rol_por_defecto = 'administrador';
        
        // Si hay un rol override válido, usarlo
        if ($rol_override && in_array($rol_override, $roles_validos)) {
            $rol_por_defecto = $rol_override;
        }
        
        return [
            'id' => 1,
            'username' => 'usuario_' . $rol_por_defecto,
            'rol' => $rol_por_defecto,
            'permisos' => []
        ];
    }
    
    $rol_usuario = $user['rol'];
    
    // Si hay un rol override válido, usarlo
    if ($rol_override && in_array($rol_override, $roles_validos)) {
        $rol_usuario = $rol_override;
    }
    
    return [
        'id' => $user['user_id'],
        'username' => $user['username'],
        'rol' => $rol_usuario,
        'permisos' => $user['permisos']
    ];
}

// Variables de compatibilidad para controladores
$_SESSION['user_id'] = $_SESSION['user_data']['user_id'] ?? 1;
$_SESSION['role'] = $_SESSION['user_data']['rol'] ?? 'administrador';
$_SESSION['username'] = $_SESSION['user_data']['username'] ?? 'admin';
