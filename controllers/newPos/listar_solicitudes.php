<?php
require_once '../../conexion.php';
header('Content-Type: application/json');

try {
    $pdo = conexion();
    
    // Obtener solicitudes pendientes
    $stmt = $pdo->prepare("
        SELECT c.*, p.nombre as producto_nombre, m.nombre as mesa_nombre, 
               u.nombre_completo as solicitante, o.codigo as orden_codigo,
               TIMESTAMPDIFF(MINUTE, c.fecha_creacion, NOW()) as minutos_transcurridos
        FROM codigos_cancelacion c
        JOIN productos p ON c.producto_id = p.id
        JOIN ordenes o ON c.orden_id = o.id
        JOIN mesas m ON o.mesa_id = m.id
        JOIN usuarios u ON c.solicitado_por = u.id
        WHERE c.usado = 0 AND c.fecha_expiracion > NOW()
        ORDER BY c.fecha_creacion DESC
    ");
    
    $stmt->execute();
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'solicitudes' => $solicitudes,
        'total' => count($solicitudes)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener solicitudes: ' . $e->getMessage(),
        'solicitudes' => []
    ]);
}
?>
