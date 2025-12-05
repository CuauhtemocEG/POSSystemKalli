<?php
/**
 * CSV Export/Import Controller for Products
 */

// Determine correct path to conexion.php
$base_path = dirname(dirname(__DIR__));
if (file_exists($base_path . '/conexion.php')) {
    require_once $base_path . '/conexion.php';
} else if (file_exists('../../conexion.php')) {
    require_once '../../conexion.php';
} else {
    die('Error: No se puede encontrar conexion.php');
}

// Start session for user verification if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify user authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Acceso denegado');
}

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
if (!isAdmin($_SESSION['user_id'], $pdo)) {
    http_response_code(403);
    die('Permisos insuficientes');
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'download_template';

try {
    switch ($action) {
        case 'download_template':
            // Get categories for the template
            $categories = $pdo->query("SELECT id, nombre FROM type ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
            
            // Get some sample products
            $sampleProducts = $pdo->query("
                SELECT p.nombre, p.descripcion, p.precio, t.nombre as categoria, p.imagen
                FROM productos p
                LEFT JOIN type t ON p.type = t.id
                ORDER BY p.nombre
                LIMIT 3
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // Set CSV headers
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="Plantilla_Productos_' . date('Y-m-d_H-i-s') . '.csv"');
            header('Cache-Control: max-age=0');
            
            // Open output stream
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fputs($output, "\xEF\xBB\xBF");
            
            // Write headers
            fputcsv($output, [
                'nombre',
                'descripcion', 
                'precio',
                'categoria',
                'imagen'
            ], ';');
            
            // Write instructions as comment rows
            fputcsv($output, [
                '# INSTRUCCIONES:'
            ], ';');
            fputcsv($output, [
                '# - nombre: Nombre del producto (obligatorio)'
            ], ';');
            fputcsv($output, [
                '# - descripcion: Descripción del producto (opcional)'
            ], ';');
            fputcsv($output, [
                '# - precio: Precio en formato decimal, ej: 25.50'
            ], ';');
            fputcsv($output, [
                '# - categoria: Debe ser exactamente uno de estos valores:'
            ], ';');
            
            foreach ($categories as $cat) {
                fputcsv($output, [
                    "#   - {$cat['nombre']}"
                ], ';');
            }
            
            fputcsv($output, [
                '# - imagen: Nombre del archivo de imagen (opcional)'
            ], ';');
            fputcsv($output, [
                '# NOTA: Elimine estas líneas de instrucciones antes de importar'
            ], ';');
            fputcsv($output, [], ';'); // Empty row
            
            // Write sample data
            foreach ($sampleProducts as $product) {
                fputcsv($output, [
                    $product['nombre'] . ' (EJEMPLO)',
                    $product['descripcion'] ?? '',
                    $product['precio'],
                    $product['categoria'] ?? '',
                    $product['imagen'] ?? ''
                ], ';');
            }
            
            fclose($output);
            exit;
            break;

        case 'import':
            if (!isset($_FILES['archivo_csv'])) {
                throw new Exception('No se ha seleccionado ningún archivo');
            }
            
            $file = $_FILES['archivo_csv'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Error al subir el archivo');
            }
            
            // Validate file type
            $allowedTypes = ['csv', 'xls', 'xlsx'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedTypes)) {
                throw new Exception('Tipo de archivo no válido. Solo se permiten: ' . implode(', ', $allowedTypes));
            }
            
            // Validate file size (10MB max)
            if ($file['size'] > 10 * 1024 * 1024) {
                throw new Exception('El archivo es demasiado grande. Máximo 10MB permitido');
            }
            
            // Process the file based on extension
            $data = [];
            
            if ($extension === 'csv') {
                $data = processCsvFile($file['tmp_name']);
            } else {
                // Handle Excel files
                $data = processExcelFile($file['tmp_name']);
            }
            
            if (empty($data)) {
                throw new Exception('El archivo está vacío o no contiene datos válidos');
            }
            
            // Import the data
            $result = importProducts($data, $pdo);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Importación completada',
                'creados' => $result['created'],
                'actualizados' => $result['updated'],
                'errores' => $result['errors']
            ]);
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    error_log("CSV Controller Error: " . $e->getMessage());
    
    if ($action === 'import') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else {
        die('Error: ' . $e->getMessage());
    }
}

/**
 * Process CSV file
 */
function processCsvFile($filename) {
    $data = [];
    
    if (($handle = fopen($filename, "r")) !== FALSE) {
        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        
        $delimiter = ';';
        if (substr_count($firstLine, ',') > substr_count($firstLine, ';')) {
            $delimiter = ',';
        }
        
        // Read header
        $headers = fgetcsv($handle, 1000, $delimiter);
        if (!$headers) {
            throw new Exception('No se pudieron leer las cabeceras del archivo CSV');
        }
        
        // Clean headers
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        
        // Validate required headers
        $requiredHeaders = ['nombre', 'precio'];
        foreach ($requiredHeaders as $required) {
            if (!in_array($required, $headers)) {
                throw new Exception("Falta la columna requerida: $required");
            }
        }
        
        // Read data
        $rowNumber = 2; // Start from row 2 (after headers)
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            // Skip empty rows and comment rows
            if (empty($row) || (isset($row[0]) && strpos($row[0], '#') === 0)) {
                $rowNumber++;
                continue;
            }
            
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = isset($row[$index]) ? trim($row[$index]) : '';
            }
            
            $rowData['_row_number'] = $rowNumber;
            $data[] = $rowData;
            $rowNumber++;
        }
        
        fclose($handle);
    } else {
        throw new Exception('No se pudo abrir el archivo CSV');
    }
    
    return $data;
}

/**
 * Process Excel file (basic implementation)
 */
function processExcelFile($filename) {
    // For Excel files, we'll convert them to CSV first
    // This is a simplified approach - in production, you might want to use PhpSpreadsheet
    throw new Exception('Los archivos Excel no están soportados en esta versión. Use archivos CSV.');
}

/**
 * Import products from parsed data
 */
function importProducts($data, $pdo) {
    $created = 0;
    $updated = 0;
    $errors = [];
    
    // Get categories map
    $categories = $pdo->query("SELECT nombre, id FROM type")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $pdo->beginTransaction();
    
    try {
        foreach ($data as $row) {
            try {
                $nombre = trim($row['nombre'] ?? '');
                $precio = floatval($row['precio'] ?? 0);
                $descripcion = trim($row['descripcion'] ?? '');
                $categoriaName = trim($row['categoria'] ?? '');
                $imagen = trim($row['imagen'] ?? '') ?: null;
                
                // Validate required fields
                if (empty($nombre)) {
                    throw new Exception("Fila {$row['_row_number']}: Nombre es requerido");
                }
                
                if ($precio <= 0) {
                    throw new Exception("Fila {$row['_row_number']}: Precio debe ser mayor a 0");
                }
                
                // Find category ID
                $categoryId = null;
                if (!empty($categoriaName)) {
                    if (isset($categories[$categoriaName])) {
                        $categoryId = $categories[$categoriaName];
                    } else {
                        throw new Exception("Fila {$row['_row_number']}: Categoría '$categoriaName' no existe");
                    }
                }
                
                // Check if product exists
                $stmt = $pdo->prepare("SELECT id FROM productos WHERE nombre = ?");
                $stmt->execute([$nombre]);
                $existingId = $stmt->fetchColumn();
                
                if ($existingId) {
                    // Update existing product
                    $sql = "UPDATE productos SET precio = ?, descripcion = ?";
                    $params = [$precio, $descripcion];
                    
                    if ($categoryId) {
                        $sql .= ", type = ?";
                        $params[] = $categoryId;
                    }
                    
                    if ($imagen) {
                        $sql .= ", imagen = ?";
                        $params[] = $imagen;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $existingId;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $updated++;
                } else {
                    // Create new product
                    $sql = "INSERT INTO productos (nombre, precio, descripcion, imagen, type, categoria) VALUES (?, ?, ?, ?, ?, 'comidas')";
                    $params = [$nombre, $precio, $descripcion, $imagen, $categoryId];
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $created++;
                }
                
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw new Exception('Error en la importación: ' . $e->getMessage());
    }
    
    return [
        'created' => $created,
        'updated' => $updated,
        'errors' => $errors
    ];
}
?>
