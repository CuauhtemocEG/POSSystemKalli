<?php
session_start();
require_once '../../auth-check.php';
require_once '../../conexion.php';
require_once '../../includes/ConfiguracionSistema.php';
require_once '../../includes/EmailSender.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que hay sesión activa
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida. Por favor inicia sesión nuevamente.']);
    exit;
}

$pdo = conexion();
$config = new ConfiguracionSistema($pdo);

$producto_id = intval($_POST['producto_id'] ?? 0);
$orden_producto_id = intval($_POST['orden_producto_id'] ?? 0);
$orden_id = intval($_POST['orden_id'] ?? 0);
$razon = trim($_POST['razon'] ?? '');
$cantidad_cancelar = intval($_POST['cantidad_cancelar'] ?? 1);
$usuario_id = intval($_SESSION['usuario_id']); // Usar el usuario de la sesión activa

if ((!$producto_id && !$orden_producto_id) || !$orden_id) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    // Verificar que el producto esté en la orden - usar el ID de orden_productos si se proporciona
    if (isset($_POST['orden_producto_id']) && intval($_POST['orden_producto_id']) > 0) {
        $orden_producto_id = intval($_POST['orden_producto_id']);
        $stmt = $pdo->prepare("SELECT op.*, p.nombre as producto_nombre FROM orden_productos op JOIN productos p ON op.producto_id = p.id WHERE op.id = ? AND op.orden_id = ?");
        $stmt->execute([$orden_producto_id, $orden_id]);
        $item = $stmt->fetch();
        
        if ($item) {
            $producto_id = $item['producto_id']; // Actualizar producto_id del item encontrado
        }
    } else {
        // Método original: buscar por producto_id y orden_id
        $stmt = $pdo->prepare("SELECT op.*, p.nombre as producto_nombre FROM orden_productos op JOIN productos p ON op.producto_id = p.id WHERE op.orden_id = ? AND op.producto_id = ? AND op.estado != 'eliminado'");
        $stmt->execute([$orden_id, $producto_id]);
        $item = $stmt->fetch();
    }
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado en la orden']);
        exit;
    }
    
    // Verificar que hay suficientes unidades disponibles para cancelar
    $cantidad_total = intval($item['cantidad']);
    $preparado = intval($item['preparado']);
    $cancelado = intval($item['cancelado']);
    $pendiente_cancelacion = intval($item['pendiente_cancelacion'] ?? 0);
    $disponibles = $cantidad_total - $preparado - $cancelado - $pendiente_cancelacion;
    
    if ($cantidad_cancelar > $disponibles) {
        echo json_encode(['success' => false, 'message' => "Solo hay {$disponibles} unidades disponibles para cancelar"]);
        exit;
    }
    
    // Verificar que hay unidades disponibles para cancelar
    if ($disponibles <= 0) {
        if ($preparado > 0) {
            echo json_encode(['success' => false, 'message' => 'No se pueden cancelar productos que ya han sido preparados. Contacta al administrador.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No hay unidades disponibles para cancelar.']);
        }
        exit;
    }
    
    // Verificar configuración de email
    $emailSender = new EmailSender($pdo);
    if (!$emailSender->emailConfigurado()) {
        echo json_encode(['success' => false, 'message' => 'No hay emails de administradores configurados para autorización']);
        exit;
    }
    
    // Generar código PIN único de 6 dígitos
    do {
        $codigo_pin = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT id FROM codigos_cancelacion WHERE codigo = ? AND usado = 0 AND fecha_expiracion > NOW()");
        $stmt->execute([$codigo_pin]);
    } while ($stmt->fetch()); // Repetir hasta encontrar un código único
    
    // Obtener tiempo de expiración configurado (convertir segundos a minutos)
    $tiempo_expiracion_segundos = $config->tiempoExpiracionPIN();
    $tiempo_expiracion = $tiempo_expiracion_segundos / 60;
    
    // Guardar en base de datos con tiempo de expiración personalizado
    $stmt = $pdo->prepare("
        INSERT INTO codigos_cancelacion (codigo, orden_id, producto_id, cantidad_solicitada, solicitado_por, razon, fecha_expiracion) 
        VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))
    ");
    $stmt->execute([$codigo_pin, $orden_id, $producto_id, $cantidad_cancelar, $usuario_id, $razon, $tiempo_expiracion]);
    
    // NUEVO: Actualizar el campo pendiente_cancelacion en orden_productos
    if ($orden_producto_id) {
        // Usar el ID específico del producto en la orden
        $stmt = $pdo->prepare("UPDATE orden_productos SET pendiente_cancelacion = pendiente_cancelacion + ? WHERE id = ?");
        $stmt->execute([$cantidad_cancelar, $orden_producto_id]);
    } else {
        // Método fallback: buscar por orden_id y producto_id
        $stmt = $pdo->prepare("UPDATE orden_productos SET pendiente_cancelacion = pendiente_cancelacion + ? WHERE orden_id = ? AND producto_id = ? LIMIT 1");
        $stmt->execute([$cantidad_cancelar, $orden_id, $producto_id]);
    }
    
    // Obtener datos del producto para el mensaje
    $stmt = $pdo->prepare("SELECT nombre FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();
    
    // Obtener datos de la mesa
    $stmt = $pdo->prepare("SELECT m.nombre FROM mesas m JOIN ordenes o ON m.id = o.mesa_id WHERE o.id = ?");
    $stmt->execute([$orden_id]);
    $mesa = $stmt->fetch();
    
    // Obtener nombre de la empresa
    $empresa = $config->obtener('empresa_nombre', 'Restaurant');
    
    // Crear mensaje de log simplificado
    $producto_nombre = $producto['nombre'] ?? 'N/A';
    $mesa_nombre = $mesa['nombre'] ?? 'N/A';
    $mensaje_log = "🚨 {$empresa} - AUTORIZACIÓN REQUERIDA: Cancelación de {$producto_nombre} en {$mesa_nombre}. PIN: {$codigo_pin}";
    
    // Enviar PIN por email
    $resultado_envio = $emailSender->enviarPinCancelacion(
        $orden_id,
        $producto['nombre'] ?? 'N/A',
        $mesa['nombre'] ?? 'N/A',
        $codigo_pin,
        $tiempo_expiracion,
        $razon
    );
    
    $emails_enviados = $resultado_envio['emails_enviados'] ?? 0;
    $total_emails = $resultado_envio['total_emails'] ?? 0;
    
    // Registrar en historial
    $stmt = $pdo->prepare("
        INSERT INTO historial_ordenes (orden_id, accion, detalle, usuario_id) 
        VALUES (?, 'SOLICITUD_CANCELACION', ?, ?)
    ");
    $detalle = "Solicitud de cancelación para producto: " . ($producto['nombre'] ?? 'N/A') . 
               ". Cantidad: {$cantidad_cancelar} unidad(es). PIN: {$codigo_pin}. Enviado por email a {$emails_enviados}/{$total_emails} administrador(es). Razón: {$razon}";
    $stmt->execute([$orden_id, $detalle, $usuario_id]);
    
    echo json_encode([
        'success' => $resultado_envio['success'], 
        'message' => $resultado_envio['success'] ? 
            "Solicitud de cancelación enviada para {$cantidad_cancelar} unidad(es)" : 
            $resultado_envio['message'],
        'codigo_id' => $pdo->lastInsertId(),
        'cantidad_cancelar' => $cantidad_cancelar,
        'tiempo_expiracion' => $tiempo_expiracion,
        'emails_enviados' => $emails_enviados,
        'total_emails' => $total_emails,
        'metodo' => 'email',
        'pin' => $codigo_pin  // Para propósitos de testing
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
