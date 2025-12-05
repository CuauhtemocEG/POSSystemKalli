<?php
session_start();
require_once '../auth-check.php';
require_once '../includes/ConfiguracionSistema.php';
require_once '../conexion.php';

// Verificar que es administrador
if (!hasPermission('configuracion', 'editar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? '';

    $pdo = conexion();
    $config = new ConfiguracionSistema($pdo);
    
    switch ($accion) {
        case 'limpiar_cache':
            // Limpiar cache de configuración
            $config->limpiarCache();
            
            // Limpiar cache de PHP si está disponible
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            $mensaje = 'Cache del sistema limpiado correctamente';
            break;
            
        case 'optimizar_bd':
            // Optimizar tablas principales
            $tablas = ['usuarios', 'productos', 'categorias', 'mesas', 'ordenes', 'orden_productos', 'configuracion'];
            $optimizadas = 0;
            
            foreach ($tablas as $tabla) {
                try {
                    $pdo->exec("OPTIMIZE TABLE $tabla");
                    $optimizadas++;
                } catch (Exception $e) {
                    // Ignorar errores de tablas que no existen
                    continue;
                }
            }
            
            $mensaje = "Base de datos optimizada. $optimizadas tablas procesadas";
            break;
            
        case 'limpiar_sesiones':
            // Limpiar sesiones expiradas de la tabla sesiones
            $table_exists = $pdo->query("SHOW TABLES LIKE 'sesiones'")->rowCount() > 0;
            
            if ($table_exists) {
                $stmt = $pdo->prepare("DELETE FROM sesiones WHERE expires_at < NOW() OR revocado = 1");
                $stmt->execute();
                $eliminadas = $stmt->rowCount();
                $mensaje = "$eliminadas sesiones expiradas/revocadas eliminadas";
            } else {
                $mensaje = "Limpieza completada (tabla sesiones no encontrada)";
            }
            break;
            
        case 'limpiar_todo':
            // Limpiar cache
            $config->limpiarCache();
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            // Limpiar sesiones expiradas
            $table_exists = $pdo->query("SHOW TABLES LIKE 'sesiones'")->rowCount() > 0;
            $eliminadas = 0;
            
            if ($table_exists) {
                $stmt = $pdo->prepare("DELETE FROM sesiones WHERE expires_at < NOW() OR revocado = 1");
                $stmt->execute();
                $eliminadas = $stmt->rowCount();
            }
            
            $mensaje = "Limpieza completa finalizada. Cache limpiado y $eliminadas sesiones eliminadas";
            break;
            
        default:
            throw new Exception('Acción no válida');
    }

    echo json_encode([
        'success' => true,
        'message' => $mensaje
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
