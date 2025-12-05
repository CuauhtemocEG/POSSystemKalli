<?php
require_once '../config.php';
require_once '../conexion.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    if (!isset($_FILES['archivo_excel']) || $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se ha subido ningún archivo válido');
    }
    
    $archivo = $_FILES['archivo_excel'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    // Validar extensión
    if (!in_array($extension, ['xls', 'xlsx', 'csv'])) {
        throw new Exception('Formato de archivo no válido. Use XLS, XLSX o CSV');
    }
    
    // Validar tamaño (máximo 5MB)
    if ($archivo['size'] > 5 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB permitido');
    }
    
    $pdo = conexion();
    
    // Obtener tipos válidos
    $tipos_validos = $pdo->query("SELECT id, nombre FROM type")->fetchAll(PDO::FETCH_KEY_PAIR);
    $tipos_nombres = array_flip($tipos_validos); // nombre => id
    
    // Leer y procesar archivo
    $contenido = file_get_contents($archivo['tmp_name']);
    
    // Detectar encoding y convertir si es necesario
    if (!mb_check_encoding($contenido, 'UTF-8')) {
        $contenido = mb_convert_encoding($contenido, 'UTF-8', 'auto');
    }
    
    $lineas = [];
    
    // Procesar según el tipo de archivo
    if ($extension === 'csv') {
        // Para CSV, dividir por líneas y procesar
        $lineas = explode("\n", $contenido);
    } else {
        // Para XLS/XLSX guardados como HTML, procesar las líneas
        $lineas = explode("\n", $contenido);
    }
    
    $productos_procesados = [];
    $errores = [];
    $actualizados = 0;
    $creados = 0;
    
    // Saltar las primeras líneas que son información/encabezados
    $inicio_datos = false;
    $fila_numero = 0;
    
    foreach ($lineas as $linea) {
        $fila_numero++;
        
        // Buscar la fila de encabezados
        if (strpos(strtoupper($linea), 'NOMBRE') !== false && strpos(strtoupper($linea), 'PRECIO') !== false) {
            $inicio_datos = true;
            continue;
        }
        
        if (!$inicio_datos) continue;
        
        // Limpiar y dividir la línea
        $linea = trim($linea);
        if (empty($linea)) continue;
        
        // Para archivos Excel guardados como HTML, extraer celdas
        if (strpos($linea, '<td>') !== false) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/i', $linea, $matches);
            $celdas = array_map('strip_tags', $matches[1]);
        } else {
            // Para CSV, probar diferentes delimitadores
            $celdas = str_getcsv($linea, ";"); // Primero punto y coma (más común en Excel español)
            if (count($celdas) === 1) {
                $celdas = str_getcsv($linea, "\t"); // Luego tabs
            }
            if (count($celdas) === 1) {
                $celdas = str_getcsv($linea, ","); // Finalmente comas
            }
        }
        
        if (count($celdas) < 5) continue; // Necesitamos al menos ID, nombre, descripción, precio, tipo
        
        $id = trim($celdas[0]);
        $nombre = trim($celdas[1]);
        $descripcion = trim($celdas[2] ?? '');
        $precio = trim($celdas[3]);
        $tipo_nombre = trim($celdas[4]);
        $imagen = trim($celdas[5] ?? '');
        
        // Validaciones
        if (empty($nombre)) {
            $errores[] = "Fila $fila_numero: El nombre del producto es obligatorio";
            continue;
        }
        
        if (empty($precio) || !is_numeric($precio) || $precio <= 0) {
            $errores[] = "Fila $fila_numero: El precio debe ser un número mayor a 0";
            continue;
        }
        
        if (empty($tipo_nombre) || !isset($tipos_nombres[$tipo_nombre])) {
            $errores[] = "Fila $fila_numero: Tipo '$tipo_nombre' no válido. Tipos disponibles: " . implode(', ', array_keys($tipos_nombres));
            continue;
        }
        
        $tipo_id = $tipos_nombres[$tipo_nombre];
        
        try {
            if (empty($id) || strtoupper($id) === 'NUEVO') {
                // Crear nuevo producto
                $stmt = $pdo->prepare("
                    INSERT INTO productos (nombre, descripcion, precio, type, imagen) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nombre, $descripcion, $precio, $tipo_id, $imagen]);
                $creados++;
            } else {
                // Actualizar producto existente
                if (!is_numeric($id)) {
                    $errores[] = "Fila $fila_numero: ID '$id' no es válido";
                    continue;
                }
                
                // Verificar que el producto existe
                $existe = $pdo->prepare("SELECT id FROM productos WHERE id = ?");
                $existe->execute([$id]);
                
                if (!$existe->fetch()) {
                    $errores[] = "Fila $fila_numero: No existe un producto con ID $id";
                    continue;
                }
                
                $stmt = $pdo->prepare("
                    UPDATE productos 
                    SET nombre = ?, descripcion = ?, precio = ?, type = ?, imagen = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $descripcion, $precio, $tipo_id, $imagen, $id]);
                $actualizados++;
            }
            
            $productos_procesados[] = [
                'id' => $id,
                'nombre' => $nombre,
                'accion' => (empty($id) || strtoupper($id) === 'NUEVO') ? 'creado' : 'actualizado'
            ];
            
        } catch (PDOException $e) {
            $errores[] = "Fila $fila_numero: Error en base de datos - " . $e->getMessage();
        }
    }
    
    $response = [
        'success' => true,
        'message' => "Proceso completado: $creados productos creados, $actualizados actualizados",
        'creados' => $creados,
        'actualizados' => $actualizados,
        'errores' => $errores,
        'productos_procesados' => $productos_procesados
    ];
    
    if (!empty($errores)) {
        $response['warning'] = true;
        $response['message'] .= ". Se encontraron " . count($errores) . " errores";
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
