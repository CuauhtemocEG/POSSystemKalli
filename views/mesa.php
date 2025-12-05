<?php
// Nota: auth-check.php y conexion.php ya están incluidos en index.php
// $pdo ya está disponible desde index.php

if (!hasPermission('ordenes', 'crear') && !hasPermission('ordenes', 'ver')) {
    header('Location: index.php?page=error-403');
    exit;
}

$userInfo = getUserInfo();
$esAdministrador = ($userInfo['rol'] === 'administrador');
$mesa_id = intval($_GET['id'] ?? 0);

if ($mesa_id <= 0) {
    header('Location: index.php?page=mesas&error=mesa_invalida');
    exit;
}

$mesa = $pdo->query("SELECT * FROM mesas WHERE id=$mesa_id")->fetch();

// Validar que la mesa exista
if (!$mesa) {
    header('Location: index.php?page=mesas&error=mesa_no_encontrada');
    exit;
}

// Obtener orden con información del mesero
$stmtOrden = $pdo->prepare("
    SELECT o.*, u.nombre_completo as mesero_nombre
    FROM ordenes o
    LEFT JOIN usuarios u ON o.usuario_id = u.id
    WHERE o.mesa_id = ? AND o.estado = 'abierta'
");
$stmtOrden->execute([$mesa_id]);
$orden = $stmtOrden->fetch();
$orden_id = $orden ? $orden['id'] : 0;
$mesero_nombre = 'Sin asignar';
if ($orden && !empty($orden['mesero_nombre'])) {
    $mesero_nombre = trim($orden['mesero_nombre']);
}

// Obtener configuración de impresión térmica
include_once 'includes/ConfiguracionSistema.php';
$config = new ConfiguracionSistema($pdo);
$config_impresion = $config->obtenerTodasConfiguraciones();
$impresion_automatica = ($config_impresion['impresion_automatica'] ?? '0') == '1';
$impresora_configurada = !empty($config_impresion['nombre_impresora'] ?? '');
?>

<!-- 🎨 Kiosk Mode Styles - Fullscreen UI sin scroll -->
<style>
    /* 🌐 KIOSK MODE - Fullscreen sin scroll */
    body, html {
        overflow: hidden !important;
        height: 100vh;
        width: 100vw;
        margin: 0;
        padding: 0;
    }

    .kiosk-container {
        height: 100vh;
        width: 100vw;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding-top: 4rem; /* Espacio para el navbar (64px) */
    }

    /* 📐 Header fijo optimizado */
    .kiosk-header {
        flex-shrink: 0;
        height: 70px;
        position: relative;
        z-index: 40; /* Por debajo del navbar (z-50) pero arriba del contenido */
    }

    /* 📐 Área de contenido principal */
    .kiosk-content {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        padding: 1rem;
    }

    /* 📐 Grid responsive del POS */
    .kiosk-pos-grid {
        height: 100%;
        display: grid;
        gap: 1rem;
        grid-template-columns: 1fr;
    }

    /* Permitir scroll en grid para móviles y tablets */
    @media (max-width: 1279px) {
        .kiosk-pos-grid {
            overflow-y: auto !important;
            max-height: 80vh;
        }
    }

    @media (max-width: 767px) {
        .kiosk-pos-grid {
            max-height: 75vh;
        }
    }

    /* Tablet y superiores */
    @media (min-width: 768px) {
        .kiosk-pos-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    /* Desktop grande */
    @media (min-width: 1280px) {
        .kiosk-pos-grid {
            grid-template-columns: 38fr 62fr;
        }
    }

    /* 📦 Paneles internos con scroll */
    .kiosk-panel {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
        background: rgba(30, 41, 59, 0.95);
        border-radius: 1rem;
        border: 1px solid rgba(71, 85, 105, 0.5);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    }

    .kiosk-panel-header {
        flex-shrink: 0;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(71, 85, 105, 0.3);
    }

    .kiosk-panel-content {
        flex: 1;
        min-height: 0; /* CRÍTICO: Permite que flex children hagan scroll */
        overflow: hidden; /* Contener el scroll dentro */
        display: flex;
        flex-direction: column;
    }

    /* 📜 Áreas de scroll internas */
    .orden-scroll-area,
    .catalogo-scroll-area {
        flex: 1;
        overflow-y: auto !important; /* Forzar scroll vertical */
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
        padding: 0.5rem 1rem;
        position: relative; /* Para que el scroll funcione correctamente */
    }
    
    /* Asegurar que el área de scroll tenga altura */
    .catalogo-scroll-area {
        height: 100%; /* Importante para que el scroll funcione */
        max-height: 100%;
        min-height: 200px; /* Mínimo para visualizar contenido */
    }

    /* 🎯 Scrollbars ultra delgados y sutiles */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    ::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.3);
        border-radius: 3px;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(71, 85, 105, 0.5);
        border-radius: 3px;
        transition: background 0.2s;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(71, 85, 105, 0.7);
    }

    /* Firefox */
    * {
        scrollbar-width: thin;
        scrollbar-color: rgba(71, 85, 105, 0.5) rgba(15, 23, 42, 0.3);
    }

    /* 🌊 Indicadores de scroll con gradientes */
    .scroll-fade-top,
    .scroll-fade-bottom {
        position: absolute;
        left: 0;
        right: 0;
        height: 40px;
        pointer-events: none;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .scroll-fade-top {
        top: 0;
        background: linear-gradient(to bottom, rgba(30, 41, 59, 0.95) 0%, rgba(30, 41, 59, 0.7) 50%, transparent 100%);
    }

    .scroll-fade-bottom {
        bottom: 0;
        background: linear-gradient(to top, rgba(30, 41, 59, 0.95) 0%, rgba(30, 41, 59, 0.7) 50%, transparent 100%);
    }

    /* Text clamp */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .aspect-square {
        aspect-ratio: 1 / 1;
    }

    /* 🎨 Product cards con animaciones suaves */
    .product-card {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
        will-change: transform;
    }

    .product-card:active {
        transform: scale(0.96);
    }

    .product-card:hover {
        box-shadow: 0 20px 40px -12px rgba(59, 130, 246, 0.5);
        transform: translateY(-4px);
    }

    /* 🎯 Optimizaciones táctiles */
    button, a, .product-card, input {
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }

    /* 📱 Grid responsivo optimizado para productos */
    .kiosk-products-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        width: 100%; /* Asegurar ancho completo */
        /* NO usar height aquí - debe ser auto para permitir scroll */
    }

    @media (min-width: 480px) {
        .kiosk-products-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        }
    }

    @media (min-width: 640px) {
        .kiosk-products-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .kiosk-products-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }

    @media (min-width: 1536px) {
        .kiosk-products-grid {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        }
    }

    /* 🎭 Animaciones suaves */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }

    /* 🔒 Prevenir selección de texto en modo kiosk */
    .kiosk-no-select {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    /* 🌙 Prevent pull-to-refresh en móvil */
    body {
        overscroll-behavior-y: none;
        overscroll-behavior-x: none;
    }

    /* 📱 Optimización para pantallas táctiles */
    @media (hover: none) and (pointer: coarse) {
        .product-card:hover {
            box-shadow: 0 10px 25px -12px rgba(0, 0, 0, 0.25);
            transform: none;
        }
        
        .product-card:active {
            transform: scale(0.97);
        }
    }

    /* 🔘 Botones de categorías responsive y elegantes */
    .category-btn {
        white-space: nowrap;
        font-size: 0.875rem;
        position: relative;
        overflow: hidden;
        font-weight: 600;
        letter-spacing: 0.025em;
        background: linear-gradient(135deg, rgba(71, 85, 105, 0.4) 0%, rgba(51, 65, 85, 0.6) 100%);
        border: 1px solid rgba(148, 163, 184, 0.2);
        backdrop-filter: blur(8px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .category-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: left 0.5s;
    }

    .category-btn:hover::before {
        left: 100%;
    }

    .category-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        border-color: rgba(148, 163, 184, 0.4);
    }

    .category-btn.active {
        background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
        border-color: #8b5cf6;
        box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4), 0 0 0 3px rgba(139, 92, 246, 0.2);
    }

    .category-btn.active:hover {
        background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.5), 0 0 0 3px rgba(139, 92, 246, 0.3);
    }

    @media (max-width: 640px) {
        .category-btn {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem !important;
        }
    }

    /* 📊 Totales y acciones fijas */
    .kiosk-totales-section {
        flex-shrink: 0;
        padding: 1rem 1.5rem;
    }

    .kiosk-actions-section {
        flex-shrink: 0;
        padding: 0 1.5rem 1.5rem;
    }

    /* 🎯 Área de búsqueda y categorías fijas */
    .kiosk-filters-section {
        flex-shrink: 0;
        padding: 1rem 1.5rem;
    }

    /* 🔧 DEBUG: Asegurar que el contenedor relativo tenga flex correcto */
    .kiosk-panel-content > .relative {
        flex: 1;
        min-height: 0; /* Permitir que el hijo haga scroll */
        display: flex;
        flex-direction: column;
    }

    /* 🌐 Responsive móvil */
    @media (max-width: 767px) {
        .kiosk-content {
            padding: 0.5rem;
        }

        .kiosk-pos-grid {
            gap: 0.5rem;
            grid-template-rows: auto 1fr;
        }

        .kiosk-panel {
            border-radius: 0.75rem;
        }

        .kiosk-header {
            height: 60px;
            padding: 0.75rem 1rem !important;
        }
    }

    /* 📐 Estado sin orden - centrado y responsive */
    .kiosk-no-order {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        padding: 2rem;
    }

</style>

<!-- 🏪 Kiosk Container - Fullscreen Mode -->
<div class="kiosk-container bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">

    <!-- 📱 Compact Header - Fixed Top -->
    <div class="kiosk-header flex-shrink-0 bg-gradient-to-r from-slate-800/95 to-slate-700/95 backdrop-blur-sm border-b border-slate-600/50 px-4 py-3">
        <div class="flex justify-between items-center h-full">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-500 p-2 rounded-lg shadow-lg">
                    <i class="bi bi-table text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white leading-tight">Mesa <?= htmlspecialchars($mesa['nombre']) ?></h1>
                    <?php if ($orden && !empty($mesero_nombre) && $mesero_nombre !== 'Sin asignar'): ?>
                        <p class="text-slate-300 text-xs flex items-center">
                            <i class="bi bi-person-badge text-blue-400 mr-1"></i>
                            Mesero: <span class="font-semibold ml-1"><?= htmlspecialchars($mesero_nombre) ?></span>
                        </p>
                    <?php else: ?>
                        <p class="text-slate-300 text-xs">Kiosk POS</p>
                    <?php endif; ?>
                </div>
            </div>
            <a href="index.php?page=mesas" class="inline-flex items-center px-3 py-2 bg-slate-600/80 hover:bg-slate-500 text-white text-sm rounded-lg transition-all duration-200">
                <i class="bi bi-arrow-left mr-1.5"></i> Volver
            </a>
        </div>
    </div>

    <?php if ($orden): ?>
        <!-- 🎯 Contenido Principal - POS con Orden Abierta -->
        <div class="kiosk-content">
            <div class="kiosk-pos-grid">
                
                <!-- 📋 Panel Izquierdo - Orden Actual -->
                <div class="kiosk-panel">
                    <div class="kiosk-panel-header">
                        <h3 class="text-white font-bold text-lg flex items-center">
                            <i class="bi bi-receipt mr-2"></i> Orden Actual
                        </h3>
                    </div>
                    
                    <div class="kiosk-panel-content">
                        <!-- Área de scroll para productos -->
                        <div class="relative flex-1 min-h-0">
                            <div class="scroll-fade-top"></div>
                            <div id="orden-lista" class="orden-scroll-area">
                                <div class="text-center text-slate-400 py-8">
                                    <i class="bi bi-arrow-clockwise animate-spin text-3xl mb-3"></i>
                                    <p>Cargando orden...</p>
                                </div>
                            </div>
                            <div class="scroll-fade-bottom"></div>
                        </div>
                        
                        <!-- Totales fijos -->
                        <div class="kiosk-totales-section">
                            <div id="orden-totales" class="bg-slate-900/80 rounded-xl p-4 border border-slate-600">
                                <div class="text-slate-400 text-center py-2 text-sm">
                                    <i class="bi bi-calculator mr-2"></i>Totales...
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botones de acción fijos -->
                        <div class="kiosk-actions-section space-y-2">
                            <?php if ($esAdministrador): ?>
                            <button id="cancelar_orden" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl">
                                <i class="bi bi-x-circle mr-2"></i>Cancelar Orden
                            </button>
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <?php if ($esAdministrador): ?>
                                <button onclick="descargarPDF(<?= $orden_id ?>)" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg text-center font-semibold transition-all text-sm shadow-md hover:shadow-lg">
                                    <i class="bi bi-printer mr-1"></i>PDF
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($impresora_configurada): ?>
                                <button onclick="imprimirTicketTermico(<?= $orden_id ?>)" 
                                        class="<?= $esAdministrador ? '' : 'col-span-2' ?> bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg font-semibold transition-all text-sm shadow-md hover:shadow-lg">
                                    <i class="bi bi-receipt mr-1"></i>Térmica
                                </button>
                                <?php else: ?>
                                <a href="index.php?page=configuracion&tab=impresoras" 
                                   class="<?= $esAdministrador ? '' : 'col-span-2' ?> block bg-slate-600 hover:bg-slate-700 text-white py-2.5 rounded-lg text-center font-semibold transition-all text-sm">
                                    <i class="bi bi-gear mr-1"></i>Configurar
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$impresion_automatica || !$impresora_configurada): ?>
                            <div class="text-center">
                                <a href="index.php?page=configuracion&tab=impresoras" class="text-slate-400 hover:text-white text-xs transition-colors">
                                    <i class="bi bi-gear mr-1"></i>
                                    <?php if (!$impresora_configurada): ?>
                                        Configurar impresora
                                    <?php else: ?>
                                        Activar impresión automática
                                    <?php endif; ?>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <form method="post" action="/POS/controllers/cerrar_orden.php" id="cerrar-orden-form">
                                <input type="hidden" name="orden_id" value="<?= $orden['id'] ?>">
                                <input type="hidden" name="mesa_id" value="<?= $mesa_id ?>">
                                <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white py-3 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl">
                                    <i class="bi bi-check-circle mr-2"></i>Cerrar Orden y Pagar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 🛒 Panel Derecho - Catálogo de Productos -->
                <div class="kiosk-panel">
                    <div class="kiosk-panel-header">
                        <h3 class="text-white font-bold text-lg flex items-center">
                            <i class="bi bi-grid-3x3-gap mr-2"></i> Catálogo de Productos
                        </h3>
                    </div>
                    
                    <div class="kiosk-panel-content">
                        <!-- Filtros fijos -->
                        <div class="kiosk-filters-section space-y-3">
                            <input type="text" id="buscador" 
                                   class="w-full px-4 py-2.5 bg-slate-900/80 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                   placeholder="🔍 Buscar producto...">
                            
                            <div id="categorias" class="flex flex-wrap gap-2"></div>
                        </div>
                        
                        <!-- Área de scroll para productos -->
                        <div class="relative flex-1 min-h-0">
                            <div class="scroll-fade-top"></div>
                            <div class="catalogo-scroll-area">
                                <div id="productos" class="kiosk-products-grid">
                                    <div class="col-span-full text-center py-12">
                                        <i class="bi bi-arrow-clockwise animate-spin text-4xl text-slate-400 mb-3"></i>
                                        <p class="text-slate-400">Cargando productos...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="scroll-fade-bottom"></div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    <?php else: ?>
        <!-- 📭 Estado sin Orden -->
        <div class="kiosk-content">
            <div class="kiosk-no-order">
                <div class="bg-slate-800/95 rounded-2xl shadow-2xl border border-slate-600 p-8 text-center max-w-md">
                    <div class="bg-blue-500/20 p-4 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                        <i class="bi bi-clipboard-x text-blue-400 text-4xl"></i>
                    </div>
                    <h3 class="text-white text-2xl font-bold mb-3">No hay orden abierta</h3>
                    <p class="text-slate-400 mb-6">Inicia una nueva orden para comenzar a agregar productos</p>
                    <form method="post" action="/POS/controllers/nueva_orden.php">
                        <input type="hidden" name="mesa_id" value="<?= $mesa_id ?>">
                        <button type="submit" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white py-4 px-8 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all">
                            <i class="bi bi-plus-circle mr-2"></i>Abrir Nueva Orden
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    const mesaId = <?= $mesa_id ?>;
    const ordenId = <?= $orden_id ?>;
    const esAdministrador = <?= $esAdministrador ? 'true' : 'false' ?>;

    /** 🔹 Funciones globales para manejo de efectivo y cambio */
    function toggleEfectivoFields() {
        const efectivoSelected = document.querySelector('input[name="metodo_pago"]:checked').value === 'efectivo';
        const efectivoFields = document.getElementById('efectivo-fields');
        
        if (efectivoSelected) {
            efectivoFields.style.display = 'block';
            // Enfocar el campo de dinero recibido
            setTimeout(() => {
                const dineroInput = document.getElementById('dinero-recibido');
                if (dineroInput) {
                    dineroInput.focus();
                }
            }, 100);
        } else {
            efectivoFields.style.display = 'none';
            // Limpiar campos cuando se cambia a tarjeta
            const dineroInput = document.getElementById('dinero-recibido');
            const cambioDisplay = document.getElementById('cambio-display');
            const cambioError = document.getElementById('cambio-error');
            
            if (dineroInput) dineroInput.value = '';
            if (cambioDisplay) cambioDisplay.style.display = 'none';
            if (cambioError) cambioError.style.display = 'none';
        }
    }

    function calcularCambio(total) {
        const dineroRecibido = parseFloat(document.getElementById('dinero-recibido').value) || 0;
        const cambioDisplay = document.getElementById('cambio-display');
        const cambioAmount = document.getElementById('cambio-amount');
        const cambioError = document.getElementById('cambio-error');
        
        if (dineroRecibido <= 0) {
            // No mostrar nada si no hay valor
            if (cambioDisplay) cambioDisplay.style.display = 'none';
            if (cambioError) cambioError.style.display = 'none';
            return;
        }
        
        if (dineroRecibido < total) {
            // Mostrar error si el dinero es insuficiente
            if (cambioDisplay) cambioDisplay.style.display = 'none';
            if (cambioError) {
                cambioError.style.display = 'block';
                cambioError.innerHTML = '<p class="text-sm">Faltan $' + (total - dineroRecibido).toFixed(2) + ' para completar el pago</p>';
            }
        } else {
            // Calcular y mostrar el cambio
            const cambio = dineroRecibido - total;
            if (cambioError) cambioError.style.display = 'none';
            if (cambioDisplay) cambioDisplay.style.display = 'block';
            
            if (cambioAmount) {
                if (cambio === 0) {
                    cambioAmount.textContent = 'Pago exacto';
                    cambioAmount.className = 'text-xl font-bold text-blue-700';
                } else {
                    cambioAmount.textContent = '$' + cambio.toFixed(2);
                    cambioAmount.className = 'text-xl font-bold text-green-700';
                }
            }
        }
    }

    // Verificar mensajes de impresión automática
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.get('impresion_exitosa') === '1') {
            const mensaje = urlParams.get('mensaje') || 'Ticket impreso correctamente';
            Swal.fire({
                icon: 'success',
                title: '¡Impresión Exitosa!',
                text: mensaje,
                confirmButtonColor: '#16a34a'
            });
            
            // Limpiar URL
            window.history.replaceState({}, document.title, window.location.pathname + '?id=' + mesaId);
        }
        
        if (urlParams.get('impresion_error') === '1') {
            const mensaje = urlParams.get('mensaje') || 'Error al imprimir ticket';
            Swal.fire({
                icon: 'error',
                title: 'Error de Impresión',
                text: mensaje,
                confirmButtonColor: '#dc2626'
            });
            
            // Limpiar URL
            window.history.replaceState({}, document.title, window.location.pathname + '?id=' + mesaId);
        }
    });

    /** 🔹 Cargar Categorías */
    function cargarCategorias() {
        // Anti-caché: siempre obtener categorías frescas
        fetch('/POS/controllers/categorias.php?_=' + Date.now(), {
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            }
        })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                let html = '<button class="category-btn active px-4 py-2.5 text-white rounded-xl transition-all duration-300 shadow-lg" data-cat="0">' +
                    '<i class="bi bi-grid-3x3-gap mr-2"></i>Todos' +
                    '</button>';
                data.forEach(function(cat) {
                    html += '<button class="category-btn px-4 py-2.5 text-white rounded-xl transition-all duration-300 shadow-lg" data-cat="' + cat.id + '">' +
                        '<i class="bi bi-tag mr-2"></i>' + cat.nombre +
                        '</button>';
                });
                document.getElementById('categorias').innerHTML = html;

                // Aplicar eventos a los botones
                document.querySelectorAll('.category-btn').forEach(function(btn) {
                    btn.onclick = function() {
                        // Remover la clase active de todos los botones
                        document.querySelectorAll('.category-btn').forEach(function(b) {
                            b.classList.remove('active');
                        });

                        // Agregar la clase active al botón clickeado
                        this.classList.add('active');

                        // Cargar productos de la categoría seleccionada
                        cargarProductos(this.getAttribute('data-cat'), document.getElementById('buscador').value);
                    };
                });
            });
    }

    /** 🔹 Cargar Productos */
    function cargarProductos(cat_id, q) {
        if (cat_id === undefined) cat_id = 0;
        if (q === undefined) q = '';

        // Anti-caché: siempre obtener productos frescos
        fetch('/POS/controllers/buscar_productos.php?cat_id=' + cat_id + '&q=' + encodeURIComponent(q) + '&_=' + Date.now(), {
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            }
        })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                let html = '';
                data.forEach(function(prod) {
                    const imageUrl = prod.imagen ? './assets/img/' + prod.imagen : null;
                    
                    html += '<div class="product-card group bg-gradient-to-br from-slate-700 to-slate-600 rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 cursor-pointer border border-slate-500 hover:border-blue-400" onclick="agregarProductoMesa(' + prod.id + ')">' +
                        '<div class="aspect-square overflow-hidden bg-slate-800 relative">' +
                        (imageUrl ? 
                            '<img src="' + imageUrl + '" ' +
                            'alt="' + prod.nombre + '" ' +
                            'class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" ' +
                            'onerror="this.style.display=\'none\'; this.nextElementSibling.classList.remove(\'hidden\');">' +
                            '<div class="hidden absolute inset-0 bg-gradient-to-br from-slate-600/40 to-slate-800/60 flex flex-col items-center justify-center">' +
                            '<i class="bi bi-image text-5xl text-gray-400 mb-2"></i>' +
                            '<p class="text-gray-400 text-sm font-semibold">Sin imagen</p>' +
                            '</div>'
                            :
                            '<div class="absolute inset-0 bg-gradient-to-br from-slate-600/40 to-slate-800/60 flex flex-col items-center justify-center">' +
                            '<i class="bi bi-image text-5xl text-gray-400 mb-2"></i>' +
                            '<p class="text-gray-400 text-sm font-semibold">Sin imagen</p>' +
                            '</div>'
                        ) +
                        '</div>' +
                        '<div class="p-4">' +
                        '<h4 class="text-white font-semibold text-base mb-2 group-hover:text-blue-300 transition-colors line-clamp-2 min-h-[3rem]">' + prod.nombre + '</h4>' +
                        '<div class="flex items-center justify-between">' +
                        '<span class="text-green-400 font-bold text-xl">$' + Number(prod.precio).toFixed(2) + '</span>' +
                        '<div class="bg-blue-600 group-hover:bg-blue-500 text-white p-2 rounded-lg transition-colors">' +
                        '<i class="bi bi-plus-lg text-lg"></i>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                });

                if (!html) {
                    html = '<div class="col-span-full text-center py-16">' +
                        '<div class="bg-slate-600 p-6 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">' +
                        '<i class="bi bi-search text-white text-3xl"></i>' +
                        '</div>' +
                        '<p class="text-slate-300 text-xl mb-2">No se encontraron productos</p>' +
                        '<p class="text-slate-400 text-sm">Intenta con otros términos de búsqueda</p>' +
                        '<p class="text-slate-500 text-xs mt-4">O selecciona una categoría diferente</p>' +
                        '</div>';
                }

                document.getElementById('productos').innerHTML = html;
                
                // Refrescar indicadores de scroll después de cargar productos
                setTimeout(refreshScrollIndicators, 100);
            });
    }

    /** 🔹 Descargar PDF (Compatible con PWA) */
    function descargarPDF(ordenId) {
        // Mostrar loading
        Swal.fire({
            title: 'Generando PDF...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });

        // Usar fetch para obtener el PDF
        fetch('controllers/impresion_ticket.php?orden_id=' + ordenId)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Error al generar el PDF');
                }
                return response.blob();
            })
            .then(function(blob) {
                // Crear un URL temporal para el blob
                const url = window.URL.createObjectURL(blob);
                
                // Crear un enlace temporal para descargar
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'ticket_orden_' + ordenId + '.pdf';
                
                // Agregar al DOM, hacer clic y remover
                document.body.appendChild(a);
                a.click();
                
                // Limpiar
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                // Cerrar loading y mostrar éxito
                Swal.fire({
                    icon: 'success',
                    title: '¡PDF Generado!',
                    text: 'El ticket se ha descargado correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
            })
            .catch(function(error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo generar el PDF. Intenta nuevamente.',
                    confirmButtonColor: '#dc2626'
                });
            });
    }

    /** 🔹 Agregar producto */
    let productoSeleccionadoId = null;
    let variedadesSeleccionadas = {};
    
    function agregarProductoMesa(producto_id) {
        // Primero verificar si el producto tiene variedades
        fetch('./api/productController/api.php?action=get_product&id=' + producto_id + '&_=' + Date.now(), {
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate'
            }
        })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (data.success && data.product) {
                    const producto = data.product;
                    
                    // Verificar si tiene variedades
                    if (producto.tiene_variedades == 1 || producto.tiene_variedades === '1' || producto.tiene_variedades === true) {
                        // Abrir modal de variedades
                        productoSeleccionadoId = producto_id;
                        abrirModalVariedades(producto);
                    } else {
                        // Agregar directamente sin variedades
                        agregarProductoDirecto(producto_id, null);
                    }
                } else {
                    // Si no se puede obtener info, agregar directamente
                    agregarProductoDirecto(producto_id, null);
                }
            })
            .catch(function(error) {
                // En caso de error, agregar directamente
                agregarProductoDirecto(producto_id, null);
            });
    }
    
    /** 🔹 Agregar producto directamente (con o sin variedades) */
    function agregarProductoDirecto(producto_id, variedades, notaAdicional) {
        console.log('🔹 agregarProductoDirecto llamado con:', {
            producto_id_recibido: producto_id,
            tipo_producto_id: typeof producto_id,
            variedades_recibidas: variedades,
            variedades_es_objeto: typeof variedades === 'object',
            nota_adicional: notaAdicional
        });
        
        // CRÍTICO: Validar que producto_id no sea null o undefined
        if (!producto_id || producto_id === null || producto_id === 'null') {
            Swal.fire('Error', 'Error interno: ID de producto inválido', 'error');
            return;
        }
        
        const formData = new URLSearchParams({
            producto_id: producto_id,
            cantidad: 1,
            orden_id: ordenId
        });
        
        // Si hay variedades, convertirlas a array si es necesario y agregarlas
        if (variedades) {
            let variedadesArray = variedades;
            
            // Si variedades es un objeto (no array), convertir a array de valores
            if (!Array.isArray(variedades)) {
                variedadesArray = Object.values(variedades);
            }
            
            // Solo agregar si el array tiene elementos
            if (variedadesArray.length > 0) {
                formData.append('variedades', JSON.stringify(variedadesArray));
            }
        }
        
        // Agregar nota adicional si existe
        if (notaAdicional && notaAdicional.trim() !== '') {
            formData.append('nota_adicional', notaAdicional.trim());
        }
        
        // Debug: Mostrar qué se está enviando
        console.log('🔹 Enviando producto:', {
            producto_id: producto_id,
            cantidad: 1,
            orden_id: ordenId,
            variedades: variedades,
            nota_adicional: notaAdicional
        });
        
        fetch('/POS/controllers/newPos/agregar_producto_orden.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(resp) {
                console.log('🔹 Respuesta del servidor:', resp);
                
                if (resp.status === "ok") {
                    // Toast no bloqueante - el usuario puede seguir interactuando
                    const toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });

                    toast.fire({
                        icon: 'success',
                        title: 'Producto agregado',
                        background: '#1f2937',
                        color: '#ffffff'
                    });
                    
                    // Limpiar estado de variedades después de agregar exitosamente
                    limpiarEstadoVariedades();

                    // Actualización optimizada con anti-caché
                    setTimeout(() => {
                        console.log('🔄 Actualizando orden de mesa...');
                        
                        // Pre-fetch con timestamp para verificar datos frescos
                        fetch('/POS/controllers/newPos/orden_actual.php?orden_id=' + ordenId + '&_=' + Date.now())
                            .then(res => res.json())
                            .then(data => {
                                console.log('✅ Datos actualizados:', data.items ? data.items.length : 0, 'items');
                                cargarOrden();
                            })
                            .catch(err => {
                                console.error('❌ Error al actualizar:', err);
                                cargarOrden(); // Intentar cargar de todas formas
                            });
                    }, 300); // 300ms = tiempo óptimo para commit de DB
                } else {
                    Swal.fire('Error', resp.msg || 'No se pudo agregar', 'error');
                }
            });
    }

    /** 🔹 FUNCIONES DE MODAL DE VARIEDADES */
    
    function abrirModalVariedades(producto) {
        // ⚠️ CRÍTICO: Guardar el ID del producto PRIMERO
        productoSeleccionadoId = producto.id;
        
        // Guardar el nombre del producto
        document.getElementById('variedad-producto-nombre').textContent = producto.nombre;
        
        // Limpiar selecciones previas
        variedadesSeleccionadas = {};
        
        console.log('🔹 Abriendo modal de variedades para producto ID:', productoSeleccionadoId);
        
        // Cargar variedades del producto
        fetch('./api/productController/api.php?action=get_variedades&producto_id=' + producto.id + '&_=' + Date.now(), {
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate'
            }
        })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (data.success && data.variedades && data.variedades.length > 0) {
                    renderGruposVariedades(data.variedades);
                    
                    // Mostrar modal
                    const modal = document.getElementById('variedadesModal');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                } else {
                    // Si no hay variedades definidas, agregar directamente
                    console.warn('Producto marcado con variedades pero no tiene grupos definidos');
                    agregarProductoDirecto(producto.id, null);
                }
            })
            .catch(function(error) {
                console.error('Error al cargar variedades:', error);
                Swal.fire('Error', 'No se pudieron cargar las variedades del producto', 'error');
            });
    }
    
    function cerrarModalVariedades() {
        const modal = document.getElementById('variedadesModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        
        document.getElementById('variedades-grupos-container').innerHTML = '';
    }
    
    function limpiarEstadoVariedades() {
        productoSeleccionadoId = null;
        variedadesSeleccionadas = {};
        
        // Limpiar nota adicional
        const checkNota = document.getElementById('check-nota-adicional');
        const campoNota = document.getElementById('campo-nota-adicional');
        const textareaNota = document.getElementById('nota-adicional-texto');
        
        if (checkNota) checkNota.checked = false;
        if (campoNota) campoNota.classList.add('hidden');
        if (textareaNota) {
            textareaNota.value = '';
            document.getElementById('contador-caracteres').textContent = '0/200';
        }
    }
    
    function renderGruposVariedades(grupos) {
        const container = document.getElementById('variedades-grupos-container');
        let html = '';
        
        grupos.forEach(function(grupo, index) {
            html += '<div class="bg-slate-700/50 rounded-xl p-5 border border-slate-600/50">';
            html += '<div class="flex items-center justify-between mb-4">';
            html += '<h4 class="text-lg font-bold text-orange-400 flex items-center gap-2">';
            html += '<i class="bi bi-list-ul"></i>' + escapeHtml(grupo.nombre);
            if (grupo.obligatorio == 1) {
                html += '<span class="text-xs bg-red-500 text-white px-2 py-1 rounded-full ml-2">Obligatorio</span>';
            }
            html += '</h4>';
            html += '</div>';
            
            html += '<div class="space-y-2">';
            
            if (grupo.opciones && grupo.opciones.length > 0) {
                grupo.opciones.forEach(function(opcion) {
                    const opcionId = 'opcion_' + grupo.id + '_' + opcion.id;
                    const inputName = 'grupo_' + grupo.id;
                    const isObligatorio = grupo.obligatorio == 1;
                    const inputType = isObligatorio ? 'radio' : 'checkbox';
                    
                    html += '<label class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg border border-slate-600/30 hover:border-orange-500/50 hover:bg-slate-700/50 cursor-pointer transition-all group">';
                    html += '<div class="flex items-center gap-3 flex-1">';
                    html += '<input type="' + inputType + '" id="' + opcionId + '" name="' + inputName + '" ';
                    html += 'value="' + opcion.id + '" ';
                    html += 'data-grupo-id="' + grupo.id + '" ';
                    html += 'data-grupo-nombre="' + escapeHtml(grupo.nombre) + '" ';
                    html += 'data-opcion-nombre="' + escapeHtml(opcion.nombre) + '" ';
                    html += 'data-precio="' + opcion.precio_adicional + '" ';
                    html += 'data-obligatorio="' + grupo.obligatorio + '" ';
                    html += 'class="w-5 h-5 text-orange-600 bg-slate-700 border-slate-500 focus:ring-orange-500 focus:ring-2 cursor-pointer" ';
                    html += 'onchange="seleccionarVariedad(' + grupo.id + ', ' + opcion.id + ', \'' + escapeHtml(grupo.nombre) + '\', \'' + escapeHtml(opcion.nombre) + '\', ' + opcion.precio_adicional + ', ' + grupo.obligatorio + ', this)">';
                    html += '<span class="text-white font-medium">' + escapeHtml(opcion.nombre) + '</span>';
                    html += '</div>';
                    
                    if (opcion.precio_adicional > 0) {
                        html += '<span class="text-green-400 font-semibold">+$' + parseFloat(opcion.precio_adicional).toFixed(2) + '</span>';
                    }
                    
                    html += '</label>';
                });
            } else {
                html += '<p class="text-gray-400 text-sm italic">Sin opciones disponibles</p>';
            }
            
            html += '</div>';
            html += '</div>';
        });
        
        container.innerHTML = html;
    }
    
    function seleccionarVariedad(grupoId, opcionId, grupoNombre, opcionNombre, precio, esObligatorio, checkbox) {
        // Para grupos obligatorios (radio buttons), funciona como antes
        if (esObligatorio == 1) {
            variedadesSeleccionadas[grupoId] = {
                grupo_id: grupoId,
                opcion_id: opcionId,
                grupo_nombre: grupoNombre,
                opcion_nombre: opcionNombre,
                precio_adicional: precio
            };
        } else {
            // Para grupos no obligatorios (checkboxes), toggle on/off
            if (checkbox.checked) {
                // Agregar la variedad si está marcada
                variedadesSeleccionadas[grupoId] = {
                    grupo_id: grupoId,
                    opcion_id: opcionId,
                    grupo_nombre: grupoNombre,
                    opcion_nombre: opcionNombre,
                    precio_adicional: precio
                };
            } else {
                // Eliminar la variedad si está desmarcada
                delete variedadesSeleccionadas[grupoId];
            }
        }
        
        console.log('Variedad seleccionada:', variedadesSeleccionadas[grupoId]);
    }
    
    function confirmarVariedades() {
        
        // Verificar que todos los grupos obligatorios tengan selección
        const inputs = document.querySelectorAll('[data-obligatorio="1"]');
        const gruposObligatorios = {};
        
        inputs.forEach(function(input) {
            const grupoId = input.getAttribute('data-grupo-id');
            if (!gruposObligatorios[grupoId]) {
                gruposObligatorios[grupoId] = {
                    nombre: input.getAttribute('data-grupo-nombre'),
                    seleccionado: false
                };
            }
            if (input.checked) {
                gruposObligatorios[grupoId].seleccionado = true;
            }
        });
        
        // Validar obligatorios
        let faltantes = [];
        for (let grupoId in gruposObligatorios) {
            if (!gruposObligatorios[grupoId].seleccionado) {
                faltantes.push(gruposObligatorios[grupoId].nombre);
            }
        }
        
        if (faltantes.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Selección Incompleta',
                text: 'Debes seleccionar una opción para: ' + faltantes.join(', '),
                confirmButtonColor: '#f59e0b'
            });
            return;
        }
        
        // Convertir objeto de variedades a array de valores
        const variedadesArray = Object.values(variedadesSeleccionadas);
        
        // Obtener nota adicional si existe
        const notaAdicional = document.getElementById('check-nota-adicional').checked 
            ? document.getElementById('nota-adicional-texto').value.trim() 
            : null;
        
        console.log('🔹 variedadesArray (convertido):', variedadesArray);
        console.log('🔹 Nota adicional:', notaAdicional);
        console.log('🔹 Llamando agregarProductoDirecto con ID:', productoSeleccionadoId);
        
        // Todo válido, agregar producto con variedades y nota
        cerrarModalVariedades();
        agregarProductoDirecto(productoSeleccionadoId, variedadesArray, notaAdicional);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /** 🔹 Funciones para nota adicional */
    function toggleNotaAdicional() {
        const checkbox = document.getElementById('check-nota-adicional');
        const campoNota = document.getElementById('campo-nota-adicional');
        const textareaNota = document.getElementById('nota-adicional-texto');
        
        if (checkbox.checked) {
            campoNota.classList.remove('hidden');
            setTimeout(() => textareaNota.focus(), 100);
        } else {
            campoNota.classList.add('hidden');
            textareaNota.value = '';
            document.getElementById('contador-caracteres').textContent = '0/200';
        }
    }
    
    // Event listener para contador de caracteres
    document.addEventListener('DOMContentLoaded', function() {
        const textareaNota = document.getElementById('nota-adicional-texto');
        if (textareaNota) {
            textareaNota.addEventListener('input', function() {
                const contador = document.getElementById('contador-caracteres');
                contador.textContent = this.value.length + '/200';
                
                if (this.value.length >= 200) {
                    contador.classList.add('text-orange-500', 'font-bold');
                } else {
                    contador.classList.remove('text-orange-500', 'font-bold');
                }
            });
        }
    });

    /** 🔹 Determinar Estado Visual del Producto */
    function getEstadoProducto(cantidad, preparado, cancelado, pendiente_cancelacion = 0) {
        const disponibles = cantidad - preparado - cancelado - pendiente_cancelacion;

        if (cancelado > 0 && pendiente_cancelacion > 0) {
            // Producto con cancelados y pendientes de cancelación
            return {
                cardClass: 'bg-gradient-to-r from-red-900/30 to-orange-900/30 rounded-xl p-4 border border-red-600/50',
                titleClass: 'text-red-300 font-semibold text-sm flex-1 pr-2',
                badge: '<span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full mr-1">CANCELADO</span><span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full">PENDIENTE</span>',
                detalles: cancelado + ' cancelado(s), ' + pendiente_cancelacion + ' pendiente(s) cancelación',
                detallesClass: 'text-red-300'
            };
        }

        if (cancelado > 0) {
            // Solo cancelados (puede ser cancelación parcial)
            if (disponibles > 0 || preparado > 0) {
                // Cancelación parcial
                let detalleTexto = '';
                if (preparado > 0) detalleTexto += preparado + ' preparado(s), ';
                if (disponibles > 0) detalleTexto += disponibles + ' disponible(s), ';
                detalleTexto += cancelado + ' cancelado(s)';
                
                return {
                    cardClass: 'bg-gradient-to-r from-slate-800/30 to-red-900/30 rounded-xl p-4 border border-red-600/50',
                    titleClass: 'text-slate-200 font-semibold text-sm flex-1 pr-2',
                    badge: '<span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">PARCIAL CANCELADO</span>',
                    detalles: detalleTexto,
                    detallesClass: 'text-red-300'
                };
            } else {
                // Completamente cancelado
                return {
                    cardClass: 'bg-red-900/30 rounded-xl p-4 border border-red-600/50 opacity-75',
                    titleClass: 'text-red-300 font-semibold text-sm flex-1 pr-2 line-through',
                    badge: '<span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">CANCELADO</span>',
                    detalles: cancelado + ' unidad(es) canceladas',
                    detallesClass: 'text-red-300'
                };
            }
        }

        if (pendiente_cancelacion > 0) {
            // Producto con pendientes de cancelación
            let detalleTexto = '';
            if (preparado > 0) detalleTexto += preparado + ' preparado(s), ';
            if (disponibles > 0) detalleTexto += disponibles + ' disponible(s), ';
            detalleTexto += pendiente_cancelacion + ' pendiente(s) cancelación';
            
            return {
                cardClass: 'bg-gradient-to-r from-slate-800/30 to-orange-900/30 rounded-xl p-4 border border-orange-600/50',
                titleClass: 'text-orange-200 font-semibold text-sm flex-1 pr-2',
                badge: '<span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full animate-pulse">PENDIENTE CANCELACIÓN</span>',
                detalles: detalleTexto,
                detallesClass: 'text-orange-300'
            };
        }

        if (preparado === 0 && disponibles > 0) {
            // Todo pendiente - naranja "Preparando"
            return {
                cardClass: 'bg-orange-900/30 rounded-xl p-4 border border-orange-500/50',
                titleClass: 'text-orange-100 font-semibold text-sm flex-1 pr-2',
                badge: '<span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full animate-pulse">PREPARANDO</span>',
                detalles: disponibles + ' unidad(es) en preparación',
                detallesClass: 'text-orange-300'
            };
        }

        if (preparado > 0 && disponibles > 0) {
            // Parcialmente preparado - verde con detalles
            return {
                cardClass: 'bg-gradient-to-r from-green-900/30 to-orange-900/30 rounded-xl p-4 border border-green-500/50',
                titleClass: 'text-green-100 font-semibold text-sm flex-1 pr-2',
                badge: '<span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">PARCIAL</span>',
                detalles: preparado + ' preparado(s), ' + disponibles + ' preparando',
                detallesClass: 'text-green-300'
            };
        }

        if (preparado > 0 && disponibles === 0) {
            // Todo preparado - verde completo
            return {
                cardClass: 'bg-green-900/30 rounded-xl p-4 border border-green-500/50',
                titleClass: 'text-green-100 font-semibold text-sm flex-1 pr-2',
                badge: '<span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full"><i class="bi bi-check-circle mr-1"></i>PREPARADO</span>',
                detalles: preparado + ' unidad(es) listas',
                detallesClass: 'text-green-300'
            };
        }

        // Estado por defecto
        return {
            cardClass: 'bg-slate-900 rounded-xl p-4 border border-slate-600 hover:border-slate-500 transition-colors',
            titleClass: 'text-white font-semibold text-sm flex-1 pr-2',
            badge: '',
            detalles: null,
            detallesClass: null
        };
    }

    /** 🔹 Función para mostrar confirmación de cancelación total */
    function mostrarConfirmacionCancelacion(ordenProductoId, productoNombre, cantidadCancelar, preparado) {
        let contextMessage = '';
        if (preparado > 0) {
            contextMessage = '<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">' +
                '<p class="text-green-700 text-sm">' +
                '<i class="bi bi-check-circle mr-1"></i>' +
                '<strong>' + preparado + ' unidad(es) ya preparada(s)</strong> - No se pueden cancelar' +
                '</p>' +
                '</div>';
        }

        Swal.fire({
            title: 'Confirmar cancelación total',
            html: '<div class="text-left">' +
                '<p class="mb-3">Producto: <strong>' + productoNombre + '</strong></p>' +
                contextMessage +
                '<div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-3">' +
                '<p class="text-red-700 text-sm">' +
                '<i class="bi bi-x-circle mr-1"></i>' +
                '<strong>' + cantidadCancelar + ' unidad(es) se cancelarán</strong>' +
                '</p>' +
                '</div>' +
                '<p class="mb-3 text-sm text-gray-600">Se enviará un PIN al administrador por Email</p>' +
                '<textarea id="razon-cancelacion" class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" rows="3" placeholder="Motivo de la cancelación (opcional)"></textarea>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Cancelar ' + cantidadCancelar + ' unidad(es)',
            cancelButtonText: 'No cancelar',
            confirmButtonColor: '#ef4444',
            preConfirm: function() {
                return document.getElementById('razon-cancelacion').value;
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                const razon = result.value || ('Cancelación total de ' + cantidadCancelar + ' unidades');
                mostrarModalCancelacion(ordenProductoId, productoNombre, cantidadCancelar, razon);
            }
        });
    }

    /** 🔹 Función para mostrar selector de cantidad parcial */
    function mostrarSelectorCantidad(ordenProductoId, productoNombre, disponibles, preparado, pendiente_cancelacion = 0) {
        let contextMessage = '';
        if (preparado > 0) {
            contextMessage = '<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">' +
                '<p class="text-green-700 text-sm">' +
                '<i class="bi bi-check-circle mr-1"></i>' +
                '<strong>' + preparado + ' unidad(es) ya preparada(s)</strong> - No se pueden cancelar' +
                '</p>' +
                '</div>';
        }
        
        if (pendiente_cancelacion > 0) {
            contextMessage += '<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">' +
                '<p class="text-orange-700 text-sm">' +
                '<i class="bi bi-clock mr-1"></i>' +
                '<strong>' + pendiente_cancelacion + ' unidad(es) esperando aprobación</strong> - Ya solicitadas' +
                '</p>' +
                '</div>';
        }

        Swal.fire({
            title: 'Cancelación parcial',
            html: '<div class="text-left">' +
                '<p class="mb-3">Producto: <strong>' + productoNombre + '</strong></p>' +
                contextMessage +
                '<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">' +
                '<p class="text-blue-700 text-sm">' +
                '<i class="bi bi-info-circle mr-1"></i>' +
                '<strong>' + disponibles + ' unidad(es) disponibles</strong> para cancelar' +
                '</p>' +
                '</div>' +
                '<div class="mb-3">' +
                '<label class="block text-sm font-medium text-gray-700 mb-2">¿Cuántas unidades deseas cancelar?</label>' +
                '<input type="number" id="cantidad-cancelar" min="1" max="' + disponibles + '" value="1" ' +
                'class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">' +
                '</div>' +
                '<p class="mb-3 text-sm text-gray-600">Se enviará un PIN al administrador por Email</p>' +
                '<textarea id="razon-cancelacion" class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" rows="3" placeholder="Motivo de la cancelación (opcional)"></textarea>' +
                '</div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Cancelar unidades seleccionadas',
            cancelButtonText: 'No cancelar',
            confirmButtonColor: '#f59e0b',
            preConfirm: function() {
                const cantidadInput = document.getElementById('cantidad-cancelar');
                const razonInput = document.getElementById('razon-cancelacion');
                const valor = parseInt(cantidadInput.value);

                if (!valor || valor < 1 || valor > disponibles) {
                    Swal.showValidationMessage('Cantidad inválida. Debe ser entre 1 y ' + disponibles);
                    return false;
                }

                return {
                    cantidad: valor,
                    razon: razonInput.value || ('Cancelación parcial de ' + valor + ' unidad(es)')
                };
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                mostrarModalCancelacion(ordenProductoId, productoNombre, result.value.cantidad, result.value.razon);
            }
        });
    }

    function mostrarModalCancelacion(ordenProductoId, productoNombre, cantidad, motivoPredefinido) {
        if (motivoPredefinido === undefined) motivoPredefinido = null;

        if (motivoPredefinido) {
            // Si ya tenemos el motivo, enviar directamente
            enviarSolicitudCancelacion(ordenProductoId, ordenId, cantidad, motivoPredefinido);
            return;
        }

        Swal.fire({
            title: 'Motivo de cancelación',
            html: '<div class="text-left">' +
                '<p class="mb-3">Producto: <strong>' + productoNombre + '</strong></p>' +
                '<p class="mb-3">Cantidad a cancelar: <strong>' + cantidad + ' unidad(es)</strong></p>' +
                '<p class="mb-3 text-sm text-gray-600">Se enviará un PIN al administrador por Email y en Autorizaciones</p>' +
                '<textarea id="razon-cancelacion" class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" rows="3" placeholder="Motivo de la cancelación (opcional)"></textarea>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Solicitar cancelación',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#f59e0b',
            preConfirm: function() {
                return document.getElementById('razon-cancelacion').value;
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                const razon = result.value || 'Sin motivo especificado';
                enviarSolicitudCancelacion(ordenProductoId, ordenId, cantidad, razon);
            }
        });
    }

    function enviarSolicitudCancelacion(ordenProductoId, ordenId, cantidad, razon) {
        Swal.fire({
            title: 'Enviando solicitud...',
            allowOutsideClick: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });

        fetch('/POS/controllers/newPos/solicitar_cancelacion.php', {
                method: 'POST',
                body: new URLSearchParams({
                    orden_producto_id: ordenProductoId,
                    orden_id: ordenId,
                    cantidad_cancelar: cantidad,
                    razon: razon
                }),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    // Toast no bloqueante
                    const toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true
                    });

                    toast.fire({
                        icon: 'success',
                        title: 'Solicitud enviada',
                        html: '<div class="text-sm">' +
                            '<p class="mb-1">' + data.message + '</p>' +
                            '<p class="text-xs opacity-75">Cantidad: ' + cantidad + ' unidad(es)</p>' +
                            '</div>',
                        background: '#1f2937',
                        color: '#ffffff'
                    });

                    // Actualización optimizada con anti-caché
                    setTimeout(() => {
                        console.log('🔄 Actualizando orden tras solicitud de cancelación...');
                        
                        // Pre-fetch con timestamp para datos frescos
                        fetch('/POS/controllers/newPos/orden_actual.php?orden_id=' + ordenId + '&_=' + Date.now())
                            .then(res => res.json())
                            .then(data => {
                                console.log('✅ Orden actualizada después de cancelación');
                                cargarOrden();
                            })
                            .catch(err => {
                                console.error('❌ Error al actualizar:', err);
                                cargarOrden(); // Intentar cargar de todas formas
                            });
                    }, 300); // 300ms optimizado
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Ocurrió un error al enviar la solicitud', 'error');
            });
    }

    /** 🔹 Cargar Orden */
    function cargarOrden() {
        // Anti-caché: siempre obtener datos frescos
        fetch('/POS/controllers/newPos/orden_actual.php?orden_id=' + ordenId + '&_=' + Date.now(), {
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        })
            .then(function(r) {
                if (!r.ok) {
                    throw new Error('Error HTTP: ' + r.status);
                }
                return r.json();
            })
            .then(function(data) {
                // Detectar si la orden fue cerrada
                if (data.orden_cerrada || data.error === 'orden_no_encontrada') {
                    console.log('⚠️ Orden cerrada detectada - Redirigiendo...');
                    window.location.href = 'index.php?page=mesas&_=' + Date.now();
                    return;
                }
                
                if (!data.items || data.items.length === 0) {
                    document.getElementById('orden-lista').innerHTML = '<div class="text-center py-8 text-slate-400">' +
                        '<i class="bi bi-cart-x text-4xl mb-3"></i>' +
                        '<p>No hay productos en la orden</p>' +
                        '<p class="text-sm mt-2">Selecciona productos del catálogo para comenzar</p>' +
                        '</div>';
                    document.getElementById('orden-totales').innerHTML = '<div class="text-slate-400 text-center py-4">' +
                        '<i class="bi bi-calculator mr-2"></i>' +
                        'Agrega productos para ver el total' +
                        '</div>';
                    return;
                }

                let html = '<div class="space-y-3">';
                data.items.forEach(function(item) {
                    let subtotal = item.precio * item.cantidad;
                    let isCancelado = item.cancelado == 1;
                    let preparado = parseInt(item.preparado || 0);
                    let cantidad = parseInt(item.cantidad || 0);
                    let cancelado = parseInt(item.cancelado || 0);
                    let pendiente_cancelacion = parseInt(item.pendiente_cancelacion || 0);
                    let disponibles = cantidad - preparado - cancelado - pendiente_cancelacion;

                    // Determinar estado visual del producto
                    let estadoInfo = getEstadoProducto(cantidad, preparado, cancelado, pendiente_cancelacion);

                    // Clases CSS diferentes según el estado
                    let cardClass = estadoInfo.cardClass;
                    let titleClass = estadoInfo.titleClass;

                    html += '<div class="' + cardClass + '">' +
                        '<div class="flex justify-between items-start mb-2">' +
                        '<h4 class="' + titleClass + '">' + item.nombre + '</h4>' +
                        estadoInfo.badge +
                        '</div>';
                    
                    // Mostrar variedades si existen
                    if (item.variedades && item.variedades.length > 0) {
                        html += '<div class="mt-2 mb-2 pl-3 border-l-2 border-orange-500/50">';
                        item.variedades.forEach(function(variedad) {
                            html += '<div class="text-xs text-orange-300 flex items-center gap-1">';
                            html += '<i class="bi bi-arrow-return-right text-orange-400"></i>';
                            html += '<span class="font-semibold">' + variedad.grupo_nombre + ':</span> ';
                            html += '<span>' + variedad.opcion_nombre + '</span>';
                            if (variedad.precio_adicional > 0) {
                                html += ' <span class="text-green-400 font-semibold">(+$' + parseFloat(variedad.precio_adicional).toFixed(2) + ')</span>';
                            }
                            html += '</div>';
                        });
                        html += '</div>';
                    }
                    
                    // Mostrar nota adicional si existe
                    if (item.nota_adicional && item.nota_adicional.trim() !== '') {
                        html += '<div class="mt-2 mb-2 p-2 bg-yellow-500/20 border border-yellow-500/40 rounded-md">';
                        html += '<div class="flex items-start gap-2">';
                        html += '<i class="bi bi-sticky text-yellow-400 mt-0.5"></i>';
                        html += '<div class="text-xs text-yellow-200">';
                        html += '<span class="font-semibold text-yellow-300">Nota adicional:</span> ';
                        html += '<span class="text-yellow-100">' + item.nota_adicional + '</span>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    }
                    
                    html += '<div class="flex justify-between items-center">' +
                        '<div class="text-slate-300 text-sm">' +
                        '$' + Number(item.precio).toFixed(2) + ' c/u' +
                        '</div>' +
                        '<div class="flex items-center space-x-3">' +
                        '<div class="flex items-center space-x-2">' +
                        '<span class="text-slate-400 text-sm">Cant:</span>';

                    if (isCancelado) {
                        html += '<span class="text-red-300 text-sm">' + item.cantidad + '</span>';
                    } else {
                        html += '<input type="number" min="1" value="' + item.cantidad + '" ' +
                            'class="sale-item-qty bg-slate-800 border border-slate-600 text-white px-2 py-1 rounded w-16 text-center text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors" ' +
                            'data-id="' + item.id + '">';
                    }

                    html += '</div>' +
                        '<div class="' + (isCancelado ? 'text-red-400 font-bold line-through' : 'text-green-400 font-bold') + '">' +
                        '$' + subtotal.toFixed(2) +
                        '</div>' +
                        '</div>' +
                        '</div>';

                    if (estadoInfo.detalles) {
                        html += '<div class="mt-2 text-xs ' + estadoInfo.detallesClass + '">' + estadoInfo.detalles + '</div>';
                    }

                    if (!isCancelado) {
                        html += '<div class="flex justify-between items-center mt-3">';

                        if (disponibles > 0) {
                            html += '<button class="sale-item-cancel-request text-orange-400 hover:text-orange-300 px-3 py-1 border border-orange-400 rounded-lg text-xs transition-colors" ' +
                                'data-id="' + item.id + '" ' +
                                'data-nombre="' + item.nombre + '"' +
                                'data-cantidad="' + cantidad + '"' +
                                'data-preparado="' + preparado + '"' +
                                'data-cancelado="' + cancelado + '"' +
                                'data-pendiente-cancelacion="' + pendiente_cancelacion + '"' +
                                'title="Solicitar cancelación">' +
                                '<i class="bi bi-exclamation-triangle mr-1"></i>Cancelar' +
                                '</button>';
                        } else if (pendiente_cancelacion > 0) {
                            html += '<span class="text-orange-400 text-xs">' +
                                '<i class="bi bi-clock mr-1"></i>Esperando aprobación de cancelación' +
                                '</span>';
                        } else {
                            html += '<span class="text-green-400 text-xs">' +
                                '<i class="bi bi-check-circle mr-1"></i>Todo preparado' +
                                '</span>';
                        }

                        // Solo mostrar botón de eliminar a administradores
                        if (esAdministrador) {
                            html += '<button class="sale-item-remove text-red-400 hover:text-red-300 p-1 transition-colors" data-id="' + item.id + '" title="Eliminar producto">' +
                                '<i class="bi bi-trash text-sm"></i>' +
                                '</button>';
                        }
                        
                        html += '</div>';
                    } else {
                        html += '<div class="mt-3 text-center">' +
                            '<span class="text-red-400 text-xs">' +
                            '<i class="bi bi-x-circle mr-1"></i>Producto cancelado por autorización' +
                            '</span>' +
                            '</div>';
                    }

                    html += '</div>';
                });
                html += '</div>';
                document.getElementById('orden-lista').innerHTML = html;

                // Totales con diseño mejorado
                let resumen = '<div class="space-y-2 text-slate-300">' +
                    '<div class="flex justify-between">' +
                    '<span>Subtotal:</span>' +
                    '<span class="text-blue-400 font-semibold">$' + Number(data.subtotal).toFixed(2) + '</span>' +
                    '</div>' +
                    '<div class="flex justify-between">' +
                    '<span>Descuento:</span>' +
                    '<span class="text-green-400 font-semibold">$' + Number(data.descuento || 0).toFixed(2) + '</span>' +
                    '</div>';

                // Mostrar total cancelado si existe
                if (data.total_cancelado && data.total_cancelado > 0) {
                    resumen += '<div class="flex justify-between">' +
                        '<span class="text-red-400">Cancelado:</span>' +
                        '<span class="text-red-400 font-semibold line-through">-$' + Number(data.total_cancelado).toFixed(2) + '</span>' +
                        '</div>';
                }

                resumen += '<hr class="border-slate-600 my-3">' +
                    '<div class="flex justify-between text-lg font-bold">' +
                    '<span class="text-white">Total:</span>' +
                    '<span class="text-green-400">$' + Number(data.total).toFixed(2) + '</span>' +
                    '</div>';

                // Mostrar conteo de productos cancelados
                if (data.productos_cancelados && data.productos_cancelados.length > 0) {
                    resumen += '<div class="mt-3 p-2 bg-red-900/20 rounded-lg border border-red-600/30">' +
                        '<div class="text-red-400 text-xs text-center">' +
                        '<i class="bi bi-exclamation-triangle mr-1"></i>' +
                        data.productos_cancelados.length + ' producto(s) cancelado(s)' +
                        '</div>' +
                        '</div>';
                }

                // Mostrar notas adicionales si existen
                let notasAdicionales = [];
                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(item) {
                        if (item.nota_adicional && item.nota_adicional.trim() !== '' && item.cancelado != 1) {
                            notasAdicionales.push({
                                nombre: item.nombre,
                                nota: item.nota_adicional
                            });
                        }
                    });
                }

                if (notasAdicionales.length > 0) {
                    resumen += '<div class="mt-3 p-3 bg-yellow-500/10 rounded-lg border border-yellow-500/30">' +
                        '<div class="text-yellow-300 text-xs font-semibold mb-2 flex items-center gap-1">' +
                        '<i class="bi bi-sticky"></i> Notas adicionales:' +
                        '</div>' +
                        '<div class="space-y-2">';
                    
                    notasAdicionales.forEach(function(item) {
                        resumen += '<div class="text-xs">' +
                            '<span class="text-yellow-400 font-medium">' + item.nombre + ':</span> ' +
                            '<span class="text-yellow-100">' + item.nota + '</span>' +
                            '</div>';
                    });
                    
                    resumen += '</div></div>';
                }

                resumen += '</div>';
                document.getElementById('orden-totales').innerHTML = resumen;
                
                // Refrescar indicadores de scroll después de actualizar la orden
                setTimeout(refreshScrollIndicators, 100);

                // Eventos para cambiar cantidad
                document.querySelectorAll('.sale-item-qty').forEach(function(input) {
                    input.onchange = function() {
                        let val = Math.max(1, parseInt(this.value));
                        this.classList.add('animate-pulse');

                        fetch('/POS/controllers/newPos/actualizar_producto_orden.php', {
                            method: 'POST',
                            body: new URLSearchParams({
                                producto_id: this.getAttribute('data-id'),
                                cantidad: val,
                                orden_id: ordenId
                            }),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).then(function(response) {
                            input.classList.remove('animate-pulse');
                            
                            // Toast no bloqueante
                            const toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            });

                            toast.fire({
                                icon: 'success',
                                title: 'Cantidad actualizada',
                                background: '#1f2937',
                                color: '#ffffff'
                            });

                            // Actualización optimizada con anti-caché
                            setTimeout(() => {
                                console.log('🔄 Actualizando orden tras cambio de cantidad...');
                                
                                // Pre-fetch con timestamp para datos frescos
                                fetch('/POS/controllers/newPos/orden_actual.php?orden_id=' + ordenId + '&_=' + Date.now())
                                    .then(res => res.json())
                                    .then(data => {
                                        console.log('✅ Orden actualizada:', data.items ? data.items.length : 0, 'items');
                                        cargarOrden();
                                    })
                                    .catch(err => {
                                        console.error('❌ Error al actualizar:', err);
                                        cargarOrden(); // Intentar cargar de todas formas
                                    });
                            }, 300); // 300ms optimizado
                        }).catch(function(error) {
                            input.classList.remove('animate-pulse');
                            console.error('Error al actualizar cantidad:', error);
                            Swal.fire('Error', 'No se pudo actualizar la cantidad', 'error');
                        });
                    };
                });

                // Eventos para solicitar cancelación de producto
                document.querySelectorAll('.sale-item-cancel-request').forEach(function(btn) {
                    btn.onclick = function() {
                        const ordenProductoId = this.getAttribute('data-id'); // ID del registro orden_productos
                        const productoNombre = this.getAttribute('data-nombre');
                        const cantidad = parseInt(this.getAttribute('data-cantidad'));
                        const preparado = parseInt(this.getAttribute('data-preparado'));
                        const cancelado = parseInt(this.getAttribute('data-cancelado'));
                        const pendiente_cancelacion = parseInt(this.getAttribute('data-pendiente-cancelacion') || 0);
                        const disponibles = cantidad - preparado - cancelado - pendiente_cancelacion;

                        // Si no hay productos disponibles para cancelar
                        if (disponibles === 0) {
                            if (preparado > 0) {
                                Swal.fire({
                                    title: 'No se puede cancelar',
                                    html: '<div class="text-left">' +
                                        '<p class="mb-3">Este producto está <strong>completamente preparado</strong>.</p>' +
                                        '<p class="mb-3 text-sm text-green-600"><i class="bi bi-check-circle mr-1"></i>' + preparado + ' unidad(es) ya lista(s)</p>' +
                                        '<p class="text-sm text-gray-600">Contacta al administrador si necesitas cancelar productos preparados.</p>' +
                                        '</div>',
                                    icon: 'info',
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#10b981'
                                });
                            } else if (pendiente_cancelacion > 0) {
                                Swal.fire({
                                    title: 'Cancelación pendiente',
                                    html: '<div class="text-left">' +
                                        '<p class="mb-3">Este producto ya tiene <strong>cancelación pendiente de aprobación</strong>.</p>' +
                                        '<p class="mb-3 text-sm text-orange-600"><i class="bi bi-clock mr-1"></i>' + pendiente_cancelacion + ' unidad(es) esperando aprobación</p>' +
                                        '<p class="text-sm text-gray-600">Espera la respuesta del administrador.</p>' +
                                        '</div>',
                                    icon: 'info',
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#f59e0b'
                                });
                            } else {
                                Swal.fire({
                                    title: 'No se puede cancelar',
                                    html: '<div class="text-left">' +
                                        '<p class="mb-3">Este producto ya ha sido <strong>completamente cancelado</strong>.</p>' +
                                        '<p class="mb-3 text-sm text-red-600"><i class="bi bi-x-circle mr-1"></i>' + cancelado + ' unidad(es) ya cancelada(s)</p>' +
                                        '</div>',
                                    icon: 'info',
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                            return;
                        }

                        // Si solo hay 1 unidad disponible para cancelar, cancelar directamente
                        if (disponibles === 1) {
                            let contextMessage = '';
                            if (preparado > 0) {
                                contextMessage = '<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">' +
                                    '<p class="text-green-700 text-sm">' +
                                    '<i class="bi bi-check-circle mr-1"></i>' +
                                    '<strong>' + preparado + ' unidad(es) ya preparada(s)</strong> - No se pueden cancelar' +
                                    '</p>' +
                                    '</div>';
                            }
                            
                            if (pendiente_cancelacion > 0) {
                                contextMessage += '<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">' +
                                    '<p class="text-orange-700 text-sm">' +
                                    '<i class="bi bi-clock mr-1"></i>' +
                                    '<strong>' + pendiente_cancelacion + ' unidad(es) esperando aprobación</strong> - Ya solicitadas' +
                                    '</p>' +
                                    '</div>';
                            }

                            Swal.fire({
                                title: 'Cancelar producto',
                                html: '<div class="text-left">' +
                                    '<p class="mb-3">Producto: <strong>' + productoNombre + '</strong></p>' +
                                    contextMessage +
                                    '<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">' +
                                    '<p class="text-orange-700 text-sm">' +
                                    '<i class="bi bi-clock mr-1"></i>' +
                                    '<strong>1 unidad disponible</strong> - Se cancelará' +
                                    '</p>' +
                                    '</div>' +
                                    '<p class="mb-3 text-sm text-gray-600">Se enviará un PIN al administrador por Email</p>' +
                                    '<textarea id="razon-cancelacion" class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" rows="3" placeholder="Motivo de la cancelación (opcional)"></textarea>' +
                                    '</div>',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Cancelar 1 unidad',
                                cancelButtonText: 'No cancelar',
                                confirmButtonColor: '#f59e0b',
                                preConfirm: function() {
                                    return document.getElementById('razon-cancelacion').value;
                                }
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    const razon = result.value || 'Cancelación de producto en preparación';
                                    mostrarModalCancelacion(ordenProductoId, productoNombre, 1, razon);
                                }
                            });
                            return;
                        }

                        // Si hay múltiples unidades disponibles, preguntar si cancelar todas o algunas
                        let contextMessage = '';
                        if (preparado > 0) {
                            contextMessage = '<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">' +
                                '<p class="text-green-700 text-sm">' +
                                '<i class="bi bi-check-circle mr-1"></i>' +
                                '<strong>' + preparado + ' unidad(es) ya preparada(s)</strong> - No se pueden cancelar' +
                                '</p>' +
                                '</div>';
                        }
                        
                        if (pendiente_cancelacion > 0) {
                            contextMessage += '<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">' +
                                '<p class="text-orange-700 text-sm">' +
                                '<i class="bi bi-clock mr-1"></i>' +
                                '<strong>' + pendiente_cancelacion + ' unidad(es) esperando aprobación</strong> - Ya solicitadas' +
                                '</p>' +
                                '</div>';
                        }

                        Swal.fire({
                            title: 'Tipo de cancelación',
                            html: '<div class="text-left">' +
                                '<p class="mb-3">Producto: <strong>' + productoNombre + '</strong></p>' +
                                contextMessage +
                                '<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">' +
                                '<p class="text-blue-700 text-sm">' +
                                '<i class="bi bi-info-circle mr-1"></i>' +
                                '<strong>' + disponibles + ' unidad(es) disponibles</strong> - Para cancelar' +
                                '</p>' +
                                '</div>' +
                                '<p class="mb-4 text-sm text-gray-600">¿Qué tipo de cancelación deseas realizar?</p>' +
                                '<div class="space-y-3">' +
                                '<button type="button" class="cancelacion-opcion w-full text-left p-3 border-2 border-orange-200 rounded-lg hover:border-orange-400 hover:bg-orange-50 transition-colors" data-tipo="total">' +
                                '<div class="flex items-center">' +
                                '<i class="bi bi-x-circle-fill text-orange-500 text-xl mr-3"></i>' +
                                '<div>' +
                                '<div class="font-semibold text-gray-900">Cancelación Total</div>' +
                                '<div class="text-sm text-gray-600">Cancelar todas las ' + disponibles + ' unidades disponibles</div>' +
                                '</div>' +
                                '</div>' +
                                '</button>' +
                                '<button type="button" class="cancelacion-opcion w-full text-left p-3 border-2 border-blue-200 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-colors" data-tipo="parcial">' +
                                '<div class="flex items-center">' +
                                '<i class="bi bi-dash-circle-fill text-blue-500 text-xl mr-3"></i>' +
                                '<div>' +
                                '<div class="font-semibold text-gray-900">Cancelación Parcial</div>' +
                                '<div class="text-sm text-gray-600">Elegir cuántas unidades cancelar (1 a ' + disponibles + ')</div>' +
                                '</div>' +
                                '</div>' +
                                '</button>' +
                                '</div>' +
                                '</div>',
                            icon: 'question',
                            showCancelButton: true,
                            showConfirmButton: false,
                            cancelButtonText: 'Cancelar operación',
                            didOpen: function() {
                                // Agregar eventos a los botones de opción
                                document.querySelectorAll('.cancelacion-opcion').forEach(function(opcionBtn) {
                                    opcionBtn.addEventListener('click', function() {
                                        const tipo = this.getAttribute('data-tipo');
                                        Swal.close();

                                        if (tipo === 'total') {
                                            // Cancelación total - confirmar directamente
                                            mostrarConfirmacionCancelacion(ordenProductoId, productoNombre, disponibles, preparado);
                                        } else {
                                            // Cancelación parcial - preguntar cantidad
                                            mostrarSelectorCantidad(ordenProductoId, productoNombre, disponibles, preparado, pendiente_cancelacion);
                                        }
                                    });
                                });
                            }
                        });
                    };
                });

                // Eventos para eliminar producto (solo administradores)
                if (esAdministrador) {
                    document.querySelectorAll('.sale-item-remove').forEach(function(btn) {
                        btn.onclick = function() {
                            const button = this;
                            const productoId = this.getAttribute('data-id');

                            Swal.fire({
                                title: '¿Eliminar producto?',
                                text: 'Se quitará este producto de la orden',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, eliminar',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#ef4444'
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    button.classList.add('animate-pulse');

                                    fetch('/POS/controllers/newPos/actualizar_producto_orden.php', {
                                        method: 'POST',
                                        body: new URLSearchParams({
                                            producto_id: productoId,
                                            cantidad: 0,
                                            orden_id: ordenId
                                        }),
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    }).then(function(response) {
                                        return response.json();
                                    }).then(function(resp) {
                                        if (resp.status === 'ok' || resp.success) {
                                            // Toast no bloqueante
                                            const toast = Swal.mixin({
                                                toast: true,
                                                position: 'top-end',
                                                showConfirmButton: false,
                                                timer: 2000,
                                                timerProgressBar: true
                                            });

                                            toast.fire({
                                                icon: 'success',
                                                title: 'Producto eliminado',
                                                background: '#1f2937',
                                                color: '#ffffff'
                                            });

                                            // Actualización optimizada con anti-caché
                                            setTimeout(() => {
                                                console.log('🔄 Actualizando orden tras eliminar producto...');
                                                
                                                // Pre-fetch con timestamp para datos frescos
                                                fetch('/POS/controllers/newPos/orden_actual.php?orden_id=' + ordenId + '&_=' + Date.now())
                                                    .then(res => res.json())
                                                    .then(data => {
                                                        console.log('✅ Orden actualizada después de eliminar');
                                                        cargarOrden();
                                                    })
                                                    .catch(err => {
                                                        console.error('❌ Error al actualizar:', err);
                                                        cargarOrden(); // Intentar cargar de todas formas
                                                    });
                                            }, 300); // 300ms optimizado
                                        } else {
                                            button.classList.remove('animate-pulse');
                                            Swal.fire('Error', resp.msg || 'No se pudo eliminar el producto', 'error');
                                        }
                                    }).catch(function(error) {
                                        button.classList.remove('animate-pulse');
                                        console.error('Error al eliminar producto:', error);
                                        Swal.fire('Error', 'No se pudo eliminar el producto', 'error');
                                    });
                                }
                            });
                        };
                    });
                }
            })
            .catch(function(error) {
                console.error('Error al cargar orden:', error);
                document.getElementById('orden-lista').innerHTML = '<div class="text-center py-8 text-red-400">' +
                    '<i class="bi bi-exclamation-triangle text-4xl mb-3"></i>' +
                    '<p>Error al cargar la orden</p>' +
                    '</div>';
            });
    }

    /** 🔹 Inicialización */
    document.addEventListener('DOMContentLoaded', function() {
        cargarCategorias();
        cargarProductos();
        cargarOrden();
        
        // Auto-actualización cada 10 segundos
        setInterval(cargarOrden, 10000);
        
        // Actualizar cuando el usuario vuelve a la pestaña
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                console.log('👁️ Usuario volvió a la pestaña - Actualizando orden...');
                cargarOrden();
                cargarProductos();
            }
        });
        
        // Actualizar cuando la ventana recupera el foco
        window.addEventListener('focus', function() {
            console.log('🎯 Ventana enfocada - Actualizando orden...');
            cargarOrden();
        });

        // Configurar indicadores de scroll
        setupScrollIndicators();
        
        // Configurar observer para cambios de contenido
        setupContentObserver();

        const buscador = document.getElementById('buscador');
        if (buscador) {
            buscador.addEventListener('input', function(e) {
                // Activar visualmente el botón "Todos" cuando se busca
                document.querySelectorAll('.category-btn').forEach(function(btn) {
                    btn.classList.remove('active');
                });

                const todosBtn = document.querySelector('.category-btn[data-cat="0"]');
                if (todosBtn) {
                    todosBtn.classList.add('active');
                }

                cargarProductos(0, e.target.value);
            });
        }

        const btnCancelar = document.getElementById('cancelar_orden');
        if (btnCancelar && esAdministrador) {
            btnCancelar.onclick = function() {
                Swal.fire({
                    title: '¿Cancelar orden?',
                    text: 'Esta acción eliminará todos los productos de la orden y no se puede deshacer',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar orden',
                    cancelButtonText: 'No, mantener orden',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280'
                }).then(function(res) {
                    if (res.isConfirmed) {
                        // Mostrar loading
                        Swal.fire({
                            title: 'Cancelando orden...',
                            allowOutsideClick: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });

                        fetch('/POS/controllers/newPos/cancelar_orden.php', {
                                method: 'POST',
                                body: new URLSearchParams({
                                    orden_id: ordenId
                                }),
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(function(response) {
                                return response.json();
                            })
                            .then(function(data) {
                                if (data.status === 'ok') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Orden cancelada',
                                        text: 'La orden ha sido cancelada exitosamente',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(function() {
                                        // Redireccionar a la vista de mesas
                                        window.location.href = 'index.php?page=mesas';
                                    });
                                } else {
                                    Swal.fire('Error', data.msg || 'No se pudo cancelar la orden', 'error');
                                }
                            })
                            .catch(function(error) {
                                console.error('Error:', error);
                                Swal.fire('Error', 'Ocurrió un error al cancelar la orden', 'error');
                            });
                    }
                });
            };
        }

        // Manejar el formulario de cerrar orden
        const cerrarOrdenForm = document.getElementById('cerrar-orden-form');
        if (cerrarOrdenForm) {
            cerrarOrdenForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Verificar si hay productos sin preparar antes de permitir cerrar
                fetch('/POS/controllers/newPos/orden_actual.php?orden_id=' + ordenId)
                    .then(function(r) {
                        if (!r.ok) {
                            throw new Error('Error HTTP: ' + r.status + ' ' + r.statusText);
                        }
                        return r.json();
                    })
                    .then(function(data) {
                        // Contar productos sin preparar
                        let productosSinPreparar = 0;
                        let detallesPendientes = [];
                        
                        if (data.items && data.items.length > 0) {
                            data.items.forEach(function(item) {
                                const preparado = parseInt(item.preparado || 0);
                                const cantidad = parseInt(item.cantidad || 0);
                                const cancelado = parseInt(item.cancelado || 0);
                                const pendientes = cantidad - preparado - cancelado;
                                
                                if (pendientes > 0) {
                                    productosSinPreparar += pendientes;
                                    detallesPendientes.push({
                                        nombre: item.nombre,
                                        pendientes: pendientes
                                    });
                                }
                            });
                        }

                        // Si hay productos sin preparar, mostrar error
                        if (productosSinPreparar > 0) {
                            let listaProductos = '<ul class="text-left mt-3 space-y-1">';
                            detallesPendientes.forEach(function(item) {
                                listaProductos += '<li class="text-orange-700">• <strong>' + item.nombre + '</strong>: ' + item.pendientes + ' unidad(es) pendiente(s)</li>';
                            });
                            listaProductos += '</ul>';

                            Swal.fire({
                                title: '❌ No se puede cerrar la orden',
                                html: '<div class="text-center">' +
                                    '<div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">' +
                                    '<p class="text-orange-800 font-semibold mb-2">' +
                                    '<i class="bi bi-exclamation-triangle mr-2"></i>' +
                                    'Hay ' + productosSinPreparar + ' producto(s) sin preparar' +
                                    '</p>' +
                                    listaProductos +
                                    '</div>' +
                                    '<p class="text-sm text-gray-600 mb-3">Para cerrar la orden debes:</p>' +
                                    '<div class="text-left space-y-2 text-sm text-gray-700">' +
                                    '<div class="flex items-center">' +
                                    '<i class="bi bi-check-circle text-green-500 mr-2"></i>' +
                                    'Completar la preparación de todos los productos' +
                                    '</div>' +
                                    '<div class="flex items-center">' +
                                    '<i class="bi bi-x-circle text-orange-500 mr-2"></i>' +
                                    'O cancelar los productos que no se van a preparar' +
                                    '</div>' +
                                    '</div>' +
                                    '</div>',
                                icon: 'warning',
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#f59e0b',
                                customClass: {
                                    popup: 'text-left'
                                }
                            });
                            return;
                        }

                        // Si todos los productos están preparados, continuar con el cierre normal
                        procederConCierreOrden(data);
                    })
                    .catch(function(error) {
                        console.error('Error al verificar productos:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo verificar el estado de los productos. Error: ' + error.message,
                            confirmButtonColor: '#ef4444'
                        });
                    });
            });
        }

        function procederConCierreOrden(data) {
            // Obtener el total actual - buscar en toda la sección de totales
            const totalElements = document.querySelectorAll('#orden-totales .text-green-400');
            let totalText = '$0.00';
            let total = 0;

            // El total es el último elemento con clase text-green-400 (el total final)
            if (totalElements.length > 0) {
                totalText = totalElements[totalElements.length - 1].textContent.trim();
                // Extraer el número del texto (remover $ y convertir a float)
                total = parseFloat(totalText.replace('$', '').replace(',', '')) || 0;
            }

            Swal.fire({
                title: '✅ ¿Cerrar y pagar orden?',
                html: '<div class="text-center">' +
                    '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">' +
                    '<p class="text-green-700 font-semibold mb-2">' +
                    '<i class="bi bi-check-circle mr-2"></i>' +
                    'Todos los productos están preparados' +
                    '</p>' +
                    '</div>' +
                    '<p class="mb-4">Esta acción cerrará la orden y marcará la mesa como disponible</p>' +
                    '<div class="bg-gray-100 p-4 rounded-lg mb-4">' +
                    '<p class="text-lg font-bold text-green-600">Total a pagar: ' + totalText + '</p>' +
                    '</div>' +
                    '<div class="mb-4">' +
                    '<label class="block text-sm font-medium text-gray-700 mb-2">Método de pago:</label>' +
                    '<div class="grid grid-cols-2 gap-3 mb-4">' +
                    '<label class="flex items-center justify-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">' +
                    '<input type="radio" name="metodo_pago" value="efectivo" checked class="mr-2" onchange="toggleEfectivoFields()">' +
                    '<span class="flex items-center">' +
                    '<i class="bi bi-cash text-green-600 mr-2"></i>' +
                    'Efectivo' +
                    '</span>' +
                    '</label>' +
                    '<label class="flex items-center justify-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">' +
                    '<input type="radio" name="metodo_pago" value="debito" class="mr-2" onchange="toggleEfectivoFields()">' +
                    '<span class="flex items-center">' +
                    '<i class="bi bi-credit-card-2-front text-blue-600 mr-2"></i>' +
                    'Débito' +
                    '</span>' +
                    '</label>' +
                    '<label class="flex items-center justify-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">' +
                    '<input type="radio" name="metodo_pago" value="credito" class="mr-2" onchange="toggleEfectivoFields()">' +
                    '<span class="flex items-center">' +
                    '<i class="bi bi-credit-card text-purple-600 mr-2"></i>' +
                    'Crédito' +
                    '</span>' +
                    '</label>' +
                    '<label class="flex items-center justify-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">' +
                    '<input type="radio" name="metodo_pago" value="transferencia" class="mr-2" onchange="toggleEfectivoFields()">' +
                    '<span class="flex items-center">' +
                    '<i class="bi bi-bank text-orange-600 mr-2"></i>' +
                    'Transferencia' +
                    '</span>' +
                    '</label>' +
                    '</div>' +
                    '<!-- Campos para efectivo -->' +
                    '<div id="efectivo-fields" class="bg-blue-50 border border-blue-200 rounded-lg p-4">' +
                    '<div class="mb-3">' +
                    '<label class="block text-sm font-medium text-gray-700 mb-1">Dinero recibido:</label>' +
                    '<input type="number" id="dinero-recibido" class="w-full px-3 py-2 border border-gray-300 rounded-md text-center text-lg font-semibold" ' +
                    'placeholder="$0.00" step="0.01" min="' + total + '" oninput="calcularCambio(' + total + ')">' +
                    '</div>' +
                    '<div id="cambio-display" class="text-center p-2 bg-green-100 rounded-md" style="display: none;">' +
                    '<p class="text-sm text-gray-600">Cambio a entregar:</p>' +
                    '<p class="text-xl font-bold text-green-700" id="cambio-amount">$0.00</p>' +
                    '</div>' +
                    '<div id="cambio-error" class="text-center p-2 bg-red-100 rounded-md text-red-700" style="display: none;">' +
                    '<p class="text-sm">El dinero recibido debe ser mayor o igual al total</p>' +
                    '</div>' +
                    '</div>' +
                    '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar orden',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280'
            }).then(function(result) {
                if (result.isConfirmed) {
                    // Obtener el método de pago seleccionado
                    const metodoPago = document.querySelector('input[name="metodo_pago"]:checked').value;

                    // Validar efectivo si es necesario
                    if (metodoPago === 'efectivo') {
                        const dineroRecibido = parseFloat(document.getElementById('dinero-recibido').value) || 0;
                        
                        if (dineroRecibido < total) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Dinero insuficiente',
                                text: 'El dinero recibido debe ser mayor o igual al total de la cuenta',
                                confirmButtonColor: '#ef4444'
                            });
                            return;
                        }
                    }

                    // Agregar el método de pago al formulario
                    const form = document.getElementById('cerrar-orden-form');
                    const metodoPagoInput = document.createElement('input');
                    metodoPagoInput.type = 'hidden';
                    metodoPagoInput.name = 'metodo_pago';
                    metodoPagoInput.value = metodoPago;
                    form.appendChild(metodoPagoInput);

                    // Si es efectivo, agregar información del dinero recibido y cambio
                    if (metodoPago === 'efectivo') {
                        const dineroRecibido = parseFloat(document.getElementById('dinero-recibido').value);
                        const cambio = dineroRecibido - total;

                        const dineroRecibidoInput = document.createElement('input');
                        dineroRecibidoInput.type = 'hidden';
                        dineroRecibidoInput.name = 'dinero_recibido';
                        dineroRecibidoInput.value = dineroRecibido.toFixed(2);
                        form.appendChild(dineroRecibidoInput);

                        const cambioInput = document.createElement('input');
                        cambioInput.type = 'hidden';
                        cambioInput.name = 'cambio';
                        cambioInput.value = cambio.toFixed(2);
                        form.appendChild(cambioInput);
                    }

                    let loadingText = 'Procesando pago por ' + metodoPago;
                    if (metodoPago === 'efectivo') {
                        const dineroRecibido = parseFloat(document.getElementById('dinero-recibido').value);
                        const cambio = dineroRecibido - total;
                        if (cambio > 0) {
                            loadingText += '. Cambio: $' + cambio.toFixed(2);
                        } else {
                            loadingText += ' (pago exacto)';
                        }
                    }

                    // Mostrar loading
                    Swal.fire({
                        title: 'Cerrando orden...',
                        text: loadingText + ' y liberando mesa',
                        allowOutsideClick: false,
                        didOpen: function() {
                            Swal.showLoading();
                        }
                    });

                    // Enviar el formulario
                    form.submit();
                }
            });
        }
    });

    /** 🔹 Configurar indicadores de scroll */
    function setupScrollIndicators() {
        console.log('🔹 Configurando indicadores de scroll...');
        
        // Configurar indicadores para la orden
        const ordenScroll = document.querySelector('.orden-scroll-area');
        
        if (ordenScroll) {
            const ordenContainer = ordenScroll.closest('.relative');
            if (ordenContainer) {
                // Crear indicadores si no existen
                let fadeTop = ordenContainer.querySelector('.scroll-fade-top');
                let fadeBottom = ordenContainer.querySelector('.scroll-fade-bottom');
                
                if (!fadeTop) {
                    fadeTop = document.createElement('div');
                    fadeTop.className = 'scroll-fade-top';
                    ordenContainer.appendChild(fadeTop);
                }
                
                if (!fadeBottom) {
                    fadeBottom = document.createElement('div');
                    fadeBottom.className = 'scroll-fade-bottom';
                    ordenContainer.appendChild(fadeBottom);
                }
                
                ordenScroll.addEventListener('scroll', function() {
                    updateScrollIndicators(this, fadeTop, fadeBottom);
                });
                
                // Verificar inicial después de que el DOM esté listo
                setTimeout(() => updateScrollIndicators(ordenScroll, fadeTop, fadeBottom), 200);
            }
        }

        // Configurar indicadores para el catálogo
        const catalogoScroll = document.querySelector('.catalogo-scroll-area');
        
        if (catalogoScroll) {
            const catalogoContainer = catalogoScroll.closest('.relative');
            if (catalogoContainer) {
                // Crear indicadores si no existen
                let fadeTop = catalogoContainer.querySelector('.scroll-fade-top');
                let fadeBottom = catalogoContainer.querySelector('.scroll-fade-bottom');
                
                if (!fadeTop) {
                    fadeTop = document.createElement('div');
                    fadeTop.className = 'scroll-fade-top';
                    catalogoContainer.appendChild(fadeTop);
                }
                
                if (!fadeBottom) {
                    fadeBottom = document.createElement('div');
                    fadeBottom.className = 'scroll-fade-bottom';
                    catalogoContainer.appendChild(fadeBottom);
                }
                
                catalogoScroll.addEventListener('scroll', function() {
                    updateScrollIndicators(this, fadeTop, fadeBottom);
                });
                
                // Verificar inicial después de que el DOM esté listo
                setTimeout(() => updateScrollIndicators(catalogoScroll, fadeTop, fadeBottom), 200);
            }
        }
    }

    /** 🔹 Actualizar indicadores de scroll */
    function updateScrollIndicators(scrollElement, fadeTop, fadeBottom) {
        if (!scrollElement || !fadeTop || !fadeBottom) return;
        
        const { scrollTop, scrollHeight, clientHeight } = scrollElement;
        const threshold = 5; // Umbral mínimo para mostrar indicadores
        
        // Calcular si hay contenido que scrollear
        const hasScrollableContent = scrollHeight > clientHeight;
        
        if (!hasScrollableContent) {
            // Si no hay contenido para scroll, ocultar ambos indicadores
            fadeTop.style.opacity = '0';
            fadeBottom.style.opacity = '0';
            return;
        }
        
        // Mostrar fade superior si no estamos en el top
        if (scrollTop > threshold) {
            fadeTop.style.opacity = '1';
        } else {
            fadeTop.style.opacity = '0';
        }
        
        // Mostrar fade inferior si no estamos en el bottom
        const isAtBottom = scrollTop >= scrollHeight - clientHeight - threshold;
        if (!isAtBottom) {
            fadeBottom.style.opacity = '1';
        } else {
            fadeBottom.style.opacity = '0';
        }
        
        // Debug para desarrollo (comentar en producción)
        // console.log(`Scroll: ${scrollTop}, Height: ${scrollHeight}, Client: ${clientHeight}, AtBottom: ${isAtBottom}`);
    }

    /** 🔹 Scroll suave para elementos */
    function scrollToProduct(productElement) {
        if (productElement && productElement.scrollIntoView) {
            productElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }

    /** 🔹 Recalcular indicadores cuando cambia el contenido */
    function refreshScrollIndicators() {
        const ordenScroll = document.querySelector('.orden-scroll-area');
        const catalogoScroll = document.querySelector('.catalogo-scroll-area');
        
        if (ordenScroll) {
            const container = ordenScroll.closest('.relative');
            if (container) {
                const fadeTop = container.querySelector('.scroll-fade-top');
                const fadeBottom = container.querySelector('.scroll-fade-bottom');
                if (fadeTop && fadeBottom) {
                    updateScrollIndicators(ordenScroll, fadeTop, fadeBottom);
                }
            }
        }
        
        if (catalogoScroll) {
            const container = catalogoScroll.closest('.relative');
            if (container) {
                const fadeTop = container.querySelector('.scroll-fade-top');
                const fadeBottom = container.querySelector('.scroll-fade-bottom');
                if (fadeTop && fadeBottom) {
                    updateScrollIndicators(catalogoScroll, fadeTop, fadeBottom);
                }
            }
        }
    }

    // Observer para detectar cambios en el contenido y actualizar indicadores
    function setupContentObserver() {
        const ordenArea = document.querySelector('.orden-scroll-area');
        const catalogoArea = document.querySelector('.catalogo-scroll-area');
        
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                let shouldRefresh = false;
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        shouldRefresh = true;
                    }
                });
                
                if (shouldRefresh) {
                    // Dar tiempo para que el DOM se actualice
                    setTimeout(refreshScrollIndicators, 100);
                }
            });
            
            if (ordenArea) {
                observer.observe(ordenArea, { 
                    childList: true, 
                    subtree: true, 
                    characterData: true 
                });
            }
            
            if (catalogoArea) {
                observer.observe(catalogoArea, { 
                    childList: true, 
                    subtree: true, 
                    characterData: true 
                });
            }
        }
    }
</script>

<!-- 🎨 Modal de Selección de Variedades -->
<div id="variedadesModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[60] hidden flex items-center justify-center p-4">
    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col border border-slate-600/50">
        <!-- Header -->
        <div class="bg-gradient-to-r from-orange-600/20 to-yellow-600/20 border-b border-orange-500/30 p-6 flex-shrink-0">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-yellow-600 rounded-xl flex items-center justify-center">
                        <i class="bi bi-list-ul text-xl text-white"></i>
                    </div>
                    <span id="variedad-producto-nombre">Seleccionar Variedades</span>
                </h3>
                <button type="button" onclick="cerrarModalVariedades()" class="text-gray-400 hover:text-white hover:bg-slate-700/50 w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 hover:scale-110">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Contenido con scroll -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6" style="scrollbar-width: thin; scrollbar-color: rgba(71, 85, 105, 0.5) rgba(15, 23, 42, 0.3);">
            <div id="variedades-grupos-container">
                <!-- Los grupos de variedades se cargarán aquí dinámicamente -->
            </div>
            
            <!-- Sección de nota adicional -->
            <div class="bg-slate-700/50 rounded-xl p-5 border border-slate-600/50">
                <div class="flex items-center mb-4">
                    <input type="checkbox" id="check-nota-adicional" 
                           class="w-5 h-5 text-orange-600 bg-slate-700 border-slate-500 rounded focus:ring-orange-500 focus:ring-2 cursor-pointer"
                           onchange="toggleNotaAdicional()">
                    <label for="check-nota-adicional" class="ml-3 text-lg font-bold text-orange-400 cursor-pointer">
                        <i class="bi bi-sticky mr-2"></i>¿Agregar nota adicional?
                    </label>
                </div>
                
                <div id="campo-nota-adicional" class="hidden">
                    <textarea id="nota-adicional-texto" 
                              class="w-full px-4 py-3 bg-slate-800/50 border border-slate-600/30 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all resize-none"
                              rows="3"
                              maxlength="200"
                              placeholder="Ejemplo: Sin cebolla, término medio, extra picante, etc."></textarea>
                    <div class="flex items-center justify-between mt-2">
                        <p class="text-xs text-slate-400">
                            <i class="bi bi-info-circle mr-1"></i>Esta nota será visible en cocina y bar
                        </p>
                        <span id="contador-caracteres" class="text-xs text-slate-400">0/200</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer con botones -->
        <div class="bg-gradient-to-r from-slate-900/80 to-slate-800/80 border-t border-slate-700/50 p-6 flex gap-3 flex-shrink-0">
            <button type="button" onclick="cerrarModalVariedades()" class="flex-1 px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-xl transition-all duration-300 font-semibold shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                <i class="bi bi-x-circle"></i>
                Cancelar
            </button>
            <button type="button" onclick="confirmarVariedades()" class="flex-1 px-6 py-3 bg-gradient-to-r from-orange-600 to-yellow-600 hover:from-orange-700 hover:to-yellow-700 text-white rounded-xl transition-all duration-300 font-semibold shadow-md hover:shadow-lg hover:scale-105 flex items-center justify-center gap-2">
                <i class="bi bi-check-lg text-xl"></i>
                Agregar a la Orden
            </button>
        </div>
    </div>
</div>

<!-- Incluir sistema de impresión térmica -->
<script src="js/impresion-termica.js"></script>
<script>
    // Hacer disponible la configuración de impresora para JavaScript
    window.configImpresoraNombre = '<?= $config_impresion['nombre_impresora'] ?? '' ?>';
</script>
</div>