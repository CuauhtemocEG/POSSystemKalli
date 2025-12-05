<?php
// Headers anti-caché
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json; charset=utf-8');

require_once '../../conexion.php';
$pdo = conexion();

$orden_id = intval($_GET['orden_id'] ?? 0);
if (!$orden_id) {
    echo json_encode([
        'items'=>[], 
        'subtotal'=>0, 
        'descuento'=>0, 
        'impuestos'=>0, 
        'total'=>0,
        'mesero_nombre' => null
    ]);
    exit;
}

// Obtener información de la orden y el mesero
$stmtOrden = $pdo->prepare("
    SELECT o.*, u.nombre_completo as mesero_nombre 
    FROM ordenes o
    LEFT JOIN usuarios u ON o.usuario_id = u.id
    WHERE o.id = ?
");
$stmtOrden->execute([$orden_id]);
$ordenInfo = $stmtOrden->fetch(PDO::FETCH_ASSOC);

// Verificar si la orden existe
if (!$ordenInfo) {
    echo json_encode([
        'error' => 'orden_no_encontrada',
        'orden_cerrada' => true,
        'items' => [],
        'subtotal' => 0,
        'total' => 0
    ]);
    exit;
}

// Verificar si la orden está cerrada
if ($ordenInfo['estado'] !== 'abierta') {
    echo json_encode([
        'orden_cerrada' => true,
        'estado' => $ordenInfo['estado'],
        'items' => [],
        'subtotal' => 0,
        'total' => 0
    ]);
    exit;
}

$mesero_nombre = 'Sin asignar';
if ($ordenInfo && !empty($ordenInfo['mesero_nombre'])) {
    $mesero_nombre = trim($ordenInfo['mesero_nombre']);
}

$stmt = $pdo->prepare("
    SELECT 
        op.id,
        op.producto_id, 
        p.nombre, 
        op.cantidad,
        COALESCE(op.preparado, 0) as preparado,
        COALESCE(op.cancelado, 0) as cancelado,
        COALESCE(op.pendiente_cancelacion, 0) as pendiente_cancelacion,
        COALESCE(op.item_index, 1) as item_index,
        p.precio,
        (op.cantidad * p.precio) as subtotal_item
    FROM orden_productos op
    JOIN productos p ON op.producto_id = p.id
    WHERE op.orden_id = ? AND op.estado != 'eliminado'
    ORDER BY op.id
");
$stmt->execute([$orden_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
$subtotal = 0;
$total_cancelado = 0;

foreach ($productos as $producto) {
    $cantidad = intval($producto['cantidad']);
    $preparado = intval($producto['preparado']);
    $cancelado = intval($producto['cancelado']);
    $pendiente_cancelacion = intval($producto['pendiente_cancelacion']);
    $precio = floatval($producto['precio']);
    
    // Calcular totales
    $cantidad_activa = $cantidad - $cancelado - $pendiente_cancelacion;
    $subtotal_producto_activo = $cantidad_activa * $precio;
    $cancelado_monto = $cancelado * $precio;
    
    if ($cancelado > 0) {
        $total_cancelado += $cancelado_monto;
    }
    
    // Solo agregar al subtotal los productos activos (no cancelados ni pendientes de cancelación)
    if ($cantidad_activa > 0) {
        $subtotal += $subtotal_producto_activo;
    }
    
    // Obtener variedades de este producto específico usando item_index
    $variedades = [];
    $item_index = $producto['item_index'] ?? 1; // Usar item_index del producto
    
    $stmtVariedades = $pdo->prepare("
        SELECT grupo_nombre, opcion_nombre, precio_adicional
        FROM orden_producto_variedades
        WHERE orden_id = ? AND producto_id = ? AND item_index = ?
        ORDER BY id
    ");
    $stmtVariedades->execute([$orden_id, $producto['producto_id'], $item_index]);
    $variedades = $stmtVariedades->fetchAll(PDO::FETCH_ASSOC);
    
    $items[] = [
        'id' => $producto['id'],
        'producto_id' => $producto['producto_id'],
        'nombre' => $producto['nombre'],
        'cantidad' => $cantidad,
        'preparado' => $preparado,
        'cancelado' => $cancelado,
        'pendiente_cancelacion' => $pendiente_cancelacion,
        'precio' => $precio,
        'subtotal' => $subtotal_producto_activo, // Solo el subtotal de productos activos
        'item_index' => $item_index, // Incluir item_index
        'variedades' => $variedades // Incluir variedades específicas de este item
    ];
}

$descuento = 0;
$impuestos = 0;
$total = $subtotal - $descuento + $impuestos;

echo json_encode([
    'items' => $items,
    'subtotal' => $subtotal,
    'descuento' => $descuento,
    'impuestos' => $impuestos,
    'total' => $total,
    'total_cancelado' => $total_cancelado,
    'mesero_nombre' => $mesero_nombre,
    'productos_cancelados' => array_filter($items, function($item) { 
        return intval($item['cancelado']) > 0; 
    })
]);
?>