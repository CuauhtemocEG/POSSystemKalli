<?php
require_once '../config.php';
require_once '../conexion.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Acceso denegado');
}

try {
    $pdo = conexion();
    
    // Obtener tipos para la plantilla
    $tipos = $pdo->query("SELECT id, nombre FROM type ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener algunos productos existentes como ejemplo
    $productos_ejemplo = $pdo->query("
        SELECT p.id, p.nombre, p.descripcion, p.precio, t.nombre as tipo, p.imagen
        FROM productos p
        LEFT JOIN type t ON p.type = t.id
        ORDER BY p.nombre
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Headers para descarga de CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Plantilla_Productos_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0');
    
    // Abrir output para CSV
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (para que Excel abra correctamente los acentos)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Información de la plantilla
    fputcsv($output, ['PLANTILLA DE PRODUCTOS - KALLI JAGUAR POS'], ';');
    fputcsv($output, ['Generado el: ' . date('d/m/Y H:i:s')], ';');
    fputcsv($output, [''], ';'); // Línea vacía
    
    // Instrucciones
    fputcsv($output, ['INSTRUCCIONES:'], ';');
    fputcsv($output, ['1. Para NUEVOS productos: deja el ID vacío o usa "NUEVO"'], ';');
    fputcsv($output, ['2. Para ACTUALIZAR productos: usa el ID existente del producto'], ';');
    fputcsv($output, ['3. El tipo debe coincidir exactamente con los valores permitidos'], ';');
    fputcsv($output, ['4. El precio debe ser un número (ej: 25.50)'], ';');
    fputcsv($output, ['5. La imagen es opcional (nombre del archivo en assets/img/)'], ';');
    fputcsv($output, [''], ';'); // Línea vacía
    
    // Tipos disponibles
    fputcsv($output, ['TIPOS DE PRODUCTOS DISPONIBLES:'], ';');
    foreach ($tipos as $tipo) {
        fputcsv($output, [$tipo['nombre']], ';');
    }
    fputcsv($output, [''], ';'); // Línea vacía
    
    // Encabezados de la plantilla
    fputcsv($output, ['ID', 'NOMBRE', 'DESCRIPCION', 'PRECIO', 'TIPO', 'IMAGEN'], ';');
    
    // Ejemplos con productos existentes
    foreach ($productos_ejemplo as $prod) {
        fputcsv($output, [
            $prod['id'],
            $prod['nombre'],
            $prod['descripcion'] ?? '',
            $prod['precio'],
            $prod['tipo'] ?? '',
            $prod['imagen'] ?? ''
        ], ';');
    }
    
    // Filas vacías para nuevos productos
    for ($i = 0; $i < 20; $i++) {
        fputcsv($output, ['NUEVO', '', '', '', '', ''], ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error al generar la plantilla CSV: ' . $e->getMessage();
}
?>
