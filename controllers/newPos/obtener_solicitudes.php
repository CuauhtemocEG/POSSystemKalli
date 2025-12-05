<?php
header('Content-Type: application/json');

// Verificar autenticación
include_once('../../auth-check.php');

// Verificar permisos específicos
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode([
        'success' => false,
        'message' => 'Sin permisos',
        'redirect' => '../../login.php'
    ]);
    exit;
}

include_once('../../conexion.php');

try {
    $pdo = conexion();
    
    // Obtener solicitudes pendientes
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            p.nombre as producto_nombre,
            m.nombre as mesa_nombre,
            o.codigo as orden_codigo,
            u.nombre_completo as solicitante,
            TIMESTAMPDIFF(MINUTE, c.fecha_creacion, NOW()) as minutos_transcurridos,
            TIMESTAMPDIFF(MINUTE, NOW(), c.fecha_expiracion) as minutos_restantes
        FROM codigos_cancelacion c
        JOIN productos p ON c.producto_id = p.id
        JOIN ordenes o ON c.orden_id = o.id
        JOIN mesas m ON o.mesa_id = m.id
        JOIN usuarios u ON c.solicitado_por = u.id
        WHERE c.usado = 0 AND c.fecha_expiracion > NOW()
        ORDER BY c.fecha_creacion DESC
    ");
    $stmt->execute();
    $solicitudes = $stmt->fetchAll();
    
    // Generar HTML para las solicitudes
    ob_start();
    foreach ($solicitudes as $solicitud): ?>
        <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-600 overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h4 class="text-xl font-bold text-white mb-1">
                            Mesa <?= htmlspecialchars($solicitud['mesa_nombre']) ?>
                        </h4>
                        <p class="text-slate-400">Orden: <?= htmlspecialchars($solicitud['orden_codigo']) ?></p>
                    </div>
                    <div class="text-right">
                        <div class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                            PIN: <?= $solicitud['codigo'] ?>
                        </div>
                        <p class="text-slate-400 text-sm mt-1">
                            Hace <?= $solicitud['minutos_transcurridos'] ?> min
                        </p>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-slate-900 rounded-xl p-4">
                        <h5 class="text-white font-semibold mb-2">Producto a cancelar:</h5>
                        <p class="text-blue-400 font-bold"><?= htmlspecialchars($solicitud['producto_nombre']) ?></p>
                    </div>
                    
                    <div class="bg-slate-900 rounded-xl p-4">
                        <h5 class="text-white font-semibold mb-2">Solicitado por:</h5>
                        <p class="text-green-400 font-bold"><?= htmlspecialchars($solicitud['solicitante']) ?></p>
                    </div>
                </div>
                
                <?php if (!empty($solicitud['razon'])): ?>
                <div class="mt-4 bg-slate-900 rounded-xl p-4">
                    <h5 class="text-white font-semibold mb-2">Razón de cancelación:</h5>
                    <p class="text-slate-300"><?= htmlspecialchars($solicitud['razon']) ?></p>
                </div>
                <?php endif; ?>
                
                <div class="mt-6 flex justify-between items-center">
                    <div class="flex space-x-2">
                        <?php if ($solicitud['minutos_restantes'] <= 5): ?>
                        <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm">
                            <i class="bi bi-clock-fill mr-1"></i>Expira en <?= $solicitud['minutos_restantes'] ?> min
                        </span>
                        <?php else: ?>
                        <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm">
                            <i class="bi bi-clock mr-1"></i>Válido por <?= $solicitud['minutos_restantes'] ?> min
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <button onclick="usarCodigo('<?= $solicitud['codigo'] ?>')" 
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl font-semibold transition-colors">
                        <i class="bi bi-check-lg mr-2"></i>Usar PIN
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach;
    
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'total_solicitudes' => count($solicitudes),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
