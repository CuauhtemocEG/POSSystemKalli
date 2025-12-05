<?php
/**
 * Handler AJAX para actualizar solicitudes de autorización en tiempo real
 */

require_once __DIR__ . '/../../auth-check.php';
require_once __DIR__ . '/../../conexion.php';

// Solo responder a peticiones AJAX
if (!isset($_POST['ajax_update']) || $_POST['ajax_update'] != '1') {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Petición inválida']));
}

header('Content-Type: application/json');

try {
    $pdo = conexion();
    
    // Verificar permisos
    if (!hasPermission('configuracion', 'ver')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        exit;
    }
    
    // Determinar si es administrador
    $esAdmin = hasPermission('configuracion', 'editar') || $_SESSION['usuario']['rol'] === 'administrador';
    
    // Primero, expirar códigos vencidos automáticamente
    $stmtExpirar = $pdo->prepare("
        UPDATE codigos_cancelacion c
        JOIN orden_productos op ON c.orden_id = op.orden_id AND c.producto_id = op.producto_id
        SET c.usado = 1,
            op.cancelado = GREATEST(0, op.cancelado - c.cantidad_solicitada),
            op.pendiente_cancelacion = GREATEST(0, op.pendiente_cancelacion - c.cantidad_solicitada)
        WHERE c.usado = 0 
        AND TIMESTAMPDIFF(SECOND, c.fecha_creacion, NOW()) >= 600
    ");
    $stmtExpirar->execute();
    
    // Obtener solicitudes actualizadas
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            p.nombre as producto_nombre,
            m.nombre as mesa_nombre,
            o.codigo as orden_codigo,
            u.nombre_completo as solicitante,
            op.cantidad as cantidad_total,
            op.preparado as cantidad_preparada,
            op.cancelado as cantidad_cancelada,
            COALESCE(c.cantidad_solicitada, 1) as cantidad_solicitada,
            TIMESTAMPDIFF(SECOND, c.fecha_creacion, NOW()) as segundos_transcurridos,
            GREATEST(0, 600 - TIMESTAMPDIFF(SECOND, c.fecha_creacion, NOW())) as segundos_restantes
        FROM codigos_cancelacion c
        JOIN productos p ON c.producto_id = p.id
        JOIN ordenes o ON c.orden_id = o.id
        JOIN mesas m ON o.mesa_id = m.id
        JOIN usuarios u ON c.solicitado_por = u.id
        JOIN orden_productos op ON c.orden_id = op.orden_id AND c.producto_id = op.producto_id
        WHERE c.usado = 0
        AND TIMESTAMPDIFF(SECOND, c.fecha_creacion, NOW()) < 600
        ORDER BY c.fecha_creacion DESC
    ");
    $stmt->execute();
    $solicitudes_ajax = $stmt->fetchAll();
    
    // Generar HTML para las solicitudes
    ob_start();
    foreach ($solicitudes_ajax as $solicitud):
        $segundosTranscurridos = intval($solicitud['segundos_transcurridos']);
        $minutosTranscurridos = floor($segundosTranscurridos / 60);
        $segundosRestantes = intval($solicitud['segundos_restantes']);
        $minutosRestantes = floor($segundosRestantes / 60);
        $segsRestantes = $segundosRestantes % 60;
        
        // Determinar nivel de urgencia
        if ($segundosRestantes <= 120) { // 2 minutos o menos
            $urgenciaClass = 'border-red-500 bg-red-900/20';
            $urgenciaIcon = '🔴';
            $urgenciaLabel = 'URGENTE';
        } elseif ($segundosRestantes <= 300) { // 5 minutos o menos
            $urgenciaClass = 'border-orange-500 bg-orange-900/20';
            $urgenciaIcon = '🟠';
            $urgenciaLabel = 'ATENCIÓN';
        } else {
            $urgenciaClass = 'border-slate-600 bg-slate-800';
            $urgenciaIcon = '🟢';
            $urgenciaLabel = 'NORMAL';
        }
    ?>
        <div class="solicitud-card <?= $urgenciaClass ?> rounded-2xl shadow-2xl border-2 overflow-hidden transform transition-all duration-300 hover:scale-[1.02] hover:shadow-3xl" 
             data-codigo="<?= $solicitud['codigo'] ?>"
             data-segundos="<?= $segundosRestantes ?>">
            
            <!-- Header con gradiente -->
            <div class="bg-gradient-to-r from-slate-800 to-slate-700 p-4 border-b border-slate-600">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="bi bi-receipt text-white text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-white flex items-center">
                                Mesa <?= htmlspecialchars($solicitud['mesa_nombre']) ?>
                                <span class="ml-2 text-xs bg-slate-600 px-2 py-1 rounded-full"><?= $urgenciaIcon ?> <?= $urgenciaLabel ?></span>
                            </h4>
                            <p class="text-slate-300 text-sm">Orden: <span class="font-mono"><?= htmlspecialchars($solicitud['orden_codigo']) ?></span></p>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <?php if ($esAdmin): ?>
                        <!-- Solo admin ve el código PIN -->
                        <div class="bg-gradient-to-r from-red-600 to-pink-600 text-white px-4 py-2 rounded-xl text-lg font-bold shadow-lg cursor-pointer hover:from-red-700 hover:to-pink-700 transition-all"
                             onclick="copiarCodigo('<?= $solicitud['codigo'] ?>')"
                             title="Click para copiar">
                            <i class="bi bi-key-fill mr-2"></i><?= $solicitud['codigo'] ?>
                        </div>
                        <p class="text-slate-400 text-xs mt-1">
                            <i class="bi bi-clock-history"></i> Hace <?= $minutosTranscurridos ?> min
                        </p>
                        <?php else: ?>
                        <!-- Cajero NO ve el código -->
                        <div class="bg-slate-700 text-slate-300 px-4 py-2 rounded-xl text-sm">
                            <i class="bi bi-lock-fill mr-2"></i>Código restringido
                            <p class="text-xs text-slate-400 mt-1">Solicite PIN al administrador</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Body -->
            <div class="p-5">
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <!-- Producto -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-xl p-4 border border-slate-700">
                        <div class="flex items-center mb-2">
                            <i class="bi bi-box-seam text-blue-400 text-lg mr-2"></i>
                            <h5 class="text-white font-semibold">Producto a cancelar</h5>
                        </div>
                        <p class="text-blue-300 font-bold text-lg"><?= htmlspecialchars($solicitud['producto_nombre']) ?></p>
                        <div class="mt-2 flex items-center space-x-4 text-sm">
                            <span class="bg-orange-600/20 text-orange-300 px-3 py-1 rounded-lg">
                                <i class="bi bi-hash"></i> <?= $solicitud['cantidad_solicitada'] ?> unidad(es)
                            </span>
                            <?php if ($solicitud['cantidad_preparada'] > 0): ?>
                            <span class="bg-yellow-600/20 text-yellow-300 px-3 py-1 rounded-lg">
                                <i class="bi bi-exclamation-triangle"></i> <?= $solicitud['cantidad_preparada'] ?> preparado(s)
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Solicitante -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-xl p-4 border border-slate-700">
                        <div class="flex items-center mb-2">
                            <i class="bi bi-person-circle text-green-400 text-lg mr-2"></i>
                            <h5 class="text-white font-semibold">Solicitado por</h5>
                        </div>
                        <p class="text-green-300 font-bold text-lg"><?= htmlspecialchars($solicitud['solicitante']) ?></p>
                        <div class="mt-2 text-sm text-slate-300 space-y-1">
                            <p><i class="bi bi-geo-alt-fill text-slate-400"></i> Mesa: <?= htmlspecialchars($solicitud['mesa_nombre']) ?></p>
                            <p><i class="bi bi-receipt-cutoff text-slate-400"></i> Orden: <?= htmlspecialchars($solicitud['orden_codigo']) ?></p>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($solicitud['razon'])): ?>
                <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-xl p-4 border border-slate-700 mb-4">
                    <div class="flex items-start">
                        <i class="bi bi-chat-left-quote text-purple-400 text-xl mr-3 mt-1"></i>
                        <div>
                            <h5 class="text-white font-semibold mb-1">Razón de cancelación</h5>
                            <p class="text-slate-300 italic">"<?= htmlspecialchars($solicitud['razon']) ?>"</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Footer con countdown y botón -->
                <div class="flex justify-between items-center pt-4 border-t border-slate-700">
                    <div class="flex items-center space-x-3">
                        <!-- Countdown animado -->
                        <div class="countdown-badge <?= $segundosRestantes <= 120 ? 'bg-red-600 animate-pulse' : ($segundosRestantes <= 300 ? 'bg-orange-600' : 'bg-blue-600') ?> text-white px-4 py-2 rounded-xl font-mono text-lg shadow-lg">
                            <i class="bi bi-hourglass-split mr-2"></i>
                            <span class="countdown" data-segundos="<?= $segundosRestantes ?>">
                                <?= sprintf('%02d:%02d', $minutosRestantes, $segsRestantes) ?>
                            </span>
                        </div>
                        
                        <?php if ($segundosRestantes <= 120): ?>
                        <span class="text-red-400 font-semibold animate-pulse">
                            <i class="bi bi-exclamation-triangle-fill"></i> ¡Por expirar!
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <button onclick="usarCodigo('<?= $solicitud['codigo'] ?>')" 
                            class="btn-autorizar bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-6 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:scale-105">
                        <i class="bi bi-check-circle-fill mr-2"></i>Autorizar Ahora
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach;
    
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'total_solicitudes' => count($solicitudes_ajax),
        'timestamp' => date('Y-m-d H:i:s'),
        'es_admin' => $esAdmin
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
