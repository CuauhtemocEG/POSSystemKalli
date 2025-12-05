<?php
require_once '../conexion.php';

header('Content-Type: application/json');

try {
    $pdo = conexion();
    
    // Obtener parámetros
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $fechaDesde = $_GET['fecha_desde'] ?? null;
    $fechaHasta = $_GET['fecha_hasta'] ?? null;
    $ordenesPorPagina = 10;
    $offset = ($pagina - 1) * $ordenesPorPagina;
    
    // Construir condición de fecha
    $condicionFecha = "";
    $textoPeriodo = "del día de hoy";
    
    if ($fechaDesde && $fechaHasta) {
        $condicionFecha = "AND DATE(o.creada_en) BETWEEN '$fechaDesde' AND '$fechaHasta'";
        $textoPeriodo = "del " . date('d/m/Y', strtotime($fechaDesde)) . " al " . date('d/m/Y', strtotime($fechaHasta));
    } else {
        $condicionFecha = "AND DATE(o.creada_en) = CURDATE()";
    }
    
    // Contar total de órdenes
    $totalQuery = "
        SELECT COUNT(DISTINCT o.id) as total
        FROM ordenes o
        WHERE o.estado = 'cerrada' $condicionFecha";
    
    $totalOrdenes = $pdo->query($totalQuery)->fetchColumn();
    $totalPaginas = ceil($totalOrdenes / $ordenesPorPagina);
    
    // Obtener órdenes de la página actual
    $ordenesQuery = "
        SELECT 
            o.id,
            o.codigo,
            o.mesa_id,
            m.nombre as mesa,
            o.total,
            o.metodo_pago,
            o.creada_en,
            o.cerrada_en,
            CASE 
                WHEN o.cerrada_en IS NOT NULL THEN
                    TIMESTAMPDIFF(MINUTE, o.creada_en, o.cerrada_en)
                ELSE 
                    NULL
            END as tiempo_total_minutos,
            COUNT(op.id) as productos_count
        FROM ordenes o
        JOIN mesas m ON o.mesa_id = m.id
        LEFT JOIN orden_productos op ON o.id = op.orden_id
        WHERE o.estado = 'cerrada' $condicionFecha
        GROUP BY o.id, o.codigo, o.mesa_id, m.nombre, o.total, o.metodo_pago, o.creada_en, o.cerrada_en
        ORDER BY o.cerrada_en DESC
        LIMIT $ordenesPorPagina OFFSET $offset";
    
    $ordenes = $pdo->query($ordenesQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    // Respuesta JSON
    echo json_encode([
        'success' => true,
        'ordenes' => $ordenes,
        'paginacion' => [
            'pagina_actual' => $pagina,
            'total_paginas' => $totalPaginas,
            'total_ordenes' => $totalOrdenes,
            'ordenes_por_pagina' => $ordenesPorPagina,
            'desde' => (($pagina - 1) * $ordenesPorPagina) + 1,
            'hasta' => min($pagina * $ordenesPorPagina, $totalOrdenes)
        ],
        'periodo' => $textoPeriodo
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener órdenes: ' . $e->getMessage()
    ]);
}
?>
