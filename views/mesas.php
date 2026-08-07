<?php
// Verificar si $pdo existe, si no, incluir conexión
if (!isset($pdo)) {
    require_once '../conexion.php';
    $pdo = conexion();
}

$mesas = $pdo->query("
    SELECT m.*, 
      (SELECT COUNT(*) FROM ordenes o WHERE o.mesa_id = m.id AND o.estado = 'abierta') as orden_abierta,
      (SELECT u.nombre_completo
       FROM ordenes o 
       LEFT JOIN usuarios u ON o.usuario_id = u.id
       WHERE o.mesa_id = m.id AND o.estado = 'abierta' 
       LIMIT 1) as mesero_nombre,
      (SELECT o.total FROM ordenes o WHERE o.mesa_id = m.id AND o.estado = 'abierta' ORDER BY o.id DESC LIMIT 1) as orden_total,
      (SELECT o.id FROM ordenes o WHERE o.mesa_id = m.id AND o.estado = 'abierta' ORDER BY o.id DESC LIMIT 1) as orden_id
    FROM mesas m
    ORDER BY m.nombre
")->fetchAll(PDO::FETCH_ASSOC);

// Obtener configuración de impresión térmica
include_once 'includes/ConfiguracionSistema.php';
$config = new ConfiguracionSistema($pdo);
$config_impresion = $config->obtenerTodasConfiguraciones();
$impresora_configurada = !empty($config_impresion['nombre_impresora'] ?? '');

// Verificar mensajes de la URL
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'orden_cerrada':
            $total = $_GET['total'] ?? '0.00';
            $success_message = "Orden cerrada exitosamente. Total: $" . htmlspecialchars($total);
            break;
        case 'mesa_creada':
            $mesa_nombre = $_GET['mesa_nombre'] ?? '';
            $success_message = "Mesa '" . htmlspecialchars($mesa_nombre) . "' creada exitosamente";
            break;
        case 'mesa_eliminada':
            $success_message = "Mesa eliminada exitosamente";
            break;
        default:
            $success_message = "Operación completada exitosamente";
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'orden_no_especificada':
            $error_message = "No se especificó la orden a cerrar";
            break;
        case 'mesa_invalida':
            $error_message = "ID de mesa inválido";
            break;
        case 'mesa_no_encontrada':
            $error_message = "La mesa especificada no existe";
            break;
        case 'mesa_con_orden':
            $error_message = "No se puede eliminar una mesa con órdenes abiertas";
            break;
        case 'error_eliminar':
            $error_message = "Error al eliminar la mesa";
            break;
        case 'nombre_vacio':
            $error_message = "El nombre de la mesa no puede estar vacío";
            break;
        case 'error_crear':
            $error_message = "Error al crear la mesa";
            break;
        case 'datos_invalidos':
            $error_message = "Datos inválidos proporcionados";
            break;
        case 'mesa_existe':
            $error_message = "Ya existe una mesa con ese nombre";
            break;
        default:
            $error_message = htmlspecialchars($_GET['error']);
    }
}
?>

<!-- SweetAlert2 para mensajes -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* === KIOSK MODE GLOBAL === */
body, html {
    overflow-y: auto !important;
    height: 100vh;
    width: 100vw;
    margin: 0;
    padding: 0;
}

/* Container principal del kiosk */
.kiosk-mesas-container {
    min-height: calc(100vh - 4rem);
    padding: 1rem;
    padding-bottom: 2rem;
}

/* Header de sección compacto */
.kiosk-section-header {
    position: sticky;
    top: 4rem;
    z-index: 30;
    background: rgba(17, 24, 39, 0.95);
    backdrop-filter: blur(10px);
    padding: 1rem;
    border-radius: 1rem;
    margin-bottom: 1rem;
}

/* Grid de mesas optimizado para kiosk */
.kiosk-mesas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (min-width: 640px) {
    .kiosk-mesas-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (min-width: 1024px) {
    .kiosk-mesas-grid {
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 2rem;
    }
}

@media (min-width: 1536px) {
    .kiosk-mesas-grid {
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    }
}

/* Cards de mesa touch-friendly */
.kiosk-mesa-card {
    min-height: 220px;
    transition: all 0.2s ease;
    cursor: pointer;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.kiosk-mesa-card:active {
    transform: scale(0.98);
}

.mesa-card-shell {
    position: relative;
    overflow: hidden;
    border-radius: 1.5rem;
    padding: 1.25rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.95), rgba(31, 41, 55, 0.9));
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.22);
}

.mesa-card-shell::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, rgba(255,255,255,0.06), transparent 45%, rgba(255,255,255,0.03));
    pointer-events: none;
}

.mesa-card-libre {
    box-shadow: 0 18px 45px rgba(16, 185, 129, 0.12);
}

.mesa-card-ocupada {
    box-shadow: 0 18px 45px rgba(244, 63, 94, 0.15);
}

.mesa-card-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.7rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
    border: 1px solid rgba(255,255,255,0.16);
    backdrop-filter: blur(8px);
}

.mesa-card-description {
    display: flex;
    align-items: flex-start;
    gap: 0.55rem;
    padding: 0.75rem 0.85rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.06);
    color: #e5e7eb;
    font-size: 0.92rem;
    margin-bottom: 0.9rem;
}

.mesa-card-description-muted {
    color: #cbd5e1;
}

.mesa-card-info-list {
    display: grid;
    gap: 0.6rem;
    margin-bottom: 1rem;
}

.mesa-card-info-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.7rem 0.8rem;
    border-radius: 0.95rem;
    background: rgba(255, 255, 255, 0.045);
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.mesa-card-info-label {
    display: inline-flex;
    align-items: center;
    color: #cbd5e1;
    font-size: 0.92rem;
}

/* Botones touch-optimizados */
.kiosk-touch-button {
    min-height: 56px;
    padding: 1rem 1.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
    border: none;
    border-radius: 0.9rem;
    color: #ffffff;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    transition: all 0.2s ease;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.kiosk-touch-button:hover {
    transform: translateY(-1px);
    filter: brightness(1.05);
}

.kiosk-touch-button:active {
    transform: scale(0.95);
}

/* Estadísticas compactas para kiosk */
.kiosk-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.kiosk-stat-card {
    padding: 1rem;
    text-align: center;
}

/* Optimización para tablets */
@media (max-width: 1024px) {
    .kiosk-section-header {
        top: 3.5rem;
    }
    
    .kiosk-mesas-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}

/* Optimización para móviles */
@media (max-width: 640px) {
    .kiosk-mesas-container {
        padding: 0.75rem;
    }
    
    .kiosk-mesas-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .kiosk-stats {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Hide scrollbar pero mantener funcionalidad */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(31, 41, 55, 0.5);
}

::-webkit-scrollbar-thumb {
    background: rgba(59, 130, 246, 0.5);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(59, 130, 246, 0.7);
}
</style>

<?php if ($success_message): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toast de confirmación
    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    toast.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: '<?= addslashes($success_message) ?>',
        background: '#1f2937',
        color: '#ffffff',
        iconColor: '#10b981'
    });

    <?php if (isset($_GET['success']) && $_GET['success'] === 'orden_cerrada'): ?>
    // 🔄 Actualización automática cuando se cierra una orden (sin recargar la página)
    console.log('🔄 Orden cerrada detectada - Actualizando vista de mesas...');
    
    // Limpiar URL sin recargar página
    const url = new URL(window.location);
    url.searchParams.delete('success');
    url.searchParams.delete('total');
    url.searchParams.delete('mesa_id');
    window.history.replaceState({}, '', url);
    
    // Esperar 500ms para que se confirme el guardado en BD y refrescar vía AJAX
    setTimeout(() => {
        if (typeof window.actualizarEstadoMesas === 'function') {
            window.actualizarEstadoMesas();
        }
    }, 500);
    <?php endif; ?>
});
</script>
<?php endif; ?>

<?php if ($error_message): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?= addslashes($error_message) ?>',
        confirmButtonColor: '#ef4444',
        background: '#1f2937',
        color: '#ffffff'
    });
});
</script>
<?php endif; ?>

<!-- === KIOSK CONTAINER === -->
<div class="kiosk-mesas-container">

<!-- Statistics Section - Kiosk Optimized -->
<div class="kiosk-stats">
  <?php
  $totalMesas = count($mesas);
  $mesasOcupadas = array_sum(array_column($mesas, 'orden_abierta'));
  $mesasLibres = $totalMesas - $mesasOcupadas;
  ?>

  <div class="kiosk-stat-card bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 shadow-xl">
    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl mx-auto mb-3 flex items-center justify-center">
      <i class="bi bi-grid-3x3 text-white text-2xl"></i>
    </div>
    <h3 class="text-3xl font-bold text-white"><?= $totalMesas ?></h3>
    <p class="text-gray-400 text-sm mt-1">Total de Mesas</p>
  </div>

  <div class="kiosk-stat-card bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 shadow-xl">
    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl mx-auto mb-3 flex items-center justify-center">
      <i class="bi bi-exclamation-triangle text-white text-2xl"></i>
    </div>
    <h3 class="text-3xl font-bold text-white"><?= $mesasOcupadas ?></h3>
    <p class="text-gray-400 text-sm mt-1">Mesas Ocupadas</p>
  </div>

  <div class="kiosk-stat-card bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 shadow-xl">
    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mx-auto mb-3 flex items-center justify-center">
      <i class="bi bi-check-circle text-white text-2xl"></i>
    </div>
    <h3 class="text-3xl font-bold text-white"><?= $mesasLibres ?></h3>
    <p class="text-gray-400 text-sm mt-1">Mesas Disponibles</p>
  </div>
</div>

<div class="mb-6">
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 shadow-xl">
    <div class="flex items-center mb-4">
      <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-3">
        <i class="bi bi-plus-lg text-white text-xl"></i>
      </div>
      <h3 class="text-lg font-semibold text-white">Crear Nueva Mesa</h3>
    </div>

    <form id="crearMesaForm" class="flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <input type="text"
          name="nombre"
          id="nombreMesa"
          class="kiosk-touch-button w-full bg-dark-600/50 border border-dark-500/50 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
          placeholder="Nombre de la nueva mesa (ej: Mesa 1, Terraza A)"
          required>
      </div>
      <button type="submit"
        class="kiosk-touch-button bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
        <i class="bi bi-plus-circle mr-2"></i>
        Agregar Mesa
      </button>
    </form>
  </div>
</div>

<div class="kiosk-mesas-grid">
  <?php foreach ($mesas as $mesa):
    if ($mesa['orden_abierta'] > 0) {
      $estado = 'ocupada';
      $statusColor = 'from-red-500 to-pink-600';
      $borderColor = 'border-red-500/30';
      $bgColor = 'bg-red-500/5';
      $iconColor = 'text-red-400';
      $statusText = 'Ocupada';
      $statusHint = 'Orden activa';
      $btnText = 'Ver POS';
      $btnIcon = 'bi-eye';
      $btnStyle = 'background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);';
      $pillClass = 'bg-rose-500/15 text-rose-100 border-rose-400/30';
      $cardClass = 'mesa-card-ocupada';
      $accentClass = 'bg-gradient-to-br from-red-500 to-pink-600';
      $detailTextClass = 'text-red-300';
    } else {
      $estado = 'libre';
      $statusColor = 'from-green-500 to-emerald-600';
      $borderColor = 'border-green-500/30';
      $bgColor = 'bg-green-500/5';
      $iconColor = 'text-green-400';
      $statusText = 'Disponible';
      $statusHint = 'Sin orden activa';
      $btnText = 'Abrir POS';
      $btnIcon = 'bi-arrow-right-circle';
      $btnStyle = 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);';
      $pillClass = 'bg-emerald-500/15 text-emerald-100 border-emerald-400/30';
      $cardClass = 'mesa-card-libre';
      $accentClass = 'bg-gradient-to-br from-emerald-500 to-cyan-600';
      $detailTextClass = 'text-emerald-300';
    }
  ?>
    <!-- Mesa Card - Kiosk -->
    <div class="kiosk-mesa-card group" data-mesa-id="<?= $mesa['id'] ?>" data-mesa-nombre="<?= htmlspecialchars($mesa['nombre']) ?>" data-orden-abierta="<?= $mesa['orden_abierta'] ?>" data-orden-total="<?= $mesa['orden_total'] ?? 0 ?>">
      <div class="mesa-card-shell <?= $cardClass ?>" id="mesa-card-<?= $mesa['id'] ?>">
        <button type="button" class="mesa-delete-btn absolute top-3 right-3 z-20 w-8 h-8 rounded-full bg-red-500/80 hover:bg-red-600 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
          title="Eliminar mesa"
          onclick="event.stopPropagation(); event.preventDefault(); eliminarMesa(this.closest('.kiosk-mesa-card'));"
          <?= $mesa['orden_abierta'] > 0 ? 'style="display:none;"' : '' ?>>
          <i class="bi bi-trash text-sm"></i>
        </button>
        <div class="flex items-start justify-between mb-4 relative z-10">
          <div class="flex items-center space-x-3">
            <div class="w-14 h-14 <?= $accentClass ?> rounded-2xl flex items-center justify-center shadow-lg mesa-card-accent">
              <i class="bi bi-table text-white text-2xl"></i>
            </div>
            <div>
              <p class="text-[11px] uppercase tracking-[0.3em] text-gray-400 mb-1">Mesa</p>
              <h3 class="text-xl font-bold text-white group-hover:text-blue-400 transition-colors">
                <?= htmlspecialchars($mesa['nombre']) ?>
              </h3>
            </div>
          </div>

          <span class="mesa-card-pill <?= $pillClass ?>">
            <span class="w-2.5 h-2.5 rounded-full bg-white/90"></span>
            <span class="mesa-status-text"><?= $statusText ?></span>
          </span>
        </div>

        <?php if (!empty($mesa['descripcion'])): ?>
          <div class="mesa-card-description">
            <i class="bi bi-info-circle <?= $iconColor ?>"></i>
            <span><?= htmlspecialchars($mesa['descripcion']) ?></span>
          </div>
        <?php else: ?>
          <div class="mesa-card-description mesa-card-description-muted">
            <i class="bi bi-info-circle <?= $iconColor ?>"></i>
            <span><?= $mesa['orden_abierta'] > 0 ? 'Orden abierta con información activa' : 'Sin observaciones registradas' ?></span>
          </div>
        <?php endif; ?>

        <div class="mesa-card-info-list relative z-10">
          <div class="mesa-card-info-item">
            <span class="mesa-card-info-label">
              <i class="bi bi-circle-fill mr-2"></i>
              Estado
            </span>
            <span class="<?= $iconColor ?> font-semibold mesa-estado-valor"><?= $statusText ?></span>
          </div>

          <div class="mesa-card-info-item mesa-total-row" <?= $mesa['orden_abierta'] > 0 ? '' : 'style="display:none;"' ?>>
            <span class="mesa-card-info-label">
              <i class="bi bi-cash-stack mr-2"></i>
              Total
            </span>
            <span class="<?= $detailTextClass ?> font-semibold mesa-total-valor">$<?= $mesa['orden_total'] ?? '0.00' ?></span>
          </div>
          <div class="mesa-card-info-item mesa-mesero-row" <?= (!empty($mesa['mesero_nombre']) && trim($mesa['mesero_nombre']) !== '') ? '' : 'style="display:none;"' ?>>
            <span class="mesa-card-info-label">
              <i class="bi bi-person-badge mr-2"></i>
              Mesero
            </span>
            <span class="text-blue-300 font-semibold flex items-center mesa-mesero-valor">
              <?= htmlspecialchars(trim($mesa['mesero_nombre'] ?? '')) ?>
            </span>
          </div>
          <div class="mesa-card-info-item mesa-disponibilidad-row" <?= $mesa['orden_abierta'] > 0 ? 'style="display:none;"' : '' ?>>
            <span class="mesa-card-info-label">
              <i class="bi bi-check-circle mr-2"></i>
              Disponibilidad
            </span>
            <span class="<?= $detailTextClass ?> font-semibold">Lista para abrir</span>
          </div>
        </div>

        <div class="mt-auto pt-2 space-y-2 relative z-10">
          <a href="index.php?page=mesa&id=<?= $mesa['id'] ?>"
            class="kiosk-touch-button w-full mesa-pos-btn"
            style="<?= $btnStyle ?>">
            <i class="bi <?= $btnIcon ?> text-lg mesa-pos-btn-icon"></i>
            <span class="mesa-pos-btn-text"><?= $btnText ?></span>
          </a>

          <button onclick="event.stopPropagation(); event.preventDefault(); imprimirTicketTermico(<?= $mesa['orden_id'] ?? 0 ?>)"
            class="kiosk-touch-button w-full mesa-thermal-btn"
            style="background: linear-gradient(135deg, #8b5cf6 0%, #4f46e5 100%); <?= ($mesa['orden_abierta'] > 0 && isset($mesa['orden_id'])) ? '' : 'display:none;' ?>">
            <i class="bi bi-receipt text-lg"></i>
            Térmica
          </button>
        </div>
      </div>
    </div>
  <?php endforeach; ?>


  <!-- Add New Table Card - Kiosk -->
  <div class="kiosk-mesa-card group cursor-pointer" onclick="document.getElementById('nombreMesa').focus()">
    <div class="bg-dark-700/20 backdrop-blur-xl rounded-2xl border-2 border-dashed border-dark-600/50 p-6 h-full flex flex-col items-center justify-center text-center hover:border-blue-500/50 transition-all duration-200 min-h-[200px]">
      <div class="w-20 h-20 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
        <i class="bi bi-plus-lg text-blue-400 text-3xl"></i>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Agregar Mesa</h3>
      <p class="text-gray-400">Crea una nueva mesa para el restaurante</p>
    </div>
  </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupFormHandlers() {
        const form = document.getElementById('crearMesaForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const nombreInput = document.getElementById('nombreMesa');
                const nombre = nombreInput.value.trim();
                
                if (!nombre) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Nombre requerido',
                        text: 'Por favor ingresa un nombre para la mesa',
                        background: '#1f2937',
                        color: '#ffffff'
                    });
                    return;
                }
                
                // Mostrar loading
                Swal.fire({
                    title: 'Creando mesa...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    background: '#1f2937',
                    color: '#ffffff',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Crear mesa
                fetch('controllers/crear_mesa.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `nombre=${encodeURIComponent(nombre)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        nombreInput.value = '';
                        
                        Swal.fire({
                            icon: 'success',
                            title: '¡Mesa creada!',
                            text: `Mesa "${nombre}" creada correctamente`,
                            timer: 2000,
                            showConfirmButton: false,
                            background: '#1f2937',
                            color: '#ffffff'
                        }).then(() => {
                            // Recargar la página para mostrar la nueva mesa
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'Error al crear la mesa',
                            background: '#1f2937',
                            color: '#ffffff'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor',
                        background: '#1f2937',
                        color: '#ffffff'
                    });
                });
            });
        }
    }

    setupFormHandlers();

    function eliminarMesa(mesaElement) {
        const mesaId = mesaElement.dataset.mesaId;
        const mesaNombre = mesaElement.dataset.mesaNombre;

        Swal.fire({
            title: '¿Eliminar Mesa?',
            text: `¿Está seguro de que desea eliminar "${mesaNombre}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            background: '#1f2937',
            color: '#ffffff'
        }).then((result) => {
            if (result.isConfirmed) {
                // Hacer petición AJAX para eliminar
                fetch('controllers/crear_mesa.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=eliminar&mesa_id=${mesaId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mesaElement.remove();
                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminada!',
                            text: `Mesa "${mesaNombre}" eliminada correctamente`,
                            timer: 2000,
                            showConfirmButton: false,
                            background: '#1f2937',
                            color: '#ffffff'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'Error al eliminar la mesa',
                            background: '#1f2937',
                            color: '#ffffff'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión al eliminar la mesa',
                        background: '#1f2937',
                        color: '#ffffff'
                    });
                });
            }
        });
    }

    // === ACTUALIZACIÓN AUTOMÁTICA DE ESTADO DE MESAS (AJAX, sin recargar la página) ===
    let autoUpdateInterval = null;
    let ultimaActualizacion = Date.now();

    // Actualiza la tarjeta del grid superior (kiosk-mesa-card) con los datos recibidos
    function actualizarTarjetaKiosk(mesaData) {
        const card = document.querySelector(`.kiosk-mesa-card[data-mesa-id="${mesaData.id}"]`);
        if (!card) return;

        const shell = card.querySelector('.mesa-card-shell');
        const ocupada = mesaData.estado === 'ocupada';

        if (shell) {
            shell.classList.toggle('mesa-card-ocupada', ocupada);
            shell.classList.toggle('mesa-card-libre', !ocupada);
        }

        const accent = card.querySelector('.mesa-card-accent');
        if (accent) {
            accent.classList.toggle('from-red-500', ocupada);
            accent.classList.toggle('to-pink-600', ocupada);
            accent.classList.toggle('from-emerald-500', !ocupada);
            accent.classList.toggle('to-cyan-600', !ocupada);
        }

        const pill = card.querySelector('.mesa-card-pill');
        const pillText = card.querySelector('.mesa-status-text');
        if (pill && pillText) {
            pill.classList.toggle('bg-rose-500/15', ocupada);
            pill.classList.toggle('text-rose-100', ocupada);
            pill.classList.toggle('border-rose-400/30', ocupada);
            pill.classList.toggle('bg-emerald-500/15', !ocupada);
            pill.classList.toggle('text-emerald-100', !ocupada);
            pill.classList.toggle('border-emerald-400/30', !ocupada);
            pillText.textContent = ocupada ? 'Ocupada' : 'Disponible';
        }

        const estadoValor = card.querySelector('.mesa-estado-valor');
        if (estadoValor) {
            estadoValor.textContent = ocupada ? 'Ocupada' : 'Disponible';
            estadoValor.classList.toggle('text-red-400', ocupada);
            estadoValor.classList.toggle('text-green-400', !ocupada);
        }

        const totalRow = card.querySelector('.mesa-total-row');
        const totalValor = card.querySelector('.mesa-total-valor');
        if (totalRow && totalValor) {
            totalRow.style.display = ocupada ? '' : 'none';
            totalValor.textContent = '$' + Number(mesaData.total || 0).toFixed(2);
            totalValor.classList.toggle('text-red-300', ocupada);
            totalValor.classList.toggle('text-emerald-300', !ocupada);
        }

        const meseroRow = card.querySelector('.mesa-mesero-row');
        const meseroValor = card.querySelector('.mesa-mesero-valor');
        const tieneMesero = ocupada && mesaData.mesero_nombre && mesaData.mesero_nombre.trim() !== '';
        if (meseroRow && meseroValor) {
            meseroRow.style.display = tieneMesero ? '' : 'none';
            meseroValor.textContent = tieneMesero ? mesaData.mesero_nombre : '';
        }

        const dispRow = card.querySelector('.mesa-disponibilidad-row');
        if (dispRow) dispRow.style.display = ocupada ? 'none' : '';

        const posBtn = card.querySelector('.mesa-pos-btn');
        const posBtnText = card.querySelector('.mesa-pos-btn-text');
        const posBtnIcon = card.querySelector('.mesa-pos-btn-icon');
        if (posBtn) {
            posBtn.style.background = ocupada
                ? 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)'
                : 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
        }
        if (posBtnText) posBtnText.textContent = ocupada ? 'Ver POS' : 'Abrir POS';
        if (posBtnIcon) {
            posBtnIcon.classList.toggle('bi-eye', ocupada);
            posBtnIcon.classList.toggle('bi-arrow-right-circle', !ocupada);
        }

        const thermalBtn = card.querySelector('.mesa-thermal-btn');
        if (thermalBtn) {
            const mostrar = ocupada && mesaData.orden_id;
            thermalBtn.style.display = mostrar ? '' : 'none';
            if (mostrar) {
                thermalBtn.setAttribute('onclick', `event.stopPropagation(); event.preventDefault(); imprimirTicketTermico(${mesaData.orden_id})`);
            }
        }
    }

    function actualizarEstadoMesas(silencioso = false) {
        if (!silencioso) {
            console.log('🔄 Verificando estado actual de mesas...');
        }
        
        // Hacer petición a API con anti-caché
        fetch('/POSSystemKalli/api/estado_mesas.php?_=' + Date.now())
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('❌ Error en respuesta:', data.error);
                return;
            }
            
            if (!silencioso) {
                console.log('✅ Estado recibido:', data);
            }
            
            ultimaActualizacion = Date.now();
            
            // Actualizar estadísticas
            const statsElements = document.querySelectorAll('.text-3xl.font-bold.text-white');
            if (statsElements.length >= 3) {
                const cambios = [];
                
                if (statsElements[0].textContent !== data.estadisticas.total.toString()) {
                    cambios.push(`Total: ${statsElements[0].textContent} → ${data.estadisticas.total}`);
                    statsElements[0].textContent = data.estadisticas.total;
                }
                
                if (statsElements[1].textContent !== data.estadisticas.ocupadas.toString()) {
                    cambios.push(`Ocupadas: ${statsElements[1].textContent} → ${data.estadisticas.ocupadas}`);
                    statsElements[1].textContent = data.estadisticas.ocupadas;
                }
                
                if (statsElements[2].textContent !== data.estadisticas.disponibles.toString()) {
                    cambios.push(`Disponibles: ${statsElements[2].textContent} → ${data.estadisticas.disponibles}`);
                    statsElements[2].textContent = data.estadisticas.disponibles;
                }
                
                if (cambios.length > 0 && !silencioso) {
                    console.log('📊 Estadísticas actualizadas:', cambios);
                }
            }
            
            // Actualizar cada mesa en el DOM (grid kiosk), sin recargar la página
            let hayDiferencias = false;
            
            data.mesas.forEach(mesaData => {
                const card = document.querySelector(`.kiosk-mesa-card[data-mesa-id="${mesaData.id}"]`);
                if (card) {
                    const estadoActual = card.dataset.ordenAbierta > 0 ? 'ocupada' : 'libre';
                    const totalActual = parseFloat(card.dataset.ordenTotal) || 0;
                    const totalNuevo = parseFloat(mesaData.total) || 0;

                    if (estadoActual !== mesaData.estado || Math.abs(totalActual - totalNuevo) > 0.001) {
                        hayDiferencias = true;
                    }

                    card.dataset.ordenAbierta = mesaData.ordenes_abiertas;
                    card.dataset.ordenTotal = mesaData.total;
                }

                actualizarTarjetaKiosk(mesaData);
            });
            
            // Si hubo cambios, avisar discretamente (sin recargar)
            if (hayDiferencias && !silencioso) {
                const toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true
                });
                
                toast.fire({
                    icon: 'info',
                    title: 'Mesas actualizadas',
                    background: '#1f2937',
                    color: '#ffffff',
                    iconColor: '#3b82f6'
                });
            }
        })
        .catch(error => {
            console.error('❌ Error actualizando estado de mesas:', error);
        });
    }
    
    function iniciarActualizacionAutomatica(intervalSeconds = 15) {
        if (autoUpdateInterval) {
            console.log('⚠️ Actualización automática ya está activa');
            return;
        }
        
        console.log(`🔄 Iniciando actualización automática cada ${intervalSeconds} segundos`);
        autoUpdateInterval = setInterval(() => {
            actualizarEstadoMesas(true); // Silencioso para no llenar consola
        }, intervalSeconds * 1000);
        
        // Mostrar notificación
        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        
        toast.fire({
            icon: 'success',
            title: `Auto-actualización activada (cada ${intervalSeconds}s)`,
            background: '#1f2937',
            color: '#ffffff',
            iconColor: '#10b981'
        });
    }
    
    function detenerActualizacionAutomatica() {
        if (autoUpdateInterval) {
            clearInterval(autoUpdateInterval);
            autoUpdateInterval = null;
            console.log('⏹️ Actualización automática detenida');
            
            const toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
            
            toast.fire({
                icon: 'info',
                title: 'Auto-actualización detenida',
                background: '#1f2937',
                color: '#ffffff',
                iconColor: '#f59e0b'
            });
        }
    }
    
    // === EVENTOS DE VISIBILIDAD Y FOCO ===
    // Actualizar cuando el usuario vuelve a la pestaña
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            const tiempoInactivo = Math.floor((Date.now() - ultimaActualizacion) / 1000);
            console.log(`👁️ Usuario volvió a la pestaña (inactivo ${tiempoInactivo}s) - Actualizando...`);
            actualizarEstadoMesas();
        }
    });
    
    // Actualizar cuando la ventana recupera el foco
    window.addEventListener('focus', () => {
        const tiempoInactivo = Math.floor((Date.now() - ultimaActualizacion) / 1000);
        
        // Solo actualizar si han pasado más de 5 segundos
        if (tiempoInactivo > 5) {
            console.log(`🎯 Ventana enfocada (inactivo ${tiempoInactivo}s) - Actualizando...`);
            actualizarEstadoMesas();
        }
    });
    
    // === INICIAR AUTO-ACTUALIZACIÓN AL CARGAR ===
    // Activar automáticamente polling cada 15 segundos
    setTimeout(() => {
        iniciarActualizacionAutomatica(15); // 15 segundos por defecto
    }, 2000); // Esperar 2 segundos después de carga inicial

    // === FUNCIONES GLOBALES ===
    window.eliminarMesa = eliminarMesa;
    window.actualizarEstadoMesas = actualizarEstadoMesas;
    window.iniciarActualizacionAutomatica = iniciarActualizacionAutomatica;
    window.detenerActualizacionAutomatica = detenerActualizacionAutomatica;
});
</script>

<!-- Configuración global de impresora para imprimirTicketTermico() -->
<script>
    // Variable global necesaria para la función imprimirTicketTermico() de js/impresion-termica.js
    window.configImpresoraNombre = '<?= $config_impresion['nombre_impresora'] ?? '' ?>';
</script>

<!-- === END KIOSK CONTAINER === -->
</div>

<!-- Script de Impresión Térmica -->
<script src="js/impresion-termica.js"></script>
