<?php
/**
 * Clase para manejar la configuración del sistema
 */
class ConfiguracionSistema {
    private $pdo;
    private $cache = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener valor de configuración
     */
    public function obtener($clave, $default = null) {
        if (isset($this->cache[$clave])) {
            return $this->cache[$clave];
        }
        
        $stmt = $this->pdo->prepare("SELECT valor, tipo FROM configuracion WHERE clave = ?");
        $stmt->execute([$clave]);
        $config = $stmt->fetch();
        
        if (!$config) {
            return $default;
        }
        
        $valor = $this->convertirTipo($config['valor'], $config['tipo']);
        $this->cache[$clave] = $valor;
        
        return $valor;
    }
    
    /**
     * Establecer valor de configuración
     */
    public function establecer($clave, $valor, $descripcion = null, $tipo = 'string') {
        $stmt = $this->pdo->prepare("
            INSERT INTO configuracion (clave, valor, descripcion, tipo) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            valor = VALUES(valor), 
            descripcion = COALESCE(VALUES(descripcion), descripcion),
            actualizado_en = CURRENT_TIMESTAMP
        ");
        
        $resultado = $stmt->execute([$clave, $valor, $descripcion, $tipo]);
        
        // Limpiar cache
        unset($this->cache[$clave]);
        
        return $resultado;
    }
    
    /**
     * Obtener todas las configuraciones que empiecen con un prefijo
     */
    public function obtenerPorPrefijo($prefijo) {
        $stmt = $this->pdo->prepare("
            SELECT clave, valor, descripcion, tipo 
            FROM configuracion 
            WHERE clave LIKE ?
            ORDER BY clave
        ");
        $stmt->execute([$prefijo . '%']);
        $configs = $stmt->fetchAll();
        
        $resultado = [];
        foreach ($configs as $config) {
            $resultado[$config['clave']] = [
                'valor' => $this->convertirTipo($config['valor'], $config['tipo']),
                'descripcion' => $config['descripcion'],
                'tipo' => $config['tipo']
            ];
        }
        
        return $resultado;
    }
    
    
    public function tiempoExpiracionPIN() {
        return (int) $this->obtener('email_pin_expiracion', 300);
    }
    
    /**
     * Convertir valor según su tipo
     */
    private function convertirTipo($valor, $tipo) {
        switch ($tipo) {
            case 'integer':
                return is_numeric($valor) ? (int) $valor : 0;
            case 'boolean':
                return in_array(strtolower($valor), ['true', '1', 'yes', 'on']);
            case 'json':
                return json_decode($valor, true) ?: [];
            default:
                return $valor;
        }
    }
    
    /**
     * Limpiar toda la cache
     */
    public function limpiarCache() {
        $this->cache = [];
    }
    
    /**
     * Verificar si las notificaciones están habilitadas
     */
    public function notificacionesHabilitadas() {
        return $this->obtener('notificaciones_habilitadas', true);
    }
    
    /**
     * Verificar si se está usando modo de prueba
     */
    public function usarModoPrueba() {
        return $this->obtener('usar_modo_prueba', true);
    }
    
    /**
     * Obtener emails de administradores
     */
    public function obtenerEmailsAdmin() {
        $emails = [];
        
        $email1 = $this->obtener('admin_email_1', '');
        $email2 = $this->obtener('admin_email_2', '');
        
        if (!empty($email1)) $emails[] = $email1;
        if (!empty($email2)) $emails[] = $email2;
        
        // Si no hay emails específicos, usar el email de la empresa
        if (empty($emails)) {
            $email_empresa = $this->obtener('empresa_email', '');
            if (!empty($email_empresa)) {
                $emails[] = $email_empresa;
            }
        }
        
        return $emails;
    }
    
    /**
     * Verificar si Twilio está configurado
     */
    public function twilioConfigurado() {
        // require_once __DIR__ . '/../config/config.php';
        return false; // Twilio deshabilitado
    }
    
    /**
     * Obtener todas las configuraciones como array
     */
    public function obtenerTodasConfiguraciones() {
        $stmt = $this->pdo->prepare("SELECT clave, valor, tipo FROM configuracion");
        $stmt->execute();
        $configs = $stmt->fetchAll();
        
        $resultado = [];
        foreach ($configs as $config) {
            $resultado[$config['clave']] = $this->convertirTipo($config['valor'], $config['tipo']);
        }
        
        return $resultado;
    }
    
    /**
     * Verificar si SendGrid está configurado
     */
    public function sendgridConfigurado() {
        // require_once __DIR__ . '/../config/config.php';
        return false; // SendGrid deshabilitado
    }
}
?>
