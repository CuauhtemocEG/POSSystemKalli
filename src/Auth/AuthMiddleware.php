<?php
namespace POS\Auth;

use POS\Auth\JWTAuth;

class AuthMiddleware {
    private $jwtAuth;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->jwtAuth = new JWTAuth($pdo);
    }
    
    /**
     * Verificar autenticación
     */
    public function authenticate($redirectToLogin = true) {
        // Verificar si la ruta actual requiere autenticación
        if ($this->isPublicRoute()) {
            return true;
        }
        
        $token = $this->getTokenFromRequest();
        
        if (!$token) {
            return $this->handleUnauthenticated($redirectToLogin);
        }
        
        $validation = $this->jwtAuth->validateToken($token);
        
        if (!$validation['valid']) {
            return $this->handleUnauthenticated($redirectToLogin);
        }
        
        // Almacenar datos del usuario en sesión
        $userData = $validation['data'];
        
        // Asegurar que los permisos sean un array
        if (isset($userData['permisos']) && is_object($userData['permisos'])) {
            $userData['permisos'] = json_decode(json_encode($userData['permisos']), true);
        }
        
        $_SESSION['user_data'] = $userData;
        $_SESSION['authenticated'] = true;
        
        return true;
    }
    
    /**
     * Verificar permisos específicos
     */
    public function checkPermission($module, $action, $redirectOnFail = true) {
        if (!$this->isAuthenticated()) {
            return $this->handleUnauthenticated($redirectOnFail);
        }
        
        $userData = $_SESSION['user_data'] ?? null;
        
        if (!$userData || !isset($userData['permisos'])) {
            return $this->handleUnauthorized($redirectOnFail);
        }
        
        $hasPermission = $this->jwtAuth->hasPermission(
            $userData['permisos'], 
            $module, 
            $action
        );
        
        if (!$hasPermission) {
            return $this->handleUnauthorized($redirectOnFail);
        }
        
        return true;
    }
    
    /**
     * Obtener token desde la request
     */
    private function getTokenFromRequest() {
        // Prioridad: Header Authorization > Cookie > GET/POST
        
        // 1. Authorization Header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        // 2. Cookie
        if (isset($_COOKIE['jwt_token'])) {
            return $_COOKIE['jwt_token'];
        }
        
        // 3. GET/POST parameter
        return $_GET['token'] ?? $_POST['token'] ?? null;
    }
    
    /**
     * Verificar si la ruta actual es pública
     */
    private function isPublicRoute() {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach (PUBLIC_ROUTES as $route) {
            if (strpos($currentPath, $route) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public function isAuthenticated() {
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }
    
    /**
     * Obtener usuario actual
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $_SESSION['user_data'] ?? null;
    }
    
    /**
     * Obtener rol del usuario actual
     */
    public function getCurrentUserRole() {
        $userData = $this->getCurrentUser();
        return $userData['rol'] ?? null;
    }
    
    /**
     * Verificar si el usuario actual tiene un rol específico
     */
    public function hasRole($role) {
        return $this->getCurrentUserRole() === $role;
    }
    
    /**
     * Manejar usuario no autenticado
     */
    private function handleUnauthenticated($redirect = true) {
        if ($this->isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'No autorizado',
                'code' => 'UNAUTHENTICATED'
            ]);
            exit;
        }
        
        if ($redirect) {
            header('Location: /POS/login.php');
            exit;
        }
        
        return false;
    }
    
    /**
     * Manejar acceso no autorizado (sin permisos)
     */
    private function handleUnauthorized($redirect = true) {
        if ($this->isAjaxRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Sin permisos suficientes',
                'code' => 'UNAUTHORIZED'
            ]);
            exit;
        }
        
        if ($redirect) {
            header('Location: /POS/error.php?code=403');
            exit;
        }
        
        return false;
    }
    
    /**
     * Verificar si es una petición AJAX
     */
    private function isAjaxRequest() {
        return (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            isset($_SERVER['CONTENT_TYPE']) && 
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
        );
    }
    
    /**
     * Limpiar sesión
     */
    public function clearSession() {
        unset($_SESSION['user_data']);
        unset($_SESSION['authenticated']);
        
        // Limpiar cookie
        if (isset($_COOKIE['jwt_token'])) {
            setcookie('jwt_token', '', time() - 3600, '/');
        }
    }
    
    /**
     * Establecer token en cookie
     */
    public function setTokenCookie($token, $remember = false) {
        $expiration = $remember ? time() + JWT_REFRESH_TIME : 0; // 0 = session cookie
        
        setcookie('jwt_token', $token, $expiration, '/', '', false, true); // httpOnly
    }
}
