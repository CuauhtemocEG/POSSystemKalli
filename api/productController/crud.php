<?php
/**
 * Product CRUD Controller
 * Handles Create, Read, Update, Delete operations for products
 */

// Suprimir warnings y notices para respuestas JSON limpias
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

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
        die('Error: No se puede encontrar conexion.php');
    }
}

// Check for POST size errors before processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
    $maxPostSize = ini_get('post_max_size');
    $maxPostBytes = parseSize($maxPostSize);
    
    if ($contentLength > $maxPostBytes) {
        http_response_code(413); // Payload Too Large
        die(json_encode([
            'success' => false, 
            'message' => "Archivo demasiado grande. Tamaño: " . formatBytes($contentLength) . ". Máximo permitido: $maxPostSize",
            'error_code' => 'FILE_TOO_LARGE'
        ]));
    }
    
    // Check if POST data was truncated
    if (empty($_POST) && empty($_FILES) && $contentLength > 0) {
        http_response_code(413);
        die(json_encode([
            'success' => false,
            'message' => "El archivo excede los límites del servidor (max: $maxPostSize)",
            'error_code' => 'POST_TRUNCATED'
        ]));
    }
}

// Helper functions for size conversion
function parseSize($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    return round($size);
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
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

// Verify admin permissions
function isAdmin($userId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT u.rol_id, r.nombre as rol_nombre 
        FROM usuarios u 
        LEFT JOIN roles r ON u.rol_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user && ($user['rol_id'] == 1 || $user['rol_nombre'] === 'administrador');
}

// Check admin permissions
if (!isAdmin($userId, $pdo)) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Permisos insuficientes']));
}

/**
 * Detect if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
           (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
}

/**
 * Send response - JSON for AJAX requests, redirect for form submissions
 */
function sendResponse($message, $type = 'success', $data = null) {
    if (isAjaxRequest()) {
        $response = ['success' => $type === 'success', 'message' => $message];
        if ($data !== null) {
            $response = array_merge($response, $data);
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        $params = http_build_query(['message' => $message, 'type' => $type]);
        header("Location: ../../index.php?page=productos&$params");
    }
    exit;
}

/**
 * Save uploaded image file
 */
function saveImage($file) {
    // Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo al disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
        ];
        
        $errorMsg = $errorMessages[$file['error']] ?? 'Error desconocido al subir el archivo';
        throw new Exception($errorMsg);
    }
    
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSize = 20 * 1024 * 1024; // 20MB
    
    $originalName = basename($file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Validate file type
    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten: ' . implode(', ', $allowedTypes));
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        $sizeMB = round($file['size'] / (1024 * 1024), 2);
        throw new Exception("El archivo es demasiado grande ({$sizeMB}MB). Máximo permitido: 20MB");
    }
    
    // Additional security: Check if file is actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('El archivo no es una imagen válida');
    }
    
    // Generate unique filename
    $newName = uniqid() . '.' . $extension;
    $uploadPath = '../../assets/img/' . $newName;
    
    // Create directory if it doesn't exist
    $uploadDir = dirname($uploadPath);
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $newName;
    }
    
    throw new Exception('Error al subir la imagen');
}

/**
 * Delete image file
 */
function deleteImage($imageName) {
    if ($imageName && file_exists("../../assets/img/$imageName")) {
        unlink("../../assets/img/$imageName");
    }
}

/**
 * Redirect with message (for form submissions) - DEPRECATED, use sendResponse instead
 */
function redirectWithMessage($message, $type = 'success') {
    sendResponse($message, $type);
}

/**
 * Guardar variedades de un producto
 */
function guardarVariedades($pdo, $productoId, $variedades) {
    try {
        // Eliminar variedades existentes
        $stmt = $pdo->prepare("DELETE FROM producto_variedad_grupos WHERE producto_id = ?");
        $stmt->execute([$productoId]);
        
        // Insertar nuevas variedades
        foreach ($variedades as $grupo) {
            $nombreGrupo = trim($grupo['nombre'] ?? '');
            $obligatorio = intval($grupo['obligatorio'] ?? 1);
            $orden = intval($grupo['orden'] ?? 0);
            
            if (empty($nombreGrupo)) continue;
            
            // Insertar grupo
            $sqlGrupo = "INSERT INTO producto_variedad_grupos (producto_id, nombre, obligatorio, orden, activo) 
                         VALUES (?, ?, ?, ?, 1)";
            $stmtGrupo = $pdo->prepare($sqlGrupo);
            $stmtGrupo->execute([$productoId, $nombreGrupo, $obligatorio, $orden]);
            $grupoId = $pdo->lastInsertId();
            
            // Insertar opciones del grupo
            if (isset($grupo['opciones']) && is_array($grupo['opciones'])) {
                $ordenOpcion = 0;
                foreach ($grupo['opciones'] as $opcion) {
                    $nombreOpcion = trim($opcion['nombre'] ?? '');
                    $precioAdicional = floatval($opcion['precio_adicional'] ?? 0);
                    
                    if (empty($nombreOpcion)) continue;
                    
                    $ordenOpcion++;
                    $sqlOpcion = "INSERT INTO producto_variedad_opciones (grupo_id, nombre, precio_adicional, orden, activo) 
                                  VALUES (?, ?, ?, ?, 1)";
                    $stmtOpcion = $pdo->prepare($sqlOpcion);
                    $stmtOpcion->execute([$grupoId, $nombreOpcion, $precioAdicional, $ordenOpcion]);
                }
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error al guardar variedades: " . $e->getMessage());
        throw new Exception("Error al guardar las variedades del producto");
    }
}

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            // Validate required fields
            $nombre = trim($_POST['nombre'] ?? '');
            $precio = floatval($_POST['precio'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            $type = intval($_POST['type'] ?? 0);
            $tiene_variedades = intval($_POST['tiene_variedades'] ?? 0);

            if (empty($nombre)) {
                throw new Exception('El nombre del producto es requerido');
            }
            if ($precio <= 0) {
                throw new Exception('El precio debe ser mayor a 0');
            }
            if ($type <= 0) {
                throw new Exception('Debe seleccionar una categoría válida');
            }

            // Handle image upload
            $imagen = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $imagen = saveImage($_FILES['imagen']);
            }

            // Insert product
            $sql = "INSERT INTO productos (nombre, precio, descripcion, imagen, type, categoria, tiene_variedades) VALUES (?, ?, ?, ?, ?, 'comidas', ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$nombre, $precio, $descripcion, $imagen, $type, $tiene_variedades]);

            if ($success) {
                $productoId = $pdo->lastInsertId();
                
                // Guardar variedades si las tiene
                if ($tiene_variedades && isset($_POST['variedades'])) {
                    $variedades = json_decode($_POST['variedades'], true);
                    if (is_array($variedades) && count($variedades) > 0) {
                        guardarVariedades($pdo, $productoId, $variedades);
                    }
                }
                
                redirectWithMessage('Producto creado exitosamente');
            } else {
                throw new Exception('Error al crear el producto');
            }
            break;

        case 'edit':
            // Validate required fields
            $id = intval($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $precio = floatval($_POST['precio'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            $type = intval($_POST['type'] ?? 0);
            $tiene_variedades = intval($_POST['tiene_variedades'] ?? 0);

            if (!$id) {
                throw new Exception('ID de producto inválido');
            }
            if (empty($nombre)) {
                throw new Exception('El nombre del producto es requerido');
            }
            if ($precio <= 0) {
                throw new Exception('El precio debe ser mayor a 0');
            }
            if ($type <= 0) {
                throw new Exception('Debe seleccionar una categoría válida');
            }

            // Get current product data
            $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentProduct) {
                throw new Exception('Producto no encontrado');
            }

            // Handle image upload
            $imagen = $currentProduct['imagen']; // Keep current image by default
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                // Delete old image
                if ($currentProduct['imagen']) {
                    deleteImage($currentProduct['imagen']);
                }
                // Upload new image
                $imagen = saveImage($_FILES['imagen']);
            }

            // Update product
            $sql = "UPDATE productos SET nombre = ?, precio = ?, descripcion = ?, imagen = ?, type = ?, tiene_variedades = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$nombre, $precio, $descripcion, $imagen, $type, $tiene_variedades, $id]);

            if ($success) {
                // Actualizar variedades
                if ($tiene_variedades && isset($_POST['variedades'])) {
                    $variedades = json_decode($_POST['variedades'], true);
                    if (is_array($variedades) && count($variedades) > 0) {
                        guardarVariedades($pdo, $id, $variedades);
                    }
                } else {
                    // Si no tiene variedades, eliminar las existentes
                    $stmt = $pdo->prepare("DELETE FROM producto_variedad_grupos WHERE producto_id = ?");
                    $stmt->execute([$id]);
                }
                
                redirectWithMessage('Producto actualizado exitosamente');
            } else {
                throw new Exception('Error al actualizar el producto');
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('ID de producto inválido');
            }

            // Get product data to delete image
            $stmt = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                // Delete image file
                if ($product['imagen']) {
                    deleteImage($product['imagen']);
                }

                // Delete product from database
                $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
                $success = $stmt->execute([$id]);

                if ($success) {
                    redirectWithMessage('Producto eliminado exitosamente');
                } else {
                    throw new Exception('Error al eliminar el producto');
                }
            } else {
                throw new Exception('Producto no encontrado');
            }
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    error_log("Product CRUD Error: " . $e->getMessage());
    sendResponse($e->getMessage(), 'error');
}
?>
