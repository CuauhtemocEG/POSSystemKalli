<?php
require_once '../conexion.php';

try {
    $pdo = conexion();
    $sql = file_get_contents('../database_updates/add_descuento_porcentaje_manual.sql');
    
    // Separar por punto y coma y ejecutar cada statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
            echo "✓ Ejecutado: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n=== MIGRACIÓN COMPLETADA ===\n";
    echo "✓ Campo aplicar_descuento_porcentaje agregado\n";
    echo "✓ Campo descuento_porcentaje_valor agregado\n";
    echo "✓ Índice idx_ordenes_descuento_manual creado\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
