<?php
/**
 * API: Estado de Mesas
 * Retorna el estado actual de todas las mesas en formato JSON
 * 
 * Uso:
 * GET /api/estado_mesas.php
 * 
 * Respuesta:
 * {
 *   "success": true,
 *   "timestamp": 1700000000,
 *   "estadisticas": {
 *     "total": 12,
 *     "ocupadas": 3,
 *     "disponibles": 9
 *   },
 *   "mesas": [
 *     {
 *       "id": 1,
 *       "nombre": "Mesa 1",
 *       "estado": "ocupada|libre",
 *       "ordenes_abiertas": 1,
 *       "mesero_nombre": "Juan Pérez"
 *     }
 *   ]
 * }
 */

// Headers para JSON y prevenir caché
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

try {
    // Incluir conexión
    require_once __DIR__ . '/../conexion.php';
    $pdo = conexion();
    
    // Obtener todas las mesas con su estado
    $query = "
        SELECT 
            m.id,
            m.nombre,
            m.descripcion,
            (SELECT COUNT(*) 
             FROM ordenes o 
             WHERE o.mesa_id = m.id AND o.estado = 'abierta') as ordenes_abiertas,
            (SELECT u.nombre_completo
             FROM ordenes o 
             LEFT JOIN usuarios u ON o.usuario_id = u.id
             WHERE o.mesa_id = m.id AND o.estado = 'abierta' 
             LIMIT 1) as mesero_nombre,
            (SELECT COUNT(*) 
             FROM orden_items oi
             INNER JOIN ordenes o ON oi.orden_id = o.id
             WHERE o.mesa_id = m.id AND o.estado = 'abierta') as items_en_orden
        FROM mesas m
        ORDER BY m.nombre
    ";
    
    $stmt = $pdo->query($query);
    $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar datos de mesas
    $mesasProcesadas = [];
    $totalOcupadas = 0;
    
    foreach ($mesas as $mesa) {
        $ordenesAbiertas = intval($mesa['ordenes_abiertas']);
        $estado = $ordenesAbiertas > 0 ? 'ocupada' : 'libre';
        
        if ($estado === 'ocupada') {
            $totalOcupadas++;
        }
        
        $mesasProcesadas[] = [
            'id' => intval($mesa['id']),
            'nombre' => $mesa['nombre'],
            'descripcion' => $mesa['descripcion'] ?? '',
            'estado' => $estado,
            'ordenes_abiertas' => $ordenesAbiertas,
            'items_en_orden' => intval($mesa['items_en_orden']),
            'mesero_nombre' => $mesa['mesero_nombre'] ? trim($mesa['mesero_nombre']) : null
        ];
    }
    
    // Calcular estadísticas
    $totalMesas = count($mesas);
    $disponibles = $totalMesas - $totalOcupadas;
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'estadisticas' => [
            'total' => $totalMesas,
            'ocupadas' => $totalOcupadas,
            'disponibles' => $disponibles
        ],
        'mesas' => $mesasProcesadas
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
