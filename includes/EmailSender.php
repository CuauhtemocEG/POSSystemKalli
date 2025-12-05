<?php
/**
 * Clase para manejar envío de emails para el sistema POS
 * Soporta PHP mail() nativo y SMTP
 */

// require_once __DIR__ . '/../config/config.php';

class EmailSender {
    private $pdo;
    private $config;
    
    // Configuración de email
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    private $useSmtp;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->config = new ConfiguracionSistema($pdo);
        
        // Cargar configuración desde la base de datos o config
        $this->loadEmailConfig();
    }
    
    /**
     * Cargar configuración de email
     */
    private function loadEmailConfig() {
        // Configuración desde base de datos
        $this->smtpHost = $this->config->obtener('smtp_host', 'smtp.titan.com');
        $this->smtpPort = $this->config->obtener('smtp_port', 465);
        $this->smtpUsername = $this->config->obtener('smtp_username', '');
        $this->smtpPassword = $this->config->obtener('smtp_password', '');
        $this->fromEmail = $this->config->obtener('email_from', 'no-reply@restaurant.com');
        $this->fromName = $this->config->obtener('email_from_name', 'Sistema POS');
        $this->useSmtp = $this->config->obtener('use_smtp', false);
    }
    
    /**
     * Enviar PIN de cancelación por email
     */
    public function enviarPinCancelacion($orden_id, $producto_nombre, $mesa_nombre, $codigo_pin, $tiempo_expiracion, $razon = '') {
        try {
            // Obtener emails de administradores
            $emails_admin = $this->obtenerEmailsAdmin();
            
            if (empty($emails_admin)) {
                throw new Exception('No hay emails de administradores configurados');
            }
            
            $empresa = $this->config->obtener('empresa_nombre', 'Restaurant');
            
            // Crear el contenido del email
            $asunto = "🚨 AUTORIZACIÓN REQUERIDA - Cancelación de Producto #{$orden_id}";
            
            // Crear mensaje HTML
            $mensaje_html = $this->crearMensajeHTMLPin($empresa, $mesa_nombre, $producto_nombre, $codigo_pin, $tiempo_expiracion, $razon, $orden_id);
            
            // Crear mensaje de texto plano
            $mensaje_texto = $this->crearMensajeTextoPin($empresa, $mesa_nombre, $producto_nombre, $codigo_pin, $tiempo_expiracion, $razon);
            
            $envios_exitosos = 0;
            $errores = [];
            
            foreach ($emails_admin as $email) {
                if ($this->enviarEmail($email, $asunto, $mensaje_html, $mensaje_texto)) {
                    $envios_exitosos++;
                    $this->registrarEnvio($email, $asunto, 'EMAIL_PIN', 'EXITOSO');
                } else {
                    $errores[] = "Error enviando a {$email}";
                    $this->registrarEnvio($email, $asunto, 'EMAIL_PIN', 'ERROR: No se pudo enviar');
                }
            }
            
            if ($envios_exitosos > 0) {
                return [
                    'success' => true,
                    'message' => "PIN enviado por email a {$envios_exitosos} administrador(es)",
                    'emails_enviados' => $envios_exitosos,
                    'total_emails' => count($emails_admin)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se pudo enviar el PIN a ningún email: ' . implode(', ', $errores)
                ];
            }
            
        } catch (Exception $e) {
            $this->registrarEnvio('sistema', 'Error general', 'EMAIL_PIN', 'ERROR: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al enviar PIN por email: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crear mensaje HTML para PIN
     */
    private function crearMensajeHTMLPin($empresa, $mesa, $producto, $pin, $expiracion, $razon, $orden_id) {
        $fecha_actual = date('d/m/Y H:i:s');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #dc2626, #b91c1c); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .pin-box { background: #fff; border: 3px solid #dc2626; padding: 20px; margin: 20px 0; text-align: center; border-radius: 8px; }
                .pin-code { font-size: 36px; font-weight: bold; color: #dc2626; letter-spacing: 8px; }
                .info-box { background: #fff; padding: 15px; margin: 10px 0; border-left: 4px solid #3b82f6; }
                .warning { background: #fef3c7; color: #d97706; padding: 15px; border-radius: 6px; margin: 15px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚨 AUTORIZACIÓN REQUERIDA</h1>
                    <p>{$empresa}</p>
                </div>
                
                <div class='content'>
                    <h2>Solicitud de Cancelación de Producto</h2>
                    
                    <div class='info-box'>
                        <strong>📋 Detalles de la Solicitud:</strong><br>
                        <strong>Orden:</strong> #{$orden_id}<br>
                        <strong>Mesa:</strong> {$mesa}<br>
                        <strong>Producto:</strong> {$producto}<br>
                        <strong>Fecha:</strong> {$fecha_actual}
                    </div>
                    
                    " . (!empty($razon) ? "
                    <div class='info-box'>
                        <strong>📝 Razón de cancelación:</strong><br>
                        {$razon}
                    </div>
                    " : "") . "
                    
                    <div class='pin-box'>
                        <p><strong>Su código PIN de autorización es:</strong></p>
                        <div class='pin-code'>{$pin}</div>
                        <p style='margin-top: 15px; color: #666;'>
                            Válido por <strong>{$expiracion} minutos</strong>
                        </p>
                    </div>
                    
                    <div class='warning'>
                        ⚠️ <strong>Importante:</strong> Este código expirará automáticamente en {$expiracion} minutos. 
                        Ingrese el código en el Kalli Jaguar POS para autorizar la cancelación del producto.
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <p><strong>¿Cómo usar este código?</strong></p>
                        <ol style='text-align: left; display: inline-block;'>
                            <li>Abra el sistema Kalli Jaguar POS.</li>
                            <li>Vaya a la pestaña de Autorizaciones.</li>
                            <li>Ingrese el código PIN cuando se solicite o pulse USAR ESTE PIN.</li>
                            <li>Confirme la cancelación.</li>
                        </ol>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Este email fue generado automáticamente por el Kalli Jaguar POS de {$empresa}</p>
                    <p>No responda a este mensaje</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Crear mensaje de texto plano para PIN
     */
    private function crearMensajeTextoPin($empresa, $mesa, $producto, $pin, $expiracion, $razon) {
        $fecha_actual = date('d/m/Y H:i:s');
        
        $mensaje = "🚨 AUTORIZACIÓN REQUERIDA - {$empresa}\n\n";
        $mensaje .= "SOLICITUD DE CANCELACIÓN DE PRODUCTO\n";
        $mensaje .= "=====================================\n\n";
        $mensaje .= "Mesa: {$mesa}\n";
        $mensaje .= "Producto: {$producto}\n";
        $mensaje .= "Fecha: {$fecha_actual}\n";
        
        if (!empty($razon)) {
            $mensaje .= "Razón: {$razon}\n";
        }
        
        $mensaje .= "\n🔑 CÓDIGO PIN DE AUTORIZACIÓN: {$pin}\n\n";
        $mensaje .= "⏰ Válido por {$expiracion} minutos\n\n";
        $mensaje .= "INSTRUCCIONES:\n";
        $mensaje .= "1. Abra el sistema Kalli Jaguar POS.\n";
        $mensaje .= "2. Vaya a la pestaña de Autorizaciones.\n";
        $mensaje .= "3. Ingrese el código PIN cuando se solicite o pulse USAR ESTE PIN.\n";
        $mensaje .= "4. Confirme la cancelación.\n\n";
        $mensaje .= "⚠️ IMPORTANTE: Este código expirará automáticamente.\n\n";
        $mensaje .= "---\n";
        $mensaje .= "Este email fue generado automáticamente.\n";
        $mensaje .= "No responda a este mensaje.";
        
        return $mensaje;
    }
    
    /**
     * Enviar email usando PHP mail() o SMTP
     */
    private function enviarEmail($to, $subject, $html_message, $text_message, &$error = null) {
        try {
            if ($this->useSmtp && !empty($this->smtpHost) && !empty($this->smtpUsername)) {
                return $this->enviarPorSMTP($to, $subject, $html_message, $text_message, $error);
            } else {
                return $this->enviarPorPHPMail($to, $subject, $html_message, $text_message, $error);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log("Error enviando email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar por PHP mail() nativo
     */
    private function enviarPorPHPMail($to, $subject, $html_message, $text_message, &$error = null) {
        $boundary = md5(time());
        
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $text_message . "\r\n";
        
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $html_message . "\r\n";
        
        $body .= "--{$boundary}--";
        
        $result = mail($to, $subject, $body, $headers);
        if (!$result) {
            $error = error_get_last()['message'] ?? 'mail() falló sin mensaje de error';
        }
        return $result;
    }
    
    /**
     * Enviar por SMTP (implementación básica)
     */
    private function enviarPorSMTP($to, $subject, $html_message, $text_message, &$error = null) {
        try {
            // Usar PHPMailer para SMTP real
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // LOG de configuración SMTP
            error_log('[SMTP] Host: ' . $this->smtpHost);
            error_log('[SMTP] Puerto: ' . $this->smtpPort);
            error_log('[SMTP] Usuario: ' . $this->smtpUsername);
            error_log('[SMTP] From: ' . $this->fromEmail);
            error_log('[SMTP] Usar SMTP: ' . ($this->useSmtp ? 'SI' : 'NO'));
            
            // Habilitar debug de PHPMailer
            $mail->SMTPDebug = 2; // 2 = client+server, 3 = client, 4 = server
            $mail->Debugoutput = function($str, $level) {
                error_log('[PHPMailer] ' . $str);
            };
            
            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = 'ssl';
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';
            
            // Configuración del mensaje
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_message;
            $mail->AltBody = $text_message;
            
            // Enviar
            $result = $mail->send();
            if ($result) {
                error_log("Email SMTP enviado exitosamente a: $to");
            }
            return $result;
        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log("Error SMTP: " . $e->getMessage());
            // Fallback a PHP mail()
            $fallbackError = null;
            $fallback = $this->enviarPorPHPMail($to, $subject, $html_message, $text_message, $fallbackError);
            if (!$fallback && $fallbackError) {
                $error .= ' | Fallback mail(): ' . $fallbackError;
            }
            return $fallback;
        }
    }
    
    /**
     * Obtener emails de administradores
     */
    private function obtenerEmailsAdmin() {
        $emails = [];
        
        // Obtener de configuración
        $email1 = $this->config->obtener('admin_email_1', '');
        $email2 = $this->config->obtener('admin_email_2', '');
        $email_empresa = $this->config->obtener('empresa_email', '');
        
        if (!empty($email1) && filter_var($email1, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $email1;
        }
        
        if (!empty($email2) && filter_var($email2, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $email2;
        }
        
        // Si no hay emails específicos, usar el de la empresa
        if (empty($emails) && !empty($email_empresa) && filter_var($email_empresa, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $email_empresa;
        }
        
        return array_unique($emails);
    }
    
    /**
     * Registrar envío en base de datos
     */
    private function registrarEnvio($destino, $asunto, $tipo, $estado) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notificaciones_log (destino, mensaje, tipo, estado, fecha_envio) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$destino, $asunto, $tipo, $estado]);
        } catch (Exception $e) {
            error_log("Error registrando envío de email: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar configuración de email
     */
    public function emailConfigurado() {
        $emails_admin = $this->obtenerEmailsAdmin();
        return !empty($emails_admin);
    }
    
    /**
     * Enviar email de prueba
     */
    public function enviarEmailPrueba($email_destino) {
        $asunto = "Prueba de Email - Sistema POS";
        $mensaje_html = "
        <h2>✅ Prueba de Email Exitosa</h2>
        <p>Este es un email de prueba del Sistema POS.</p>
        <p>Si recibiste este mensaje, la configuración de email está funcionando correctamente.</p>
        <p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>
        ";
        $mensaje_texto = "Prueba de Email Exitosa\n\nEste es un email de prueba del Sistema POS.\nSi recibiste este mensaje, la configuración está funcionando correctamente.\n\nFecha: " . date('d/m/Y H:i:s');
        $error = null;
        if ($this->enviarEmail($email_destino, $asunto, $mensaje_html, $mensaje_texto, $error)) {
            $this->registrarEnvio($email_destino, $asunto, 'EMAIL_PRUEBA', 'EXITOSO');
            return ['success' => true, 'message' => 'Email de prueba enviado correctamente'];
        } else {
            $this->registrarEnvio($email_destino, $asunto, 'EMAIL_PRUEBA', 'ERROR: ' . $error);
            return ['success' => false, 'message' => 'Error al enviar email de prueba: ' . $error];
        }
    }
}
?>
