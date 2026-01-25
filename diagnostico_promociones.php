<?php
/**
 * DIAGNÓSTICO COMPLETO DEL SISTEMA DE PROMOCIONES
 * Analiza toda la estructura y flujo de promociones
 */

require_once 'config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== DIAGNÓSTICO DEL SISTEMA DE PROMOCIONES ===\n\n";
    
    // 1. Verificar tablas de promociones
    echo "1. TABLAS DE PROMOCIONES:\n";
    $tablas_necesarias = ['promociones', 'promocion_productos', 'promocion_categorias', 'promociones_aplicadas'];
    foreach ($tablas_necesarias as $tabla) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM $tabla")->fetchColumn();
            echo "  ✓ $tabla existe ($count registros)\n";
        } else {
            echo "  ✗ $tabla NO EXISTE - CRÍTICO\n";
        }
    }
    
    // 2. Verificar estructura de mesas
    echo "\n2. ESTRUCTURA DE MESAS:\n";
    $stmt = $pdo->query("DESCRIBE mesas");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tiene_aplicar_promo = in_array('aplicar_promociones', $cols);
    $tiene_para_llevar = in_array('es_para_llevar', $cols);
    
    echo "  Campo 'aplicar_promociones': " . ($tiene_aplicar_promo ? "✓ Existe" : "✗ NO existe") . "\n";
    echo "  Campo 'es_para_llevar': " . ($tiene_para_llevar ? "✓ Existe" : "✗ NO existe") . "\n";
    
    if (!$tiene_aplicar_promo || !$tiene_para_llevar) {
        echo "  ⚠️ EJECUTAR: database_updates/fix_promociones_complete.sql\n";
    }
    
    // 3. Verificar estructura de ordenes
    echo "\n3. ESTRUCTURA DE ORDENES:\n";
    $stmt = $pdo->query("DESCRIBE ordenes");
    $cols_ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tiene_subtotal = false;
    $tiene_total = false;
    
    foreach ($cols_ordenes as $col) {
        if ($col['Field'] == 'subtotal') $tiene_subtotal = true;
        if ($col['Field'] == 'total') $tiene_total = true;
    }
    
    echo "  Campo 'subtotal': " . ($tiene_subtotal ? "✓ Existe" : "✗ NO existe") . "\n";
    echo "  Campo 'total': " . ($tiene_total ? "✓ Existe" : "✗ NO existe") . "\n";
    
    // 4. Analizar órdenes recientes
    echo "\n4. ANÁLISIS DE ÓRDENES RECIENTES:\n";
    $stmt = $pdo->query("
        SELECT 
            o.id,
            o.codigo,
            o.estado,
            o.total as total_guardado,
            " . ($tiene_subtotal ? "o.subtotal as subtotal_guardado," : "0 as subtotal_guardado,") . "
            (SELECT SUM(op.cantidad * p.precio) 
             FROM orden_productos op 
             JOIN productos p ON op.producto_id = p.id 
             WHERE op.orden_id = o.id 
             AND (op.cancelado = 0 OR op.cancelado IS NULL)) as subtotal_real,
            (SELECT COALESCE(SUM(pa.descuento_aplicado), 0)
             FROM promociones_aplicadas pa
             WHERE pa.orden_id = o.id) as descuentos_aplicados
        FROM ordenes o
        ORDER BY o.creada_en DESC
        LIMIT 5
    ");
    
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $problemas = 0;
    
    foreach ($ordenes as $orden) {
        $subtotal = floatval($orden['subtotal_real']);
        $descuentos = floatval($orden['descuentos_aplicados']);
        $total_esperado = $subtotal - $descuentos;
        $total_guardado = floatval($orden['total_guardado']);
        $diferencia = abs($total_esperado - $total_guardado);
        
        $estado_icono = $diferencia < 0.01 ? "✓" : "✗";
        echo "  $estado_icono Orden {$orden['codigo']} ({$orden['estado']}):\n";
        echo "     Subtotal: \$" . number_format($subtotal, 2) . "\n";
        echo "     Descuentos: -\$" . number_format($descuentos, 2) . "\n";
        echo "     Total esperado: \$" . number_format($total_esperado, 2) . "\n";
        echo "     Total guardado: \$" . number_format($total_guardado, 2) . "\n";
        
        if ($diferencia >= 0.01) {
            echo "     ⚠️  DISCREPANCIA: \$" . number_format($diferencia, 2) . "\n";
            $problemas++;
        }
    }
    
    if ($problemas > 0) {
        echo "\n  ⚠️  Se encontraron $problemas órdenes con discrepancias\n";
    }
    
    // 5. Verificar promociones activas
    echo "\n5. PROMOCIONES ACTIVAS:\n";
    try {
        $stmt = $pdo->query("
            SELECT id, nombre, tipo, activa, minimo_productos
            FROM promociones 
            WHERE activa = 1
            ORDER BY prioridad DESC
        ");
        $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($promos) > 0) {
            foreach ($promos as $promo) {
                echo "  • {$promo['nombre']} (tipo: {$promo['tipo']}, mínimo: {$promo['minimo_productos']})\n";
            }
        } else {
            echo "  ⚠️ No hay promociones activas\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Error al consultar promociones: " . $e->getMessage() . "\n";
    }
    
    // 6. Verificar mesas configuradas
    echo "\n6. CONFIGURACIÓN DE MESAS:\n";
    if ($tiene_aplicar_promo && $tiene_para_llevar) {
        $stmt = $pdo->query("
            SELECT nombre, 
                   COALESCE(aplicar_promociones, 1) as aplica_promo,
                   COALESCE(es_para_llevar, 0) as para_llevar
            FROM mesas
            ORDER BY nombre
        ");
        $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($mesas as $mesa) {
            $icono_promo = $mesa['aplica_promo'] ? "✓" : "✗";
            $icono_llevar = $mesa['para_llevar'] ? "📦" : "🍽️ ";
            echo "  {$icono_llevar} Mesa {$mesa['nombre']}: Promociones {$icono_promo}\n";
        }
    } else {
        echo "  ⚠️ Campos de configuración no disponibles aún\n";
    }
    
    // 7. Verificar promociones de órdenes abiertas
    echo "\n7. PROMOCIONES EN ÓRDENES ABIERTAS:\n";
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as total
            FROM promociones_aplicadas pa
            JOIN ordenes o ON pa.orden_id = o.id
            WHERE o.estado != 'cerrada'
        ");
        $promo_abiertas = $stmt->fetchColumn();
        
        if ($promo_abiertas > 0) {
            echo "  ⚠️  Hay $promo_abiertas promociones en órdenes NO cerradas\n";
            echo "      Las promociones deberían guardarse solo al cerrar la orden\n";
        } else {
            echo "  ✓ No hay promociones en órdenes abiertas (correcto)\n";
        }
    } catch (Exception $e) {
        echo "  ⚠️  No se pudo verificar: " . $e->getMessage() . "\n";
    }
    
    // 8. Resumen final
    echo "\n=== RESUMEN ===\n";
    $criticos = 0;
    $warnings = 0;
    
    // Verificar tablas
    foreach ($tablas_necesarias as $tabla) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() == 0) $criticos++;
    }
    
    // Verificar campos de mesas
    if (!$tiene_aplicar_promo || !$tiene_para_llevar) $warnings++;
    
    // Verificar subtotal en ordenes
    if (!$tiene_subtotal) $warnings++;
    
    // Verificar discrepancias
    if ($problemas > 0) $criticos++;
    
    echo "\nProblemas críticos: $criticos\n";
    echo "Advertencias: $warnings\n";
    
    if ($criticos > 0 || $warnings > 0) {
        echo "\n⚠️  ACCIÓN REQUERIDA:\n";
        echo "1. Ejecutar: database_updates/fix_promociones_complete.sql\n";
        echo "2. Verificar que cerrar_orden.php calcule y guarde promociones\n";
        echo "3. Verificar que orden_actual.php NO guarde promociones (solo calcule)\n";
        echo "4. Actualizar mesa.php para permitir toggle de promociones\n";
    } else {
        echo "\n✓ Sistema configurado correctamente\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nVerificar:\n";
    echo "- Credenciales de BD en config.php\n";
    echo "- Que el servidor MySQL esté corriendo\n";
}
