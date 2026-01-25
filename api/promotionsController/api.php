<?php
require_once '../../conexion.php';
header('Content-Type: application/json');

$pdo = conexion();

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {
    case 'list':
        listarPromociones($pdo);
        break;
    
    case 'get':
        obtenerPromocion($pdo);
        break;
    
    case 'create':
        crearPromocion($pdo);
        break;
    
    case 'update':
        actualizarPromocion($pdo);
        break;
    
    case 'delete':
        eliminarPromocion($pdo);
        break;
    
    case 'toggle':
        togglePromocion($pdo);
        break;
    
    case 'calcular':
        calcularPromociones($pdo);
        break;
    
    default:
        echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
        break;
}

function listarPromociones($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT p.*,
                   GROUP_CONCAT(DISTINCT pp.producto_id) as productos_ids,
                   GROUP_CONCAT(DISTINCT pc.categoria) as categorias_ids
            FROM promociones p
            LEFT JOIN promocion_productos pp ON p.id = pp.promocion_id
            LEFT JOIN promocion_categorias pc ON p.id = pc.promocion_id
            GROUP BY p.id
            ORDER BY p.prioridad DESC, p.id DESC
        ");
        
        $promociones = $stmt->fetchAll();
        
        echo json_encode([
            'status' => 'ok',
            'data' => $promociones
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
}

function obtenerPromocion($pdo) {
    try {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            throw new Exception('ID de promoción requerido');
        }
        
        // Obtener promoción
        $stmt = $pdo->prepare("SELECT * FROM promociones WHERE id = ?");
        $stmt->execute([$id]);
        $promocion = $stmt->fetch();
        
        if (!$promocion) {
            throw new Exception('Promoción no encontrada');
        }
        
        // Obtener productos asociados
        $stmt = $pdo->prepare("SELECT producto_id FROM promocion_productos WHERE promocion_id = ?");
        $stmt->execute([$id]);
        $promocion['productos'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Obtener categorías asociadas
        $stmt = $pdo->prepare("SELECT categoria FROM promocion_categorias WHERE promocion_id = ?");
        $stmt->execute([$id]);
        $promocion['categorias'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode([
            'status' => 'ok',
            'data' => $promocion
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
}

function crearPromocion($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        // Validar campos requeridos
        if (empty($data['nombre']) || empty($data['tipo'])) {
            throw new Exception('Nombre y tipo son requeridos');
        }
        
        $pdo->beginTransaction();
        
        // Insertar promoción
        $stmt = $pdo->prepare("
            INSERT INTO promociones 
            (nombre, descripcion, tipo, valor, aplica_a, activa, fecha_inicio, fecha_fin, 
             dias_activos, hora_inicio, hora_fin, prioridad, aplicar_mayor_valor, minimo_productos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Procesar valor: convertir vacío o string vacío a NULL
        $valor = null;
        if (isset($data['valor']) && $data['valor'] !== '' && $data['valor'] !== null) {
            $valor = floatval($data['valor']);
        }
        
        $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['tipo'],
            $valor,
            $data['aplica_a'] ?? 'productos',
            isset($data['activa']) ? intval($data['activa']) : 1,
            $data['fecha_inicio'] ?? null,
            $data['fecha_fin'] ?? null,
            $data['dias_activos'] ?? null,
            $data['hora_inicio'] ?? null,
            $data['hora_fin'] ?? null,
            $data['prioridad'] ?? 0,
            isset($data['aplicar_mayor_valor']) ? intval($data['aplicar_mayor_valor']) : 1,
            $data['minimo_productos'] ?? 2
        ]);
        
        $promocion_id = $pdo->lastInsertId();
        
        // Insertar productos asociados
        if (!empty($data['productos']) && is_array($data['productos'])) {
            $stmt = $pdo->prepare("INSERT INTO promocion_productos (promocion_id, producto_id) VALUES (?, ?)");
            foreach ($data['productos'] as $producto_id) {
                $stmt->execute([$promocion_id, intval($producto_id)]);
            }
        }
        
        // Insertar categorías asociadas
        if (!empty($data['categorias']) && is_array($data['categorias'])) {
            $stmt = $pdo->prepare("INSERT INTO promocion_categorias (promocion_id, categoria) VALUES (?, ?)");
            foreach ($data['categorias'] as $categoria) {
                $stmt->execute([$promocion_id, $categoria]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'status' => 'ok',
            'msg' => 'Promoción creada exitosamente',
            'id' => $promocion_id
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
}

function actualizarPromocion($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $id = intval($data['id'] ?? 0);
        
        if (!$id) {
            throw new Exception('ID de promoción requerido');
        }
        
        $pdo->beginTransaction();
        
        // Actualizar promoción
        $stmt = $pdo->prepare("
            UPDATE promociones SET
                nombre = ?,
                descripcion = ?,
                tipo = ?,
                valor = ?,
                aplica_a = ?,
                activa = ?,
                fecha_inicio = ?,
                fecha_fin = ?,
                dias_activos = ?,
                hora_inicio = ?,
                hora_fin = ?,
                prioridad = ?,
                aplicar_mayor_valor = ?,
                minimo_productos = ?
            WHERE id = ?
        ");
        
        // Procesar valor: convertir vacío o string vacío a NULL
        $valor = null;
        if (isset($data['valor']) && $data['valor'] !== '' && $data['valor'] !== null) {
            $valor = floatval($data['valor']);
        }
        
        $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['tipo'],
            $valor,
            $data['aplica_a'] ?? 'productos',
            isset($data['activa']) ? intval($data['activa']) : 1,
            $data['fecha_inicio'] ?? null,
            $data['fecha_fin'] ?? null,
            $data['dias_activos'] ?? null,
            $data['hora_inicio'] ?? null,
            $data['hora_fin'] ?? null,
            $data['prioridad'] ?? 0,
            isset($data['aplicar_mayor_valor']) ? intval($data['aplicar_mayor_valor']) : 1,
            $data['minimo_productos'] ?? 2,
            $id
        ]);
        
        // Eliminar relaciones anteriores
        $pdo->prepare("DELETE FROM promocion_productos WHERE promocion_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM promocion_categorias WHERE promocion_id = ?")->execute([$id]);
        
        // Insertar nuevas relaciones de productos
        if (!empty($data['productos']) && is_array($data['productos'])) {
            $stmt = $pdo->prepare("INSERT INTO promocion_productos (promocion_id, producto_id) VALUES (?, ?)");
            foreach ($data['productos'] as $producto_id) {
                $stmt->execute([$id, intval($producto_id)]);
            }
        }
        
        // Insertar nuevas relaciones de categorías
        if (!empty($data['categorias']) && is_array($data['categorias'])) {
            $stmt = $pdo->prepare("INSERT INTO promocion_categorias (promocion_id, categoria) VALUES (?, ?)");
            foreach ($data['categorias'] as $categoria) {
                $stmt->execute([$id, $categoria]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'status' => 'ok',
            'msg' => 'Promoción actualizada exitosamente'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
}

function eliminarPromocion($pdo) {
    try {
        $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        
        if (!$id) {
            throw new Exception('ID de promoción requerido');
        }
        
        $stmt = $pdo->prepare("DELETE FROM promociones WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'status' => 'ok',
            'msg' => 'Promoción eliminada exitosamente'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
}

function togglePromocion($pdo) {
    try {
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            throw new Exception('ID de promoción requerido');
        }
        
        $stmt = $pdo->prepare("UPDATE promociones SET activa = NOT activa WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'status' => 'ok',
            'msg' => 'Estado actualizado exitosamente'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
}

function calcularPromociones($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || empty($data['productos'])) {
            throw new Exception('Lista de productos requerida');
        }
        
        $es_personal = $data['es_personal'] ?? false;
        $productos = $data['productos']; // Array de objetos con id, cantidad, precio, categoria
        
        // Obtener promociones activas
        $stmt = $pdo->query("
            SELECT p.*
            FROM promociones p
            WHERE p.activa = 1
            ORDER BY p.prioridad DESC
        ");
        
        $promociones = $stmt->fetchAll();
        
        $descuentos = [];
        $productos_procesados = [];
        
        foreach ($promociones as $promo) {
            // Verificar si es promoción de personal
            if ($promo['tipo'] === 'descuento_personal' && !$es_personal) {
                continue;
            }
            
            // Filtrar productos elegibles
            $productos_elegibles = filtrarProductosElegibles($pdo, $productos, $promo, $productos_procesados);
            
            if (count($productos_elegibles) < $promo['minimo_productos']) {
                continue;
            }
            
            // Calcular descuento según tipo
            $descuento = calcularDescuentoPorTipo($promo, $productos_elegibles);
            
            if ($descuento['monto'] > 0) {
                $descuentos[] = $descuento;
                // Marcar productos como procesados si aplica
                foreach ($descuento['productos_afectados'] as $prod_id) {
                    $productos_procesados[] = $prod_id;
                }
            }
        }
        
        // Calcular totales
        $total_descuentos = array_sum(array_column($descuentos, 'monto'));
        
        echo json_encode([
            'status' => 'ok',
            'descuentos' => $descuentos,
            'total_descuentos' => $total_descuentos
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
}

function filtrarProductosElegibles($pdo, $productos, $promo, $productos_procesados) {
    $elegibles = [];
    
    if ($promo['aplica_a'] === 'todos') {
        // Aplica a todos los productos no procesados
        foreach ($productos as $prod) {
            if (!in_array($prod['id'], $productos_procesados)) {
                $elegibles[] = $prod;
            }
        }
    } elseif ($promo['aplica_a'] === 'productos') {
        // Obtener productos específicos de la promoción
        $stmt = $pdo->prepare("SELECT producto_id FROM promocion_productos WHERE promocion_id = ?");
        $stmt->execute([$promo['id']]);
        $productos_promo = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($productos as $prod) {
            if (in_array($prod['id'], $productos_promo) && !in_array($prod['id'], $productos_procesados)) {
                $elegibles[] = $prod;
            }
        }
    } elseif ($promo['aplica_a'] === 'categorias') {
        // Obtener categorías de la promoción
        $stmt = $pdo->prepare("SELECT categoria FROM promocion_categorias WHERE promocion_id = ?");
        $stmt->execute([$promo['id']]);
        $categorias_promo = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($productos as $prod) {
            if (in_array($prod['categoria'], $categorias_promo) && !in_array($prod['id'], $productos_procesados)) {
                $elegibles[] = $prod;
            }
        }
    }
    
    return $elegibles;
}

function calcularDescuentoPorTipo($promo, $productos) {
    $descuento = [
        'promocion_id' => $promo['id'],
        'nombre' => $promo['nombre'],
        'tipo' => $promo['tipo'],
        'monto' => 0,
        'productos_afectados' => []
    ];
    
    // Ordenar por precio descendente si aplica
    if ($promo['aplicar_mayor_valor']) {
        usort($productos, function($a, $b) {
            return $b['precio'] <=> $a['precio'];
        });
    }
    
    switch ($promo['tipo']) {
        case '2x1':
            // Por cada 2 productos, el de menor valor es gratis
            $grupos = floor(count($productos) / 2);
            for ($i = 0; $i < $grupos; $i++) {
                $idx1 = $i * 2;
                $idx2 = $idx1 + 1;
                if (isset($productos[$idx2])) {
                    // El más barato es gratis
                    $descuento['monto'] += min($productos[$idx1]['precio'], $productos[$idx2]['precio']);
                    $descuento['productos_afectados'][] = $productos[$idx1]['id'];
                    $descuento['productos_afectados'][] = $productos[$idx2]['id'];
                }
            }
            break;
            
        case '3x2':
            // Por cada 3 productos, el de menor valor es gratis
            $grupos = floor(count($productos) / 3);
            for ($i = 0; $i < $grupos; $i++) {
                $idx1 = $i * 3;
                $idx2 = $idx1 + 1;
                $idx3 = $idx1 + 2;
                if (isset($productos[$idx3])) {
                    // El más barato de los 3 es gratis
                    $precio_min = min($productos[$idx1]['precio'], $productos[$idx2]['precio'], $productos[$idx3]['precio']);
                    $descuento['monto'] += $precio_min;
                    $descuento['productos_afectados'][] = $productos[$idx1]['id'];
                    $descuento['productos_afectados'][] = $productos[$idx2]['id'];
                    $descuento['productos_afectados'][] = $productos[$idx3]['id'];
                }
            }
            break;
            
        case 'descuento_porcentaje':
        case 'descuento_personal':
            // Aplicar porcentaje a todos los productos elegibles
            $porcentaje = floatval($promo['valor']);
            foreach ($productos as $prod) {
                $descuento['monto'] += ($prod['precio'] * $prod['cantidad'] * $porcentaje / 100);
                $descuento['productos_afectados'][] = $prod['id'];
            }
            break;
            
        case 'descuento_fijo':
            // Descuento fijo total
            $descuento['monto'] = floatval($promo['valor']);
            foreach ($productos as $prod) {
                $descuento['productos_afectados'][] = $prod['id'];
            }
            break;
    }
    
    return $descuento;
}
?>
