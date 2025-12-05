<?php
require_once '../config.php';
require_once '../conexion.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Acceso denegado');
}

try {
    $pdo = conexion();
    
    // Obtener tipos para la plantilla
    $tipos = $pdo->query("SELECT id, nombre FROM type ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener algunos productos existentes como ejemplo
    $productos_ejemplo = $pdo->query("
        SELECT p.id, p.nombre, p.descripcion, p.precio, t.nombre as tipo, p.imagen
        FROM productos p
        LEFT JOIN type t ON p.type = t.id
        ORDER BY p.nombre
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Headers para descarga de Excel
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Plantilla_Productos_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Comenzar salida HTML para Excel
    echo "\xEF\xBB\xBF"; // BOM para UTF-8
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            .header { background-color: #4f46e5; color: white; font-weight: bold; text-align: center; }
            .info { background-color: #f3f4f6; }
            .ejemplo { background-color: #ecfdf5; }
            .tipos { background-color: #fef3c7; }
        </style>
    </head>
    <body>
        <!-- Información de la plantilla -->
        <table border="1">
            <tr class="info">
                <td colspan="6"><strong>PLANTILLA DE PRODUCTOS - KALLI JAGUAR POS</strong></td>
            </tr>
            <tr class="info">
                <td colspan="6">Generado el: <?= date('d/m/Y H:i:s') ?></td>
            </tr>
            <tr class="info">
                <td colspan="6"><strong>INSTRUCCIONES:</strong></td>
            </tr>
            <tr class="info">
                <td colspan="6">1. Para NUEVOS productos: deja el ID vacío o usa "NUEVO"</td>
            </tr>
            <tr class="info">
                <td colspan="6">2. Para ACTUALIZAR productos: usa el ID existente del producto</td>
            </tr>
            <tr class="info">
                <td colspan="6">3. El tipo debe coincidir exactamente con los valores permitidos</td>
            </tr>
            <tr class="info">
                <td colspan="6">4. El precio debe ser un número (ej: 25.50)</td>
            </tr>
            <tr class="info">
                <td colspan="6">5. La imagen es opcional (nombre del archivo en assets/img/)</td>
            </tr>
            <tr><td colspan="6"></td></tr>
        </table>
        
        <!-- Tipos disponibles -->
        <table border="1">
            <tr class="tipos">
                <td colspan="6"><strong>TIPOS DE PRODUCTOS DISPONIBLES:</strong></td>
            </tr>
            <?php foreach ($tipos as $tipo): ?>
            <tr class="tipos">
                <td colspan="6"><?= htmlspecialchars($tipo['nombre']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr><td colspan="6"></td></tr>
        </table>

        <!-- Encabezados de la plantilla -->
        <table border="1">
            <tr class="header">
                <td>ID</td>
                <td>NOMBRE</td>
                <td>DESCRIPCION</td>
                <td>PRECIO</td>
                <td>TIPO</td>
                <td>IMAGEN</td>
            </tr>
            
            <!-- Ejemplos con productos existentes -->
            <?php foreach ($productos_ejemplo as $prod): ?>
            <tr class="ejemplo">
                <td><?= $prod['id'] ?></td>
                <td><?= htmlspecialchars($prod['nombre']) ?></td>
                <td><?= htmlspecialchars($prod['descripcion'] ?? '') ?></td>
                <td><?= $prod['precio'] ?></td>
                <td><?= htmlspecialchars($prod['tipo'] ?? '') ?></td>
                <td><?= htmlspecialchars($prod['imagen'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            
            <!-- Filas vacías para nuevos productos -->
            <?php for ($i = 0; $i < 20; $i++): ?>
            <tr>
                <td>NUEVO</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <?php endfor; ?>
        </table>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error al generar la plantilla: ' . $e->getMessage();
}
?>
