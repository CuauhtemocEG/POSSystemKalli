<?php
/**
 * Expirar códigos de cancelación automáticamente después de 10 minutos
 * Este script puede ejecutarse manualmente o programarse con cron
 */

require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json');

try {
    $pdo = conexion();
    
    // Buscar códigos expirados (más de 10 minutos y no usados)
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.codigo,
            c.orden_id,
            c.producto_id,
            c.cantidad_solicitada,
            p.nombre as producto_nombre,
            m.nombre as mesa_nombre,
            o.codigo as orden_codigo,
            TIMESTAMPDIFF(MINUTE, c.fecha_creacion, NOW()) as minutos_transcurridos
        FROM codigos_cancelacion c
        JOIN productos p ON c.producto_id = p.id
        JOIN ordenes o ON c.orden_id = o.id
        JOIN mesas m ON o.mesa_id = m.id
        WHERE c.usado = 0 
        AND c.fecha_expiracion < NOW()
        AND TIMESTAMPDIFF(MINUTE, c.fecha_creacion, NOW()) >= 10
    ");
    
    $stmt->execute();
    $codigosExpirados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $expirados = 0;
    $revertidos = [];
    
    foreach ($codigosExpirados as $codigo) {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        try {
            // 1. Revertir cancelación y limpiar pendiente_cancelacion
            $stmtRevertir = $pdo->prepare("
                UPDATE orden_productos 
                SET cancelado = GREATEST(0, cancelado - ?),
                    pendiente_cancelacion = GREATEST(0, pendiente_cancelacion - ?)
                WHERE orden_id = ? 
                AND producto_id = ?
            ");
            
            $stmtRevertir->execute([
                $codigo['cantidad_solicitada'],
                $codigo['cantidad_solicitada'],
                $codigo['orden_id'],
                $codigo['producto_id']
            ]);
            
            // 2. Marcar el código como usado (expirado)
            $stmtMarcar = $pdo->prepare("
                UPDATE codigos_cancelacion 
                SET usado = 1
                WHERE id = ?
            ");
            
            $stmtMarcar->execute([$codigo['id']]);
            
            // 3. Recalcular total de la orden
            $stmtTotal = $pdo->prepare("
                UPDATE ordenes 
                SET total = (
                    SELECT COALESCE(SUM(
                        (op.cantidad - op.cancelado) * op.precio_unitario
                    ), 0)
                    FROM orden_productos op 
                    WHERE op.orden_id = ?
                )
                WHERE id = ?
            ");
            
            $stmtTotal->execute([$codigo['orden_id'], $codigo['orden_id']]);
            
            // Commit de la transacción
            $pdo->commit();
            
            $expirados++;
            $revertidos[] = [
                'codigo' => $codigo['codigo'],
                'producto' => $codigo['producto_nombre'],
                'mesa' => $codigo['mesa_nombre'],
                'orden' => $codigo['orden_codigo'],
                'cantidad' => $codigo['cantidad_solicitada'],
                'minutos' => $codigo['minutos_transcurridos']
            ];
            
        } catch (Exception $e) {
            // Rollback en caso de error
            $pdo->rollBack();
            throw $e;
        }
    }
    
    echo json_encode([
        'success' => true,
        'expirados' => $expirados,
        'detalles' => $revertidos,
        'mensaje' => $expirados > 0 
            ? "$expirados código(s) expirado(s) y productos revertidos a pendiente"
            : "No hay códigos expirados para procesar",
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
