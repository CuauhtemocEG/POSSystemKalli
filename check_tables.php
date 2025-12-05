<?php
require_once 'conexion.php';

try {
    $pdo = conexion();
    
    // Verificar estructura de la tabla mesas
    $stmt = $pdo->query("DESCRIBE mesas");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Estructura de la tabla 'mesas':\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Verificar si existe la tabla mesa_layouts
    $stmt = $pdo->query("SHOW TABLES LIKE 'mesa_layouts'");
    if ($stmt->rowCount() > 0) {
        echo "\nLa tabla 'mesa_layouts' existe.\n";
        
        $stmt = $pdo->query("DESCRIBE mesa_layouts");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Estructura de la tabla 'mesa_layouts':\n";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } else {
        echo "\nLa tabla 'mesa_layouts' NO existe.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
