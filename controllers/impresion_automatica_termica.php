<?php
// Verificar si ya hay una sesión activa antes de iniciar una nueva
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../auth-check.php';
require_once '../includes/ConfiguracionSistema.php';
require_once '../conexion.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('No autorizado');
}

try {
    $orden_id = $_GET['orden_id'] ?? '';
    
    if (empty($orden_id)) {
        throw new Exception('ID de orden requerido');
    }

    // Obtener conexión a la base de datos y configuración
    $pdo = conexion();
    $config = new ConfiguracionSistema($pdo);
    $config_sistema = $config->obtenerTodasConfiguraciones();
    
    // Verificar si la impresión automática está habilitada
    $impresion_automatica = ($config_sistema['impresion_automatica'] ?? '0') == '1';
    
    if (!$impresion_automatica) {
        // Si no está habilitada la impresión automática, redirigir al controlador normal
        header("Location: impresion_ticket_termico.php?orden_id=$orden_id");
        exit;
    }
    
    // Generar el PDF térmico
    include_once 'impresion_ticket_termico.php';
    
    // Obtener la ruta del PDF generado (necesitamos modificar el controlador para que devuelva la ruta)
    $rutaPDF = generarTicketTermico($orden_id, true); // true indica que es para impresión automática
    
    if (!$rutaPDF || !file_exists($rutaPDF)) {
        throw new Exception('Error al generar el ticket térmico');
    }
    
    // Obtener configuración de impresión
    $nombreImpresora = $config_sistema['nombre_impresora'] ?? '';
    $metodoImpresion = $config_sistema['metodo_impresion'] ?? 'local';
    $ipImpresora = $config_sistema['ip_impresora'] ?? '';
    $puertoImpresora = $config_sistema['puerto_impresora'] ?? '9100';
    $copiasTicket = intval($config_sistema['copias_ticket'] ?? 1);
    
    if (empty($nombreImpresora)) {
        throw new Exception('Impresora no configurada. Ve a Configuración > Impresoras Térmicas');
    }
    
    $errores = [];
    $exitoso = false;
    
    // Imprimir las copias solicitadas
    for ($copia = 1; $copia <= $copiasTicket; $copia++) {
        try {
            $resultado = enviarAImpresora($rutaPDF, $nombreImpresora, $metodoImpresion, $ipImpresora, $puertoImpresora);
            if ($resultado['success']) {
                $exitoso = true;
            } else {
                $errores[] = "Copia $copia: " . $resultado['message'];
            }
        } catch (Exception $e) {
            $errores[] = "Copia $copia: " . $e->getMessage();
        }
        
        // Pausa pequeña entre copias
        if ($copia < $copiasTicket) {
            usleep(500000); // 0.5 segundos
        }
    }
    
    // Limpiar archivo temporal
    if (file_exists($rutaPDF)) {
        unlink($rutaPDF);
    }
    
    // Mostrar resultado
    if ($exitoso) {
        if (!empty($errores)) {
            $mensaje = "Impresión parcialmente exitosa. Algunas copias fallaron:\n" . implode("\n", $errores);
        } else {
            $mensaje = "Ticket impreso correctamente";
            if ($copiasTicket > 1) {
                $mensaje .= " ($copiasTicket copias)";
            }
        }
        
        // Redirigir de vuelta con mensaje de éxito
        header("Location: ../views/mesa.php?mesa=" . ($_GET['mesa'] ?? '') . "&impresion_exitosa=1&mensaje=" . urlencode($mensaje));
    } else {
        $mensajeError = "Error al imprimir: " . implode(", ", $errores);
        header("Location: ../views/mesa.php?mesa=" . ($_GET['mesa'] ?? '') . "&impresion_error=1&mensaje=" . urlencode($mensajeError));
    }
    
} catch (Exception $e) {
    error_log("Error en impresión automática: " . $e->getMessage());
    header("Location: ../views/mesa.php?mesa=" . ($_GET['mesa'] ?? '') . "&impresion_error=1&mensaje=" . urlencode($e->getMessage()));
}

/**
 * Función para enviar PDF a impresora
 */
function enviarAImpresora($rutaPDF, $impresora, $metodo, $ip = '', $puerto = '9100') {
    try {
        switch ($metodo) {
            case 'local':
                return imprimirLocal($rutaPDF, $impresora);
                
            case 'compartida':
                return imprimirCompartida($rutaPDF, $impresora);
                
            case 'cups':
                return imprimirCUPS($rutaPDF, $impresora);
                
            case 'red':
                if (empty($ip)) {
                    throw new Exception('IP de impresora requerida para impresión en red');
                }
                return imprimirRed($rutaPDF, $ip, $puerto);
                
            default:
                throw new Exception('Método de impresión no válido: ' . $metodo);
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Impresión local (USB/Paralelo)
 */
function imprimirLocal($rutaPDF, $impresora) {
    $os = strtolower(PHP_OS);
    
    if (strpos($os, 'win') !== false) {
        // Windows
        $comando = "print /D:\"$impresora\" \"$rutaPDF\"";
    } elseif (strpos($os, 'darwin') !== false) {
        // macOS
        $comando = "lp -d '$impresora' '$rutaPDF'";
    } else {
        // Linux
        $comando = "lp -d '$impresora' '$rutaPDF'";
    }
    
    $output = shell_exec($comando . ' 2>&1');
    $success = $output !== null && strpos(strtolower($output), 'error') === false;
    
    return [
        'success' => $success,
        'message' => $success ? 'Enviado a impresora local' : 'Error: ' . $output,
        'comando' => $comando
    ];
}

/**
 * Impresión compartida (Windows)
 */
function imprimirCompartida($rutaPDF, $impresora) {
    if (strtolower(substr(PHP_OS, 0, 3)) !== 'win') {
        throw new Exception('Impresión compartida solo disponible en Windows');
    }
    
    $comando = "copy \"$rutaPDF\" \"\\\\$impresora\"";
    $output = shell_exec($comando . ' 2>&1');
    $success = $output !== null && strpos(strtolower($output), 'error') === false;
    
    return [
        'success' => $success,
        'message' => $success ? 'Enviado a impresora compartida' : 'Error: ' . $output,
        'comando' => $comando
    ];
}

/**
 * Impresión CUPS (Linux/macOS)
 */
function imprimirCUPS($rutaPDF, $impresora) {
    $comando = "lp -d '$impresora' -o fit-to-page '$rutaPDF'";
    $output = shell_exec($comando . ' 2>&1');
    $success = $output !== null && strpos(strtolower($output), 'error') === false;
    
    return [
        'success' => $success,
        'message' => $success ? 'Enviado vía CUPS' : 'Error: ' . $output,
        'comando' => $comando
    ];
}

/**
 * Impresión por red (Raw TCP)
 */
function imprimirRed($rutaPDF, $ip, $puerto) {
    $contenido = file_get_contents($rutaPDF);
    if (!$contenido) {
        throw new Exception('No se pudo leer el archivo PDF');
    }
    
    $socket = fsockopen($ip, $puerto, $errno, $errstr, 10);
    if (!$socket) {
        throw new Exception("No se pudo conectar a $ip:$puerto - $errstr ($errno)");
    }
    
    $enviado = fwrite($socket, $contenido);
    fclose($socket);
    
    return [
        'success' => $enviado > 0,
        'message' => $enviado > 0 ? 'Enviado por red' : 'Error al enviar por red',
        'bytes' => $enviado
    ];
}

/**
 * Función modificada para generar ticket térmico y devolver ruta
 */
function generarTicketTermico($orden_id, $paraImpresion = false) {
    // Aquí iría el código del controlador de impresión térmica
    // pero modificado para devolver la ruta del archivo en lugar de enviarlo al navegador
    
    // Por ahora, redirigimos al controlador original
    // Esta función se implementaría completamente en una versión futura
    return false;
}
?>
