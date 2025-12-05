<?php
/**
 * Script de limpieza para corregir productos que quedaron con pendiente_cancelacion
 * después de expirar códigos
 * 
 * Ejecutar una sola vez para limpiar datos huérfanos
 */

require_once __DIR__ . '/../../auth-check.php';
require_once __DIR__ . '/../../conexion.php';

// Solo administradores pueden ejecutar este script
if (!hasPermission('configuracion', 'editar')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Acceso denegado']));
}

header('Content-Type: application/json');

try {
    $pdo = conexion();
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // 1. Buscar productos con pendiente_cancelacion pero sin códigos activos
    $stmt = $pdo->prepare("
        SELECT 
            op.orden_id,
            op.producto_id,
            op.pendiente_cancelacion,
            p.nombre as producto_nombre,
            o.codigo as orden_codigo,
            m.nombre as mesa_nombre,
            (SELECT COUNT(*) 
             FROM codigos_cancelacion cc 
             WHERE cc.orden_id = op.orden_id 
             AND cc.producto_id = op.producto_id 
             AND cc.usado = 0) as codigos_activos
        FROM orden_productos op
        JOIN productos p ON op.producto_id = p.id
        JOIN ordenes o ON op.orden_id = o.id
        JOIN mesas m ON o.mesa_id = m.id
        WHERE op.pendiente_cancelacion > 0
    ");
    
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $corregidos = 0;
    $detalles = [];
    
    foreach ($productos as $prod) {
        // Si no tiene códigos activos, limpiar pendiente_cancelacion
        if ($prod['codigos_activos'] == 0) {
            $stmtUpdate = $pdo->prepare("
                UPDATE orden_productos
                SET pendiente_cancelacion = 0
                WHERE orden_id = ? AND producto_id = ?
            ");
            
            $stmtUpdate->execute([
                $prod['orden_id'],
                $prod['producto_id']
            ]);
            
            $corregidos++;
            $detalles[] = [
                'orden' => $prod['orden_codigo'],
                'mesa' => $prod['mesa_nombre'],
                'producto' => $prod['producto_nombre'],
                'cantidad_limpiada' => $prod['pendiente_cancelacion']
            ];
        }
    }
    
    // Commit
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'productos_corregidos' => $corregidos,
        'total_revisados' => count($productos),
        'detalles' => $detalles,
        'mensaje' => $corregidos > 0 
            ? "$corregidos producto(s) corregido(s). Pendientes de cancelación limpiados."
            : "No se encontraron productos con pendientes de cancelación huérfanos.",
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al limpiar pendientes: ' . $e->getMessage()
    ]);
}
