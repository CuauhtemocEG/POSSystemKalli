<?php
/**
 * Products API Controller
 * Provides JSON endpoints for product data
 */

// Suprimir warnings y notices para respuestas JSON limpias
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Headers anti-caché
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Determine correct path to conexion.php
$base_path = dirname(dirname(__DIR__));
if (file_exists($base_path . '/auth-check.php')) {
    require_once $base_path . '/auth-check.php';
} else if (file_exists('../../auth-check.php')) {
    require_once '../../auth-check.php';
} else {
    // Fallback connection
    if (file_exists($base_path . '/conexion.php')) {
        require_once $base_path . '/conexion.php';
    } else if (file_exists('../../conexion.php')) {
        require_once '../../conexion.php';
    } else {
        die(json_encode(['success' => false, 'message' => 'Database connection not found']));
    }
}

// Start session for user verification if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify user authentication - Should be set by auth-check.php
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'No autorizado']));
}

$userId = $_SESSION['user_data']['user_id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? 1;

$pdo = conexion();

/**
 * Send JSON response and exit
 */
function sendResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_product':
            $id = intval($_GET['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('ID de producto requerido');
            }
            
            $stmt = $pdo->prepare("
                SELECT p.*, t.nombre as categoria_nombre 
                FROM productos p 
                LEFT JOIN type t ON p.type = t.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                sendResponse(true, 'Producto encontrado', ['product' => $product]);
            } else {
                throw new Exception('Producto no encontrado');
            }
            break;

        case 'get_products':
            $limit = intval($_GET['limit'] ?? 1000); // Aumentado a 1000 para mostrar todos los productos
            $offset = intval($_GET['offset'] ?? 0);
            $search = trim($_GET['search'] ?? '');
            $category = intval($_GET['category'] ?? 0);
            
            $sql = "
                SELECT p.*, t.nombre as categoria_nombre 
                FROM productos p 
                LEFT JOIN type t ON p.type = t.id 
                WHERE 1=1
            ";
            $params = [];
            
            // Add search filter
            if (!empty($search)) {
                $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            // Add category filter
            if ($category > 0) {
                $sql .= " AND p.type = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY p.nombre ASC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countSql = "SELECT COUNT(*) FROM productos p WHERE 1=1";
            $countParams = [];
            
            if (!empty($search)) {
                $countSql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
                $countParams[] = $searchParam;
                $countParams[] = $searchParam;
            }
            
            if ($category > 0) {
                $countSql .= " AND p.type = ?";
                $countParams[] = $category;
            }
            
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetchColumn();
            
            sendResponse(true, 'Productos obtenidos', [
                'products' => $products,
                'total' => intval($total),
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;

        case 'get_categories':
            $stmt = $pdo->query("SELECT id, nombre FROM type ORDER BY nombre");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse(true, 'Categorías obtenidas', ['categories' => $categories]);
            break;

        case 'search_products':
            $query = trim($_GET['q'] ?? '');
            
            if (empty($query)) {
                throw new Exception('Término de búsqueda requerido');
            }
            
            $stmt = $pdo->prepare("
                SELECT p.id, p.nombre, p.precio, p.imagen, t.nombre as categoria_nombre
                FROM productos p 
                LEFT JOIN type t ON p.type = t.id 
                WHERE p.nombre LIKE ? OR p.descripcion LIKE ?
                ORDER BY p.nombre ASC
                LIMIT 20
            ");
            
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse(true, 'Búsqueda completada', ['products' => $products]);
            break;

        case 'get_stats':
            // Get product statistics
            $stats = [];
            
            // Total products
            $stmt = $pdo->query("SELECT COUNT(*) FROM productos");
            $stats['total_products'] = intval($stmt->fetchColumn());
            
            // Total categories
            $stmt = $pdo->query("SELECT COUNT(*) FROM type");
            $stats['total_categories'] = intval($stmt->fetchColumn());
            
            // Products by category
            $stmt = $pdo->query("
                SELECT t.nombre as categoria, COUNT(p.id) as cantidad
                FROM type t
                LEFT JOIN productos p ON t.id = p.type
                GROUP BY t.id, t.nombre
                ORDER BY cantidad DESC
            ");
            $stats['products_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Price statistics
            $stmt = $pdo->query("
                SELECT 
                    MIN(precio) as min_price,
                    MAX(precio) as max_price,
                    AVG(precio) as avg_price,
                    COUNT(*) as total
                FROM productos
            ");
            $priceStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['price_stats'] = [
                'min_price' => floatval($priceStats['min_price']),
                'max_price' => floatval($priceStats['max_price']),
                'avg_price' => round(floatval($priceStats['avg_price']), 2),
                'total' => intval($priceStats['total'])
            ];
            
            sendResponse(true, 'Estadísticas obtenidas', ['stats' => $stats]);
            break;

        case 'validate_product':
            $nombre = trim($_POST['nombre'] ?? '');
            $id = intval($_POST['id'] ?? 0);
            
            if (empty($nombre)) {
                throw new Exception('Nombre de producto requerido');
            }
            
            $sql = "SELECT id FROM productos WHERE nombre = ?";
            $params = [$nombre];
            
            // Exclude current product if editing
            if ($id > 0) {
                $sql .= " AND id != ?";
                $params[] = $id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                sendResponse(false, 'Ya existe un producto con ese nombre', ['field' => 'nombre']);
            } else {
                sendResponse(true, 'Nombre de producto disponible');
            }
            break;

        case 'get_variedades':
            $productoId = intval($_GET['producto_id'] ?? 0);
            
            if (!$productoId) {
                throw new Exception('ID de producto requerido');
            }
            
            // Obtener grupos de variedades
            $stmtGrupos = $pdo->prepare("
                SELECT id, nombre, obligatorio, orden 
                FROM producto_variedad_grupos 
                WHERE producto_id = ? AND activo = 1 
                ORDER BY orden ASC
            ");
            $stmtGrupos->execute([$productoId]);
            $grupos = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);
            
            // Para cada grupo, obtener sus opciones
            foreach ($grupos as &$grupo) {
                $stmtOpciones = $pdo->prepare("
                    SELECT id, nombre, precio_adicional, orden 
                    FROM producto_variedad_opciones 
                    WHERE grupo_id = ? AND activo = 1 
                    ORDER BY orden ASC
                ");
                $stmtOpciones->execute([$grupo['id']]);
                $grupo['opciones'] = $stmtOpciones->fetchAll(PDO::FETCH_ASSOC);
            }
            
            sendResponse(true, 'Variedades obtenidas', ['variedades' => $grupos]);
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    error_log("Products API Error: " . $e->getMessage());
    http_response_code(400);
    sendResponse(false, $e->getMessage());
}
?>
