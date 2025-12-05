<?php
namespace POS\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;
use Exception;

class JWTAuth {
    private $pdo;
    private $secretKey;
    private $algorithm;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->secretKey = JWT_SECRET_KEY;
        $this->algorithm = JWT_ALGORITHM;
    }
    
    /**
     * Autenticar usuario con username/email y password
     */
    public function login($login, $password, $remember = false) {
        try {
            // Buscar usuario por username o email
            $stmt = $this->pdo->prepare("
                SELECT u.*, r.nombre as rol_nombre, r.permisos 
                FROM usuarios u 
                JOIN roles r ON u.rol_id = r.id 
                WHERE (u.username = ? OR u.email = ?) AND u.activo = 1
            ");
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $this->logLoginAttempt($login, false, $_SERVER['REMOTE_ADDR'] ?? '');
                throw new Exception('Credenciales inválidas');
            }
            
            // Actualizar último login
            $this->pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")
                      ->execute([$user['id']]);
            
            // Generar tokens
            $accessToken = $this->generateAccessToken($user);
            $refreshToken = $remember ? $this->generateRefreshToken($user) : null;
            
            $this->logLoginAttempt($login, true, $_SERVER['REMOTE_ADDR'] ?? '');
            
            return [
                'success' => true,
                'user' => $this->sanitizeUserData($user),
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => JWT_EXPIRATION_TIME
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar Access Token JWT
     */
    private function generateAccessToken($user) {
        $issuedAt = time();
        $expirationTime = $issuedAt + JWT_EXPIRATION_TIME;
        $jti = bin2hex(random_bytes(16));
        
        $payload = [
            'iss' => APP_URL,
            'aud' => APP_URL,
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'jti' => $jti,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'rol' => $user['rol_nombre'],
            'permisos' => json_decode($user['permisos'], true)
        ];
        
        // Guardar sesión en BD
        $this->pdo->prepare("
            INSERT INTO sesiones (usuario_id, token_jti, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))
        ")->execute([
            $user['id'],
            $jti,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expirationTime
        ]);
        
        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }
    
    /**
     * Generar Refresh Token
     */
    private function generateRefreshToken($user) {
        $issuedAt = time();
        $expirationTime = $issuedAt + JWT_REFRESH_TIME;
        $jti = bin2hex(random_bytes(16));
        
        $payload = [
            'iss' => APP_URL,
            'aud' => APP_URL,
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'jti' => $jti,
            'user_id' => $user['id'],
            'type' => 'refresh'
        ];
        
        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }
    
    /**
     * Validar token JWT
     */
    public function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $decodedArray = (array) $decoded;
            
            // Convertir permisos de objeto a array si es necesario
            if (isset($decodedArray['permisos']) && is_object($decodedArray['permisos'])) {
                $decodedArray['permisos'] = json_decode(json_encode($decodedArray['permisos']), true);
            }
            
            // Verificar si el token está revocado
            if (isset($decodedArray['jti'])) {
                $stmt = $this->pdo->prepare("
                    SELECT revocado FROM sesiones 
                    WHERE token_jti = ? AND expires_at > NOW()
                ");
                $stmt->execute([$decodedArray['jti']]);
                $session = $stmt->fetch();
                
                if (!$session || $session['revocado']) {
                    throw new Exception('Token revocado o expirado');
                }
            }
            
            return [
                'valid' => true,
                'data' => $decodedArray
            ];
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Renovar access token con refresh token
     */
    public function refreshToken($refreshToken) {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->secretKey, $this->algorithm));
            $decodedArray = (array) $decoded;
            
            if (!isset($decodedArray['type']) || $decodedArray['type'] !== 'refresh') {
                throw new Exception('Token de tipo inválido');
            }
            
            // Obtener datos del usuario
            $stmt = $this->pdo->prepare("
                SELECT u.*, r.nombre as rol_nombre, r.permisos 
                FROM usuarios u 
                JOIN roles r ON u.rol_id = r.id 
                WHERE u.id = ? AND u.activo = 1
            ");
            $stmt->execute([$decodedArray['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Generar nuevo access token
            $newAccessToken = $this->generateAccessToken($user);
            
            return [
                'success' => true,
                'access_token' => $newAccessToken,
                'expires_in' => JWT_EXPIRATION_TIME
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cerrar sesión y revocar token
     */
    public function logout($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $decodedArray = (array) $decoded;
            
            if (isset($decodedArray['jti'])) {
                $this->pdo->prepare("UPDATE sesiones SET revocado = 1 WHERE token_jti = ?")
                          ->execute([$decodedArray['jti']]);
            }
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Verificar permisos del usuario
     */
    public function hasPermission($userPermissions, $module, $action) {
        // Convertir objeto a array si es necesario
        if (is_object($userPermissions)) {
            $userPermissions = json_decode(json_encode($userPermissions), true);
        }
        
        if (!isset($userPermissions[$module])) {
            return false;
        }
        
        // Asegurarse de que los permisos del módulo sean un array
        $modulePermissions = $userPermissions[$module];
        if (is_object($modulePermissions)) {
            $modulePermissions = json_decode(json_encode($modulePermissions), true);
        }
        
        return in_array($action, $modulePermissions);
    }
    
    /**
     * Obtener usuario actual desde token
     */
    public function getCurrentUser($token) {
        $validation = $this->validateToken($token);
        
        if (!$validation['valid']) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT u.*, r.nombre as rol_nombre, r.permisos 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE u.id = ? AND u.activo = 1
        ");
        $stmt->execute([$validation['data']['user_id']]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Limpiar datos sensibles del usuario
     */
    private function sanitizeUserData($user) {
        unset($user['password_hash'], $user['token_reset'], $user['token_reset_expira']);
        return $user;
    }
    
    /**
     * Registrar intento de login
     */
    private function logLoginAttempt($login, $success, $ip) {
        if (!LOG_AUTH_ATTEMPTS) return;
        
        // Aquí podrías implementar un sistema de logs más robusto
        error_log(sprintf(
            "[%s] Login attempt - User: %s, Success: %s, IP: %s",
            date('Y-m-d H:i:s'),
            $login,
            $success ? 'YES' : 'NO',
            $ip
        ));
    }
    
    /**
     * Limpiar sesiones expiradas
     */
    public function cleanExpiredSessions() {
        $this->pdo->query("DELETE FROM sesiones WHERE expires_at < NOW()");
    }
}
