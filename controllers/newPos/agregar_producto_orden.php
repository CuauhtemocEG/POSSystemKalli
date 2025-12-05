<?php
require_once '../../auth-check.php'; // Para obtener getUserInfo()
require_once '../../conexion.php';
$pdo = conexion();

// Obtener información del usuario actual
$userInfo = getUserInfo();
$usuario_id = $userInfo['id'] ?? 1; // Usar ID 1 como fallback si no hay usuario

$producto_id = intval($_POST['producto_id'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 1);
$orden_id = intval($_POST['orden_id'] ?? 0);
$variedades_json = $_POST['variedades'] ?? null; // JSON de variedades seleccionadas
$nota_adicional = trim($_POST['nota_adicional'] ?? ''); // Nota adicional del cliente

if ($cantidad < 1 || !$producto_id || !$orden_id) {
    echo json_encode(['status'=>'error', 'msg'=>'Datos incompletos']);
    exit;
}

// Función para actualizar el total de la orden
function actualizarTotalOrden($pdo, $orden_id) {
    try {
        // Intentar con campo cancelado
        $total_query = $pdo->prepare("
            SELECT SUM(op.cantidad * p.precio) as total
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            WHERE op.orden_id = ? AND op.cancelado = 0
        ");
        $total_query->execute([$orden_id]);
        $total = $total_query->fetchColumn() ?? 0;
    } catch (Exception $e) {
        // Sin campo cancelado
        $total_query = $pdo->prepare("
            SELECT SUM(op.cantidad * p.precio) as total
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            WHERE op.orden_id = ?
        ");
        $total_query->execute([$orden_id]);
        $total = $total_query->fetchColumn() ?? 0;
    }
    
    $update_orden = $pdo->prepare("UPDATE ordenes SET total = ? WHERE id = ?");
    $update_orden->execute([$total, $orden_id]);
    
    return $total;
}

try {
    // Verifica si el producto existe
    $stmt = $pdo->prepare("SELECT id, nombre, precio FROM productos WHERE id=?");
    $stmt->execute([$producto_id]);
    $prod = $stmt->fetch();

    if (!$prod) {
        echo json_encode(['status'=>'error', 'msg'=>'Producto no encontrado']);
        exit;
    }

    // Parsear variedades si existen
    $variedades = null;
    if ($variedades_json) {
        $variedades = json_decode($variedades_json, true);
    }
    
    // Si el producto tiene variedades, NO agrupar - siempre crear nuevo registro
    // para mantener las variedades separadas por cada item
    if ($variedades && count($variedades) > 0) {
        // Producto con variedades - SIEMPRE crear nuevo registro
        
        // Calcular item_index ANTES de insertar (obtener el próximo número secuencial)
        $count_stmt = $pdo->prepare("SELECT COALESCE(MAX(item_index), 0) + 1 as next_index 
                                      FROM orden_productos 
                                      WHERE orden_id = ? AND producto_id = ?");
        $count_stmt->execute([$orden_id, $producto_id]);
        $item_index = $count_stmt->fetchColumn();
        
        try {
            $stmt = $pdo->prepare("INSERT INTO orden_productos (orden_id, producto_id, cantidad, item_index, preparado, cancelado, agregado_por_usuario_id, nota_adicional) VALUES (?, ?, ?, ?, 0, 0, ?, ?)");
            $stmt->execute([$orden_id, $producto_id, $cantidad, $item_index, $usuario_id, $nota_adicional ?: null]);
        } catch (Exception $e) {
            // Si falla (por ejemplo, si la columna nota_adicional no existe), insertar solo campos básicos
            $stmt = $pdo->prepare("INSERT INTO orden_productos (orden_id, producto_id, cantidad, item_index, preparado, cancelado, agregado_por_usuario_id) VALUES (?, ?, ?, ?, 0, 0, ?)");
            $stmt->execute([$orden_id, $producto_id, $cantidad, $item_index, $usuario_id]);
        }
        
        $orden_producto_id = $pdo->lastInsertId();
        
        // Guardar las variedades seleccionadas con el item_index calculado
        foreach ($variedades as $variedad) {
            $stmt = $pdo->prepare("
                INSERT INTO orden_producto_variedades 
                (orden_id, producto_id, item_index, grupo_id, opcion_id, grupo_nombre, opcion_nombre, precio_adicional) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orden_id,
                $producto_id,
                $item_index,  // Usar el item_index calculado
                $variedad['grupo_id'],
                $variedad['opcion_id'],
                $variedad['grupo_nombre'],
                $variedad['opcion_nombre'],
                $variedad['precio_adicional'] ?? 0
            ]);
        }
    } else {
        // Producto SIN variedades - comportamiento original (agrupar si existe)
        // Busca si ya existe en la orden Y NO está cancelado
        $stmt = $pdo->prepare("
            SELECT id, cantidad, COALESCE(cancelado, 0) as cancelado 
            FROM orden_productos 
            WHERE orden_id=? AND producto_id=? AND COALESCE(cancelado, 0) = 0
        ");
        $stmt->execute([$orden_id, $producto_id]);
        $item = $stmt->fetch();

        if ($item) {
            // Producto existe y NO está cancelado - actualizar cantidad
            $nuevo = $item['cantidad'] + $cantidad;
            $pdo->prepare("UPDATE orden_productos SET cantidad=? WHERE id=?")
                ->execute([$nuevo, $item['id']]);
        } else {
            // Producto no existe O está cancelado - crear nuevo registro con usuario
            try {
                $pdo->prepare("INSERT INTO orden_productos (orden_id, producto_id, cantidad, preparado, cancelado, agregado_por_usuario_id) VALUES (?, ?, ?, 0, 0, ?)")
                    ->execute([$orden_id, $producto_id, $cantidad, $usuario_id]);
            } catch (Exception $e) {
                // Si falla, insertar solo campos básicos
                $pdo->prepare("INSERT INTO orden_productos (orden_id, producto_id, cantidad) VALUES (?, ?, ?)")
                    ->execute([$orden_id, $producto_id, $cantidad]);
            }
        }
    }

    // Actualizar el total de la orden
    $total = actualizarTotalOrden($pdo, $orden_id);

    echo json_encode(['status'=>'ok', 'total' => $total]);
    
} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'msg'=>'Error al agregar producto: ' . $e->getMessage()]);
}
?>