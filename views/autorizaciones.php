<?php
require_once 'auth-check.php';
require_once 'conexion.php';
$pdo = conexion();

// Verificar que el usuario tenga acceso a autorizaciones
if (!hasPermission('configuracion', 'ver')) {
    header('Location: index.php?page=error-403');
    exit;
}

// Determinar si es administrador (puede ver códigos PIN)
$esAdministrador = hasPermission('configuracion', 'editar') || $_SESSION['usuario']['rol'] === 'administrador';

// Obtener solicitudes de cancelación pendientes para la carga inicial
$solicitudes = [];
$tabla_error = false;

try {
    $solicitudes = $pdo->query("
        SELECT c.*, p.nombre as producto_nombre, m.nombre as mesa_nombre, 
               u.nombre_completo as solicitante, o.codigo as orden_codigo,
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
    ")->fetchAll();
} catch (PDOException $e) {
    // Si la tabla no existe o hay un error, marcar error y crear array vacío
    $tabla_error = true;
    $solicitudes = [];
}
?>

<!-- Container con margen ajustado al header -->
<div class="container mx-auto px-4 py-6 max-w-7xl">
    
    <!-- Header simplificado -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-700 rounded-2xl shadow-xl p-6 mb-6 border border-slate-600">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center space-x-4">
                <div class="bg-red-600/20 backdrop-blur-md p-3 rounded-xl">
                    <i class="bi bi-shield-check text-red-400 text-3xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                        Panel de Autorizaciones
                        <?php if ($esAdministrador): ?>
                        <span class="text-xs bg-yellow-500 text-black px-2 py-1 rounded-full font-semibold">
                            <i class="bi bi-star-fill"></i> ADMIN
                        </span>
                        <?php else: ?>
                        <span class="text-xs bg-blue-500 text-white px-2 py-1 rounded-full font-semibold">
                            <i class="bi bi-person-fill"></i> CAJERO
                        </span>
                        <?php endif; ?>
                    </h1>
                    <p class="text-slate-300 text-sm mt-1">
                        <i class="bi bi-info-circle"></i> 
                        <?php if ($esAdministrador): ?>
                            Puedes ver y autorizar solicitudes de cancelación
                        <?php else: ?>
                            Ingresa el código PIN suministrado por el administrador
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="bg-slate-700/50 backdrop-blur-md px-5 py-3 rounded-xl">
                <div class="text-center">
                    <div class="text-3xl font-bold text-white" id="contador-solicitudes"><?= count($solicitudes) ?></div>
                    <div class="text-slate-300 text-xs uppercase tracking-wider">Pendientes</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicador de actualización en tiempo real -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl p-4 mb-6 border border-slate-700/50 shadow-lg">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div class="flex items-center text-slate-300">
                <div id="indicador-conexion" class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></div>
                <i class="bi bi-arrow-repeat mr-2"></i>
                <span class="font-semibold">Actualización automática:</span>
                <span class="ml-2 text-slate-400" id="intervalo-texto">
                    <?= $esAdministrador ? 'cada 10 segundos' : 'cada 30 segundos' ?>
                </span>
            </div>
            <div class="flex items-center space-x-4 text-slate-400 text-sm">
                <span id="ultima-actualizacion">
                    <i class="bi bi-clock-history mr-1"></i>
                    Cargando...
                </span>
                <span id="proxima-actualizacion" class="text-blue-400">
                    <i class="bi bi-hourglass-split mr-1"></i>
                    Próxima: --
                </span>
            </div>
        </div>
    </div>

    <?php if (empty($solicitudes)): ?>
    <!-- Sin solicitudes -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-12 text-center border border-slate-700/50">
        <?php if ($tabla_error): ?>
        <!-- Mensaje de error de tabla no encontrada -->
        <i class="bi bi-database text-6xl text-yellow-500 mb-4"></i>
        <h3 class="text-2xl font-bold text-white mb-3">Base de datos no configurada</h3>
        <p class="text-slate-400 mb-6">Las tablas necesarias para el sistema de autorizaciones no existen.</p>
        <div class="bg-slate-900/70 backdrop-blur-md rounded-xl p-5 text-left max-w-md mx-auto">
            <p class="text-green-400 mb-2 font-semibold">📁 Ejecuta el archivo SQL:</p>
            <code class="text-blue-300 bg-black/30 px-3 py-2 rounded-lg block">database_autorizaciones.sql</code>
            <p class="text-slate-400 mt-3 text-sm">Este archivo contiene las tablas necesarias para el funcionamiento del sistema.</p>
        </div>
        <?php else: ?>
        <!-- Mensaje de no hay solicitudes -->
        <i class="bi bi-check-circle text-6xl text-green-500 mb-4"></i>
        <h3 class="text-2xl font-bold text-white mb-2">No hay solicitudes pendientes</h3>
        <p class="text-slate-400">Todas las solicitudes de cancelación han sido procesadas</p>
        <div class="mt-6 inline-flex items-center space-x-2 bg-green-600/20 text-green-300 px-5 py-2 rounded-full">
            <i class="bi bi-shield-check"></i>
            <span>Sistema funcionando correctamente</span>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>        <!-- Formulario de autorización -->
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 mb-6 border border-slate-700/50">
            <div class="flex items-center mb-5">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                    <i class="bi bi-key-fill text-white"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white">Autorizar Cancelación</h3>
                    <p class="text-slate-400 text-sm">Ingresa el código PIN de 6 dígitos</p>
                </div>
            </div>
            <form id="form-autorizacion" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" 
                           id="codigo-pin" 
                           placeholder="••••••" 
                           class="w-full px-6 py-4 bg-slate-900/70 border-2 border-slate-600 hover:border-slate-500 focus:border-blue-500 rounded-xl text-white placeholder-slate-500 focus:ring-4 focus:ring-blue-500/20 transition-all text-center text-2xl font-mono tracking-[0.5em] shadow-inner"
                           maxlength="6"
                           autocomplete="off"
                           autofocus>
                </div>
                <button type="submit" 
                        class="px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-blue-500/50 transform hover:scale-[1.02] active:scale-[0.98]">
                    <i class="bi bi-unlock-fill mr-2"></i>Autorizar Cancelación
                </button>
            </form>
        </div>

        <!-- Lista de solicitudes -->
        <!-- Lista de solicitudes con diseño mejorado -->
        <div id="lista-solicitudes" class="grid gap-6">
            <?php 
            // Renderizar solicitudes iniciales con el mismo diseño que AJAX
            foreach ($solicitudes as $solicitud):
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
                            <?php if ($esAdministrador): ?>
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
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Animaciones personalizadas */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.solicitud-card {
    animation: slideInUp 0.3s ease-out;
}

/* Efecto de pulso para urgentes */
@keyframes urgentPulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
    }
}

.border-red-500 {
    animation: urgentPulse 2s infinite;
}

/* Countdown estilo */
.countdown-badge {
    font-variant-numeric: tabular-nums;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-autorizacion');
    const inputPin = document.getElementById('codigo-pin');
    const listaSolicitudes = document.getElementById('lista-solicitudes');
    
    // Determinar si es administrador
    const esAdministrador = <?= json_encode($esAdministrador) ?>;
    const intervaloActualizacion = esAdministrador ? 10000 : 30000; // 10s admin, 30s cajero
    
    let intervalId = null;
    let countdownIntervals = [];
    let proximaActualizacion = null;
    
    // Formulario de autorización
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const pin = inputPin.value.trim();
            if (pin.length !== 6) {
                Swal.fire({
                    icon: 'warning',
                    title: 'PIN Inválido',
                    text: 'El código PIN debe tener exactamente 6 dígitos',
                    confirmButtonColor: '#3b82f6',
                    background: '#1e293b',
                    color: '#ffffff'
                });
                return;
            }
            
            autorizarCancelacion(pin);
        });
        
        // Auto-focus en input
        inputPin.focus();
        
        // Sonido al escribir (opcional)
        inputPin.addEventListener('input', function() {
            if (this.value.length === 6) {
                // Vibración táctil en móviles
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
            }
        });
    }
    
    // Iniciar actualización automática
    iniciarActualizacionAutomatica();
    
    // Función para actualizar solicitudes
    function actualizarSolicitudes(silencioso = false) {
        const indicador = document.getElementById('indicador-conexion');
        const ultimaActualizacion = document.getElementById('ultima-actualizacion');
        
        // Cambiar indicador a amarillo (cargando)
        if (indicador) {
            indicador.className = 'w-3 h-3 bg-yellow-500 rounded-full mr-3';
        }
        
        if (!silencioso) {
            console.log('🔄 Actualizando solicitudes...');
        }
        
        const formData = new FormData();
        formData.append('ajax_update', '1');
        
        fetch('controllers/newPos/actualizar_autorizaciones.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar lista de solicitudes
                if (listaSolicitudes && data.html !== undefined) {
                    listaSolicitudes.innerHTML = data.html;
                }
                
                // Actualizar contador
                const contador = document.getElementById('contador-solicitudes');
                if (contador) {
                    contador.textContent = data.total_solicitudes || 0;
                }
                
                // Actualizar indicadores
                if (indicador) {
                    indicador.className = 'w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse';
                }
                
                if (ultimaActualizacion) {
                    const ahora = new Date();
                    ultimaActualizacion.innerHTML = `
                        <i class="bi bi-clock-history mr-1"></i>
                        ${ahora.toLocaleTimeString()}
                    `;
                }
                
                // IMPORTANTE: Reiniciar el contador de próxima actualización
                proximaActualizacion = Date.now() + intervaloActualizacion;
                
                // Reiniciar countdowns
                iniciarCountdowns();
                
                if (!silencioso) {
                    console.log(`✅ ${data.total_solicitudes} solicitud(es) actualizada(s)`);
                }
                
            } else {
                if (indicador) {
                    indicador.className = 'w-3 h-3 bg-red-500 rounded-full mr-3';
                }
                console.error('❌ Error en respuesta:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Error actualizando solicitudes:', error);
            if (indicador) {
                indicador.className = 'w-3 h-3 bg-red-500 rounded-full mr-3';
            }
        });
    }
    
    function iniciarActualizacionAutomatica() {
        
        // Primera actualización inmediata (después de 2s)
        setTimeout(() => {
            actualizarSolicitudes(true);
        }, 2000);
        
        // Actualización periódica
        intervalId = setInterval(() => {
            actualizarSolicitudes(true);
        }, intervaloActualizacion);
        
        // Actualizar indicador de próxima actualización
        actualizarProximaActualizacion();
        setInterval(actualizarProximaActualizacion, 1000);
        
    }
    
    function actualizarProximaActualizacion() {
        const proximaEl = document.getElementById('proxima-actualizacion');
        if (!proximaEl) return;
        
        if (!proximaActualizacion) {
            proximaActualizacion = Date.now() + intervaloActualizacion;
        }
        
        const segundosRestantes = Math.max(0, Math.floor((proximaActualizacion - Date.now()) / 1000));
        
        if (segundosRestantes === 0) {
            proximaActualizacion = Date.now() + intervaloActualizacion;
        }
        
        proximaEl.innerHTML = `
            <i class="bi bi-hourglass-split mr-1"></i>
            Próxima: ${segundosRestantes}s
        `;
    }
    
    function iniciarCountdowns() {
        // Limpiar countdowns anteriores
        countdownIntervals.forEach(clearInterval);
        countdownIntervals = [];
        
        // Iniciar nuevos countdowns
        document.querySelectorAll('.countdown').forEach(countdown => {
            const segundos = parseInt(countdown.dataset.segundos);
            let restante = segundos;
            
            const intervalId = setInterval(() => {
                restante--;
                
                if (restante <= 0) {
                    clearInterval(intervalId);
                    countdown.textContent = '00:00';
                    countdown.closest('.solicitud-card')?.classList.add('opacity-50');
                    return;
                }
                
                const mins = Math.floor(restante / 60);
                const secs = restante % 60;
                countdown.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                
                // Cambiar color según urgencia
                const badge = countdown.closest('.countdown-badge');
                if (badge) {
                    if (restante <= 60) {
                        badge.className = 'countdown-badge bg-red-600 animate-pulse text-white px-4 py-2 rounded-xl font-mono text-lg shadow-lg';
                    } else if (restante <= 120) {
                        badge.className = 'countdown-badge bg-orange-600 text-white px-4 py-2 rounded-xl font-mono text-lg shadow-lg';
                    }
                }
            }, 1000);
            
            countdownIntervals.push(intervalId);
        });
    }
    
    // Función global para copiar código (solo admin)
    window.copiarCodigo = function(codigo) {
        if (!esAdministrador) {
            Swal.fire({
                icon: 'error',
                title: 'Acceso Denegado',
                text: 'Solo administradores pueden ver los códigos PIN',
                confirmButtonColor: '#ef4444',
                background: '#1e293b',
                color: '#ffffff'
            });
            return;
        }
        
        navigator.clipboard.writeText(codigo).then(() => {
            const toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
            
            toast.fire({
                icon: 'success',
                title: `Código ${codigo} copiado`,
                background: '#1e293b',
                color: '#ffffff',
                iconColor: '#10b981'
            });
        }).catch(err => {
            console.error('Error al copiar:', err);
        });
    };
    
    // Función global para usar código
    window.usarCodigo = function(codigo) {
        inputPin.value = codigo;
        inputPin.focus();
        
        // Vibración táctil
        if (navigator.vibrate) {
            navigator.vibrate([50, 100, 50]);
        }
        
        // Auto-enviar si es administrador
        if (esAdministrador) {
            setTimeout(() => {
                autorizarCancelacion(codigo);
            }, 500);
        }
    };
    
    // Función para autorizar cancelación
    function autorizarCancelacion(pin) {
        Swal.fire({
            title: 'Procesando autorización...',
            html: '<div class="text-center"><i class="bi bi-hourglass-split text-4xl text-blue-500 animate-spin"></i></div>',
            allowOutsideClick: false,
            showConfirmButton: false,
            background: '#1e293b',
            color: '#ffffff'
        });
        
        fetch('controllers/newPos/autorizar_cancelacion.php', {
            method: 'POST',
            body: new URLSearchParams({
                codigo_pin: pin
            }),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const detalles = data.data?.detalles || {};
                
                Swal.fire({
                    icon: 'success',
                    title: '✅ Cancelación Autorizada',
                    html: `
                        <div class="text-left bg-slate-800 rounded-xl p-6 space-y-3">
                            <div class="flex justify-between border-b border-slate-700 pb-2">
                                <span class="text-slate-400">Mesa:</span>
                                <span class="text-white font-bold">${detalles.mesa || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-700 pb-2">
                                <span class="text-slate-400">Producto:</span>
                                <span class="text-blue-400 font-bold">${detalles.producto || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-700 pb-2">
                                <span class="text-slate-400">Solicitante:</span>
                                <span class="text-green-400">${detalles.solicitante || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between pt-2">
                                <span class="text-slate-400">Nuevo total:</span>
                                <span class="text-green-500 font-bold text-xl">$${data.nuevo_total || '0.00'}</span>
                            </div>
                        </div>
                    `,
                    confirmButtonColor: '#10b981',
                    background: '#1e293b',
                    color: '#ffffff',
                    timer: 5000,
                    timerProgressBar: true
                }).then(() => {
                    // Limpiar input y actualizar lista
                    inputPin.value = '';
                    inputPin.focus();
                    actualizarSolicitudes();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Autorización',
                    text: data.message || 'No se pudo procesar la autorización',
                    confirmButtonColor: '#ef4444',
                    background: '#1e293b',
                    color: '#ffffff'
                });
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo conectar con el servidor. Inténtalo de nuevo.',
                confirmButtonColor: '#ef4444',
                background: '#1e293b',
                color: '#ffffff'
            });
        });
    }
    
    // Iniciar countdowns en carga inicial
    iniciarCountdowns();
    
    // Detectar cuando el usuario vuelve a la pestaña
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            console.log('👁️ Ventana enfocada - Actualizando inmediatamente...');
            actualizarSolicitudes();
            proximaActualizacion = Date.now() + intervaloActualizacion;
        }
    });
});
</script>

