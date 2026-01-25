<?php
/**
 * DIAGNÓSTICO COMPLETO DEL SISTEMA DE PROMOCIONES
 * Acceso: http://localhost/POS/diagnostico_promociones_web.php
 */

require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Promociones - POS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 20px;
            color: #333;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1e3c72;
            margin-bottom: 15px;
            border-bottom: 2px solid #1e3c72;
            padding-bottom: 10px;
        }
        .status { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin-right: 10px; }
        .status.ok { background-color: #10b981; color: white; }
        .status.error { background-color: #ef4444; color: white; }
        .status.warning { background-color: #f59e0b; color: white; }
        .info-box {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .alert-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .error-box {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #1e3c72;
            color: white;
        }
        tr:hover { background-color: #f5f5f5; }
        code {
            background-color: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .metric {
            display: inline-block;
            margin: 10px 15px;
            text-align: center;
        }
        .metric-value {
            font-size: 2em;
            font-weight: bold;
            color: #1e3c72;
        }
        .metric-label {
            color: #666;
            font-size: 0.9em;
        }
        .check-list {
            list-style: none;
            padding-left: 0;
        }
        .check-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2563eb;
        }
        .btn-success { background-color: #10b981; }
        .btn-success:hover { background-color: #059669; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico del Sistema de Promociones</h1>

        <?php
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // 1. Verificar tablas
            echo '<div class="card">';
            echo '<h2>1. Tablas de Promociones</h2>';
            $tablas_necesarias = ['promociones', 'promocion_productos', 'promocion_categorias', 'promociones_aplicadas'];
            $tablas_ok = 0;
            
            echo '<table>';
            echo '<tr><th>Tabla</th><th>Estado</th><th>Registros</th></tr>';
            
            foreach ($tablas_necesarias as $tabla) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
                echo '<tr>';
                echo '<td><code>' . $tabla . '</code></td>';
                
                if ($stmt->rowCount() > 0) {
                    $count = $pdo->query("SELECT COUNT(*) FROM $tabla")->fetchColumn();
                    echo '<td><span class="status ok">✓ Existe</span></td>';
                    echo '<td>' . number_format($count) . '</td>';
                    $tablas_ok++;
                } else {
                    echo '<td><span class="status error">✗ No existe</span></td>';
                    echo '<td>-</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
            
            if ($tablas_ok < count($tablas_necesarias)) {
                echo '<div class="error-box">';
                echo '<strong>⚠️ ACCIÓN REQUERIDA:</strong> Ejecutar script SQL:<br>';
                echo '<code>database_updates/fix_promociones_complete.sql</code>';
                echo '</div>';
            } else {
                echo '<div class="info-box">';
                echo '<span class="status ok">✓</span> Todas las tablas existen correctamente';
                echo '</div>';
            }
            echo '</div>';
            
            // 2. Verificar estructura de mesas
            echo '<div class="card">';
            echo '<h2>2. Estructura de Mesas</h2>';
            $stmt = $pdo->query("DESCRIBE mesas");
            $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $tiene_aplicar_promo = in_array('aplicar_promociones', $cols);
            $tiene_para_llevar = in_array('es_para_llevar', $cols);
            
            echo '<ul class="check-list">';
            echo '<li>' . ($tiene_aplicar_promo ? '<span class="status ok">✓</span>' : '<span class="status error">✗</span>') . ' Campo <code>aplicar_promociones</code></li>';
            echo '<li>' . ($tiene_para_llevar ? '<span class="status ok">✓</span>' : '<span class="status error">✗</span>') . ' Campo <code>es_para_llevar</code></li>';
            echo '</ul>';
            
            if (!$tiene_aplicar_promo || !$tiene_para_llevar) {
                echo '<div class="alert-box">';
                echo '<strong>⚠️ Campos faltantes:</strong> Estos campos son necesarios para controlar qué mesas aplican promociones.';
                echo '</div>';
            }
            echo '</div>';
            
            // 3. Verificar estructura de ordenes
            echo '<div class="card">';
            echo '<h2>3. Estructura de Órdenes</h2>';
            $stmt = $pdo->query("DESCRIBE ordenes");
            $cols_ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tiene_subtotal = false;
            $tiene_total = false;
            
            foreach ($cols_ordenes as $col) {
                if ($col['Field'] == 'subtotal') $tiene_subtotal = true;
                if ($col['Field'] == 'total') $tiene_total = true;
            }
            
            echo '<ul class="check-list">';
            echo '<li>' . ($tiene_subtotal ? '<span class="status ok">✓</span>' : '<span class="status warning">⚠</span>') . ' Campo <code>subtotal</code> ' . ($tiene_subtotal ? '(existe)' : '(recomendado pero opcional)') . '</li>';
            echo '<li>' . ($tiene_total ? '<span class="status ok">✓</span>' : '<span class="status error">✗</span>') . ' Campo <code>total</code></li>';
            echo '</ul>';
            
            if ($tiene_subtotal) {
                echo '<div class="info-box">';
                echo '✓ El sistema puede trackear subtotal y total por separado (recomendado)';
                echo '</div>';
            } else {
                echo '<div class="alert-box">';
                echo '⚠️ Sin campo subtotal. El sistema calculará todo en tiempo real.';
                echo '</div>';
            }
            echo '</div>';
            
            // 4. Análisis de órdenes
            echo '<div class="card">';
            echo '<h2>4. Análisis de Órdenes Recientes (últimas 10)</h2>';
            
            $stmt = $pdo->query("
                SELECT 
                    o.id,
                    o.codigo,
                    o.estado,
                    o.total as total_guardado,
                    " . ($tiene_subtotal ? "o.subtotal as subtotal_guardado," : "NULL as subtotal_guardado,") . "
                    (SELECT COALESCE(SUM(op.cantidad * p.precio), 0)
                     FROM orden_productos op 
                     JOIN productos p ON op.producto_id = p.id 
                     WHERE op.orden_id = o.id 
                     AND (op.cancelado = 0 OR op.cancelado IS NULL)) as subtotal_real,
                    (SELECT COALESCE(SUM(pa.descuento_aplicado), 0)
                     FROM promociones_aplicadas pa
                     WHERE pa.orden_id = o.id) as descuentos_aplicados
                FROM ordenes o
                ORDER BY o.creada_en DESC
                LIMIT 10
            ");
            
            $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $problemas = 0;
            
            if (count($ordenes) > 0) {
                echo '<table>';
                echo '<tr>';
                echo '<th>Código</th>';
                echo '<th>Estado</th>';
                echo '<th>Subtotal Real</th>';
                echo '<th>Descuentos</th>';
                echo '<th>Total Esperado</th>';
                echo '<th>Total Guardado</th>';
                echo '<th>Estado</th>';
                echo '</tr>';
                
                foreach ($ordenes as $orden) {
                    $subtotal = floatval($orden['subtotal_real']);
                    $descuentos = floatval($orden['descuentos_aplicados']);
                    $total_esperado = $subtotal - $descuentos;
                    $total_guardado = floatval($orden['total_guardado']);
                    $diferencia = abs($total_esperado - $total_guardado);
                    
                    $estado = 'ok';
                    if ($diferencia >= 0.01) {
                        $estado = 'error';
                        $problemas++;
                    }
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($orden['codigo']) . '</td>';
                    echo '<td>' . htmlspecialchars($orden['estado']) . '</td>';
                    echo '<td>$' . number_format($subtotal, 2) . '</td>';
                    echo '<td>$' . number_format($descuentos, 2) . '</td>';
                    echo '<td>$' . number_format($total_esperado, 2) . '</td>';
                    echo '<td>$' . number_format($total_guardado, 2) . '</td>';
                    echo '<td>';
                    if ($estado == 'ok') {
                        echo '<span class="status ok">✓ OK</span>';
                    } else {
                        echo '<span class="status error">✗ Diff: $' . number_format($diferencia, 2) . '</span>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                if ($problemas > 0) {
                    echo '<div class="error-box">';
                    echo '<strong>⚠️ PROBLEMA CRÍTICO:</strong> Se encontraron ' . $problemas . ' órdenes con totales incorrectos.<br>';
                    echo 'El campo <code>total</code> no coincide con el cálculo: subtotal - descuentos<br>';
                    echo '<strong>CAUSA:</strong> El proceso de cierre no está calculando correctamente los descuentos.';
                    echo '</div>';
                } else {
                    echo '<div class="info-box">';
                    echo '<span class="status ok">✓</span> Todas las órdenes tienen totales correctos';
                    echo '</div>';
                }
            } else {
                echo '<div class="alert-box">No hay órdenes en el sistema</div>';
            }
            echo '</div>';
            
            // 5. Promociones activas
            echo '<div class="card">';
            echo '<h2>5. Promociones Activas</h2>';
            
            try {
                $stmt = $pdo->query("
                    SELECT id, nombre, tipo, activa, minimo_productos, prioridad
                    FROM promociones 
                    WHERE activa = 1
                    AND (fecha_inicio IS NULL OR fecha_inicio <= NOW())
                    AND (fecha_fin IS NULL OR fecha_fin >= NOW())
                    ORDER BY prioridad DESC, id DESC
                ");
                $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($promos) > 0) {
                    echo '<table>';
                    echo '<tr><th>Nombre</th><th>Tipo</th><th>Mínimo</th><th>Prioridad</th></tr>';
                    foreach ($promos as $promo) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($promo['nombre']) . '</td>';
                        echo '<td>' . htmlspecialchars($promo['tipo']) . '</td>';
                        echo '<td>' . $promo['minimo_productos'] . '</td>';
                        echo '<td>' . $promo['prioridad'] . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="alert-box">⚠️ No hay promociones activas actualmente</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error-box">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            echo '</div>';
            
            // 6. Configuración de mesas
            if ($tiene_aplicar_promo && $tiene_para_llevar) {
                echo '<div class="card">';
                echo '<h2>6. Configuración de Mesas</h2>';
                
                $stmt = $pdo->query("
                    SELECT nombre, 
                           COALESCE(aplicar_promociones, 1) as aplica_promo,
                           COALESCE(es_para_llevar, 0) as para_llevar
                    FROM mesas
                    ORDER BY nombre
                ");
                $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $sin_promo = 0;
                $para_llevar_count = 0;
                
                echo '<table>';
                echo '<tr><th>Mesa</th><th>Tipo</th><th>Aplica Promociones</th></tr>';
                
                foreach ($mesas as $mesa) {
                    if (!$mesa['aplica_promo']) $sin_promo++;
                    if ($mesa['para_llevar']) $para_llevar_count++;
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($mesa['nombre']) . '</td>';
                    echo '<td>' . ($mesa['para_llevar'] ? '📦 Para llevar' : '🍽️  Mesa') . '</td>';
                    echo '<td>' . ($mesa['aplica_promo'] ? '<span class="status ok">✓ Sí</span>' : '<span class="status warning">✗ No</span>') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                echo '<div class="info-box">';
                echo '<div class="metric"><div class="metric-value">' . (count($mesas) - $sin_promo) . '</div><div class="metric-label">Con promociones</div></div>';
                echo '<div class="metric"><div class="metric-value">' . $sin_promo . '</div><div class="metric-label">Sin promociones</div></div>';
                echo '<div class="metric"><div class="metric-value">' . $para_llevar_count . '</div><div class="metric-label">Para llevar</div></div>';
                echo '</div>';
                
                echo '</div>';
            }
            
            // 7. Promociones en órdenes abiertas
            echo '<div class="card">';
            echo '<h2>7. Validación: Promociones en Órdenes Abiertas</h2>';
            
            try {
                $stmt = $pdo->query("
                    SELECT COUNT(*) as total
                    FROM promociones_aplicadas pa
                    JOIN ordenes o ON pa.orden_id = o.id
                    WHERE o.estado != 'cerrada'
                ");
                $promo_abiertas = $stmt->fetchColumn();
                
                if ($promo_abiertas > 0) {
                    echo '<div class="error-box">';
                    echo '<strong>⚠️ PROBLEMA:</strong> Hay ' . $promo_abiertas . ' promociones en órdenes NO cerradas.<br>';
                    echo 'Las promociones deberían guardarse SOLO cuando se cierra la orden.<br><br>';
                    echo '<strong>Script para limpiar:</strong><br>';
                    echo '<code>DELETE pa FROM promociones_aplicadas pa<br>';
                    echo 'JOIN ordenes o ON pa.orden_id = o.id<br>';
                    echo 'WHERE o.estado != \'cerrada\';</code>';
                    echo '</div>';
                } else {
                    echo '<div class="info-box">';
                    echo '<span class="status ok">✓</span> No hay promociones en órdenes abiertas (correcto)';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="alert-box">No se pudo verificar: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            echo '</div>';
            
            // 8. Resumen y recomendaciones
            echo '<div class="card">';
            echo '<h2>8. Resumen y Recomendaciones</h2>';
            
            $criticos = 0;
            $warnings = 0;
            
            // Contar problemas
            if ($tablas_ok < count($tablas_necesarias)) $criticos++;
            if (!$tiene_aplicar_promo || !$tiene_para_llevar) $warnings++;
            if ($problemas > 0) $criticos++;
            
            echo '<div style="text-align: center; margin: 20px 0;">';
            echo '<div class="metric">';
            echo '<div class="metric-value" style="color: ' . ($criticos > 0 ? '#ef4444' : '#10b981') . ';">' . $criticos . '</div>';
            echo '<div class="metric-label">Problemas Críticos</div>';
            echo '</div>';
            echo '<div class="metric">';
            echo '<div class="metric-value" style="color: ' . ($warnings > 0 ? '#f59e0b' : '#10b981') . ';">' . $warnings . '</div>';
            echo '<div class="metric-label">Advertencias</div>';
            echo '</div>';
            echo '</div>';
            
            if ($criticos > 0 || $warnings > 0) {
                echo '<div class="error-box">';
                echo '<h3>⚠️ ACCIONES REQUERIDAS:</h3>';
                echo '<ol style="margin-left: 20px; margin-top: 10px;">';
                
                if ($tablas_ok < count($tablas_necesarias)) {
                    echo '<li>Ejecutar script SQL: <code>database_updates/fix_promociones_complete.sql</code></li>';
                }
                
                if ($problemas > 0) {
                    echo '<li><strong>CRÍTICO:</strong> Corregir el cálculo de totales en <code>cerrar_orden.php</code></li>';
                    echo '<li>Las promociones deben guardarse al cerrar la orden y aplicarse al total</li>';
                }
                
                if (!$tiene_aplicar_promo || !$tiene_para_llevar) {
                    echo '<li>Actualizar estructura de mesas para control de promociones</li>';
                    echo '<li>Modificar <code>mesa.php</code> para permitir toggle de promociones</li>';
                }
                
                echo '</ol>';
                echo '</div>';
                
                echo '<div style="margin-top: 20px;">';
                echo '<a href="database_updates/fix_promociones_complete.sql" class="btn btn-success" download>📥 Descargar Script SQL</a>';
                echo '</div>';
                
            } else {
                echo '<div class="info-box" style="text-align: center; padding: 30px;">';
                echo '<h3 style="color: #10b981; margin-bottom: 15px;">✅ Sistema Configurado Correctamente</h3>';
                echo '<p>Todas las validaciones pasaron exitosamente. El sistema está listo para usar.</p>';
                echo '</div>';
            }
            
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="card">';
            echo '<div class="error-box">';
            echo '<h3>❌ Error de Conexión</h3>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Verificar:</strong></p>';
            echo '<ul>';
            echo '<li>Credenciales de BD en config.php</li>';
            echo '<li>Que el servidor MySQL esté corriendo</li>';
            echo '<li>Que la base de datos existe</li>';
            echo '</ul>';
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px; color: white; opacity: 0.7;">
            <p>Diagnóstico generado el <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
</body>
</html>
