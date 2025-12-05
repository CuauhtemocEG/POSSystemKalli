<?php
// Verificar si ya hay una sesión activa antes de iniciar una nueva
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../auth-check.php';
require_once '../includes/ConfiguracionSistema.php';
require_once '../conexion.php';

// Verificar que es administrador usando la función del sistema
if (!hasPermission('configuracion', 'editar')) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Acceso denegado. Solo los administradores pueden modificar la configuración del sistema.'
    ]);
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener conexión a la base de datos
    $pdo = conexion();
    $config = new ConfiguracionSistema($pdo);
    $accion = $_POST['accion'] ?? '';

    switch ($accion) {
        case 'actualizar_email':
            // Validar emails requeridos
            $email1 = $_POST['admin_email_1'] ?? '';
            $email_from = $_POST['email_from'] ?? '';
            $email_from_name = $_POST['email_from_name'] ?? '';
            
            if (empty($email1)) {
                throw new Exception('El email del administrador principal es requerido');
            }
            
            if (empty($email_from)) {
                throw new Exception('El email remitente es requerido');
            }
            
            if (empty($email_from_name)) {
                throw new Exception('El nombre del remitente es requerido');
            }
            
            // Validar formato de emails
            if (!filter_var($email1, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El email del administrador principal no tiene un formato válido');
            }
            
            if (!filter_var($email_from, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El email remitente no tiene un formato válido');
            }
            
            $email2 = $_POST['admin_email_2'] ?? '';
            if (!empty($email2) && !filter_var($email2, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El email del administrador secundario no tiene un formato válido');
            }
            
            // Actualizar configuración de email
            $config->establecer('email_habilitado', isset($_POST['email_habilitado']) ? '1' : '0', 'Email habilitado para códigos PIN');
            $config->establecer('admin_email_1', $email1, 'Email administrador principal');
            $config->establecer('admin_email_2', $email2, 'Email administrador secundario');
            $config->establecer('email_from', $email_from, 'Email remitente del sistema');
            $config->establecer('email_from_name', $email_from_name, 'Nombre remitente del sistema');
            $config->establecer('usar_modo_prueba', isset($_POST['usar_modo_prueba']) ? '1' : '0', 'Modo de prueba activado');
            
            // Configuración SMTP
            $config->establecer('use_smtp', isset($_POST['use_smtp']) ? '1' : '0', 'Usar SMTP personalizado');
            $config->establecer('smtp_host', $_POST['smtp_host'] ?? 'smtp.gmail.com', 'Servidor SMTP');
            $config->establecer('smtp_port', $_POST['smtp_port'] ?? '587', 'Puerto SMTP');
            $config->establecer('smtp_username', $_POST['smtp_username'] ?? '', 'Usuario SMTP');
            $config->establecer('smtp_password', $_POST['smtp_password'] ?? '', 'Contraseña SMTP');
            
            // Convertir minutos a segundos para almacenar
            $expiracion_minutos = intval($_POST['pin_expiracion'] ?? 5);
            $config->establecer('email_pin_expiracion', $expiracion_minutos * 60, 'Tiempo de expiración de códigos PIN en segundos');
            
            echo json_encode([
                'success' => true, 
                'message' => 'Configuración de email actualizada correctamente'
            ]);
            break;
            
        case 'actualizar_empresa':
            // Actualizar información de empresa
            $config->establecer('empresa_nombre', $_POST['empresa_nombre'] ?? '', 'Nombre de la empresa');
            $config->establecer('empresa_direccion', $_POST['empresa_direccion'] ?? '', 'Dirección de la empresa');
            $config->establecer('empresa_telefono', $_POST['empresa_telefono'] ?? '', 'Teléfono de la empresa');
            $config->establecer('empresa_email', $_POST['empresa_email'] ?? '', 'Email de la empresa');
            $config->establecer('empresa_rfc', $_POST['empresa_rfc'] ?? '', 'RFC de la empresa');
            
            echo json_encode([
                'success' => true, 
                'message' => 'Información de la empresa actualizada correctamente'
            ]);
            break;
            
        case 'actualizar_impresoras':
            // Actualizar configuración de impresoras térmicas
            $config->establecer('impresion_automatica', isset($_POST['impresion_automatica']) ? '1' : '0', 'Impresión automática térmica habilitada');
            $config->establecer('metodo_impresion', $_POST['metodo_impresion'] ?? 'local', 'Método de impresión térmica');
            $config->establecer('nombre_impresora', $_POST['nombre_impresora'] ?? '', 'Nombre de la impresora térmica');
            $config->establecer('ip_impresora', $_POST['ip_impresora'] ?? '', 'IP de la impresora térmica');
            $config->establecer('puerto_impresora', $_POST['puerto_impresora'] ?? '9100', 'Puerto de la impresora térmica');
            $config->establecer('ancho_papel', $_POST['ancho_papel'] ?? '80', 'Ancho de papel térmico en mm');
            $config->establecer('copias_ticket', $_POST['copias_ticket'] ?? '1', 'Número de copias por ticket');
            $config->establecer('corte_automatico', $_POST['corte_automatico'] ?? '1', 'Corte automático de papel');
            
            // 🔥 NUEVA CONFIGURACIÓN DEL LOGO
            $config->establecer('logo_activado', isset($_POST['logo_activado']) ? '1' : '0', 'Logo activado en tickets');
            $config->establecer('logo_imagen', $_POST['logo_imagen'] ?? 'LogoBlack.png', 'Imagen del logo para tickets');
            $config->establecer('logo_tamaño', $_POST['logo_tamaño'] ?? 'grande', 'Tamaño del logo en tickets');
            
            echo json_encode([
                'success' => true, 
                'message' => 'Configuración de impresoras térmicas actualizada correctamente'
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida: ' . $accion);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Error de base de datos en actualizar_configuracion.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos'
    ]);
}
?>
