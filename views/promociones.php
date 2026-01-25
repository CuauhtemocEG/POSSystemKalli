<?php
if (!hasPermission('productos', 'ver')) {
    header('Location: index.php?page=error-403');
    exit;
}

$puedeEditar = hasPermission('productos', 'editar');
$puedeCrear = hasPermission('productos', 'crear');
$puedeEliminar = hasPermission('productos', 'eliminar');
?>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">
                    <i class="bi bi-tag-fill text-yellow-400 mr-2"></i>
                    Gestión de Promociones
                </h1>
                <p class="text-gray-400">Configura promociones como 2x1, 3x2, descuentos por categoría y más</p>
            </div>
            <?php if ($puedeCrear): ?>
            <button onclick="abrirModalCrear()" class="mt-4 md:mt-0 bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                Nueva Promoción
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 mb-6 shadow-xl border border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Buscar</label>
                <input type="text" id="searchPromo" placeholder="Buscar promoción..." class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Tipo</label>
                <select id="filterTipo" class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                    <option value="">Todos los tipos</option>
                    <option value="2x1">2x1</option>
                    <option value="3x2">3x2</option>
                    <option value="descuento_porcentaje">Descuento %</option>
                    <option value="descuento_fijo">Descuento Fijo</option>
                    <option value="descuento_personal">Descuento Personal</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Estado</label>
                <select id="filterEstado" class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                    <option value="">Todas</option>
                    <option value="1">Activas</option>
                    <option value="0">Inactivas</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Lista de Promociones -->
    <div id="promocionesContainer" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- Las promociones se cargarán aquí dinámicamente -->
    </div>
</div>

<!-- Modal Crear/Editar Promoción -->
<div id="modalPromocion" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl border border-gray-700">
            <!-- Header -->
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 px-6 py-4 rounded-t-2xl">
                <div class="flex justify-between items-center">
                    <h3 id="modalTitle" class="text-xl font-bold text-white">Nueva Promoción</h3>
                    <button onclick="cerrarModal()" class="text-white hover:text-gray-200 transition-colors">
                        <i class="bi bi-x-lg text-2xl"></i>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <form id="formPromocion" class="p-6">
                <input type="hidden" id="promo_id" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nombre -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Nombre de la Promoción *
                        </label>
                        <input type="text" id="promo_nombre" name="nombre" required
                               class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                               placeholder="Ej: 2x1 en Bebidas">
                    </div>

                    <!-- Descripción -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Descripción
                        </label>
                        <textarea id="promo_descripcion" name="descripcion" rows="2"
                                  class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                  placeholder="Describe los detalles de la promoción"></textarea>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Tipo de Promoción *
                        </label>
                        <select id="promo_tipo" name="tipo" required onchange="actualizarCamposTipo()"
                                class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">Seleccionar...</option>
                            <option value="2x1">2x1 - Lleva 2 paga 1</option>
                            <option value="3x2">3x2 - Lleva 3 paga 2</option>
                            <option value="descuento_porcentaje">Descuento en Porcentaje</option>
                            <option value="descuento_fijo">Descuento Fijo</option>
                            <option value="descuento_personal">Descuento Personal</option>
                        </select>
                    </div>

                    <!-- Valor -->
                    <div id="campoValor" class="hidden">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Valor <span id="labelValor">(% o monto)</span>
                        </label>
                        <input type="number" id="promo_valor" name="valor" step="0.01" min="0"
                               class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                               placeholder="0.00">
                    </div>

                    <!-- Aplica a -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Aplica a *
                        </label>
                        <select id="promo_aplica_a" name="aplica_a" required onchange="actualizarCamposAplicacion()"
                                class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="productos">Productos Específicos</option>
                            <option value="categorias">Categorías</option>
                            <option value="todos">Todos los Productos</option>
                        </select>
                    </div>

                    <!-- Prioridad -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Prioridad
                            <span class="text-xs text-gray-400">(Mayor = se aplica primero)</span>
                        </label>
                        <input type="number" id="promo_prioridad" name="prioridad" value="0" min="0"
                               class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                    </div>

                    <!-- Productos (si aplica) -->
                    <div id="campoProductos" class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Productos
                        </label>
                        <div class="relative">
                            <input type="text" id="searchProductos" placeholder="Buscar productos..."
                                   class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 mb-2">
                            <div id="productosSeleccionables" class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-60 overflow-y-auto bg-gray-700/30 rounded-lg p-3 border border-gray-600">
                                <!-- Productos se cargarán aquí -->
                            </div>
                        </div>
                    </div>

                    <!-- Categorías (si aplica) -->
                    <div id="campoCategorias" class="md:col-span-2 hidden">
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Categorías
                        </label>
                        <div id="categoriasSeleccionables" class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <!-- Categorías se cargarán aquí -->
                        </div>
                    </div>

                    <!-- Opciones adicionales -->
                    <div class="md:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    Mínimo de Productos
                                </label>
                                <input type="number" id="promo_minimo_productos" name="minimo_productos" value="2" min="1"
                                       class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            </div>

                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer mt-6">
                                    <input type="checkbox" id="promo_mayor_valor" name="aplicar_mayor_valor" checked
                                           class="w-4 h-4 text-yellow-500 bg-gray-700 border-gray-600 rounded focus:ring-yellow-500">
                                    <span class="ml-2 text-sm text-gray-300">Aplicar a mayor valor</span>
                                </label>
                            </div>

                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer mt-6">
                                    <input type="checkbox" id="promo_activa" name="activa" checked
                                           class="w-4 h-4 text-yellow-500 bg-gray-700 border-gray-600 rounded focus:ring-yellow-500">
                                    <span class="ml-2 text-sm text-gray-300">Promoción activa</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Fechas -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Fecha Inicio (opcional)
                        </label>
                        <input type="datetime-local" id="promo_fecha_inicio" name="fecha_inicio"
                               class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Fecha Fin (opcional)
                        </label>
                        <input type="datetime-local" id="promo_fecha_fin" name="fecha_fin"
                               class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-700">
                    <button type="button" onclick="cerrarModal()"
                            class="px-6 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white rounded-lg font-medium shadow-lg transition-all">
                        Guardar Promoción
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let promociones = [];
let productos = [];
let categorias = [];
let productosFiltrados = [];

// Cargar datos iniciales
document.addEventListener('DOMContentLoaded', function() {
    cargarPromociones();
    cargarProductos();
    cargarCategorias();
    
    // Eventos de filtros
    document.getElementById('searchPromo').addEventListener('input', filtrarPromociones);
    document.getElementById('filterTipo').addEventListener('change', filtrarPromociones);
    document.getElementById('filterEstado').addEventListener('change', filtrarPromociones);
    document.getElementById('searchProductos').addEventListener('input', filtrarProductosModal);
    
    // Form submit
    document.getElementById('formPromocion').addEventListener('submit', guardarPromocion);
});

// Cargar promociones
function cargarPromociones() {
    fetch('api/promotionsController/api.php?action=list')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'ok') {
                promociones = data.data;
                mostrarPromociones();
            } else {
                console.error('Error en respuesta:', data);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cargar',
                    text: data.msg || 'No se pudieron cargar las promociones',
                    background: '#1f2937',
                    color: '#fff'
                });
            }
        })
        .catch(err => {
            console.error('Error al cargar promociones:', err);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor',
                background: '#1f2937',
                color: '#fff'
            });
        });
}

// Mostrar promociones
function mostrarPromociones() {
    const container = document.getElementById('promocionesContainer');
    const search = document.getElementById('searchPromo').value.toLowerCase();
    const filterTipo = document.getElementById('filterTipo').value;
    const filterEstado = document.getElementById('filterEstado').value;
    
    let promosFiltradas = promociones.filter(promo => {
        const matchSearch = promo.nombre.toLowerCase().includes(search);
        const matchTipo = !filterTipo || promo.tipo === filterTipo;
        const matchEstado = !filterEstado || promo.activa == filterEstado;
        return matchSearch && matchTipo && matchEstado;
    });
    
    if (promosFiltradas.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="bi bi-inbox text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-lg">No se encontraron promociones</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = promosFiltradas.map(promo => {
        const esActiva = promo.activa == 1;
        const icono = obtenerIconoTipo(promo.tipo);
        const colorBadge = esActiva ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400';
        
        return `
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl shadow-xl border ${esActiva ? 'border-yellow-500/30' : 'border-gray-700'} hover:shadow-2xl transition-all duration-300 overflow-hidden group">
                <!-- Header -->
                <div class="bg-gradient-to-r ${esActiva ? 'from-yellow-500/20 to-yellow-600/20' : 'from-gray-700/20 to-gray-600/20'} p-4 border-b border-gray-700">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-white mb-1 flex items-center gap-2">
                                <i class="${icono} text-yellow-400"></i>
                                ${promo.nombre}
                            </h3>
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold ${colorBadge}">
                                ${esActiva ? 'Activa' : 'Inactiva'}
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <?php if ($puedeEditar): ?>
                            <button onclick="togglePromocion(${promo.id})" 
                                    class="text-gray-400 hover:text-yellow-400 transition-colors p-1"
                                    title="${esActiva ? 'Desactivar' : 'Activar'}">
                                <i class="bi ${esActiva ? 'bi-toggle-on' : 'bi-toggle-off'} text-xl"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Body -->
                <div class="p-4 space-y-3">
                    ${promo.descripcion ? `<p class="text-gray-400 text-sm">${promo.descripcion}</p>` : ''}
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2 text-gray-300">
                            <i class="bi bi-tag text-yellow-400"></i>
                            <span class="font-semibold">Tipo:</span>
                            <span>${obtenerNombreTipo(promo.tipo)}</span>
                        </div>
                        
                        ${promo.valor ? `
                        <div class="flex items-center gap-2 text-gray-300">
                            <i class="bi bi-percent text-yellow-400"></i>
                            <span class="font-semibold">Valor:</span>
                            <span>${promo.valor}${promo.tipo.includes('porcentaje') || promo.tipo.includes('personal') ? '%' : ' MXN'}</span>
                        </div>
                        ` : ''}
                        
                        <div class="flex items-center gap-2 text-gray-300">
                            <i class="bi bi-bullseye text-yellow-400"></i>
                            <span class="font-semibold">Aplica a:</span>
                            <span>${obtenerNombreAplicacion(promo.aplica_a)}</span>
                        </div>
                        
                        <div class="flex items-center gap-2 text-gray-300">
                            <i class="bi bi-arrow-up-circle text-yellow-400"></i>
                            <span class="font-semibold">Prioridad:</span>
                            <span>${promo.prioridad}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="p-4 bg-gray-900/50 border-t border-gray-700 flex justify-end gap-2">
                    <?php if ($puedeEditar): ?>
                    <button onclick="editarPromocion(${promo.id})" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                        <i class="bi bi-pencil"></i>
                        Editar
                    </button>
                    <?php endif; ?>
                    <?php if ($puedeEliminar): ?>
                    <button onclick="eliminarPromocion(${promo.id})" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                        <i class="bi bi-trash"></i>
                        Eliminar
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        `;
    }).join('');
}

function obtenerIconoTipo(tipo) {
    const iconos = {
        '2x1': 'bi bi-basket2-fill',
        '3x2': 'bi bi-basket3-fill',
        'descuento_porcentaje': 'bi bi-percent',
        'descuento_fijo': 'bi bi-cash-coin',
        'descuento_personal': 'bi bi-person-badge',
        'combo': 'bi bi-boxes'
    };
    return iconos[tipo] || 'bi bi-tag-fill';
}

function obtenerNombreTipo(tipo) {
    const nombres = {
        '2x1': '2x1',
        '3x2': '3x2',
        'descuento_porcentaje': 'Descuento %',
        'descuento_fijo': 'Descuento Fijo',
        'descuento_personal': 'Descuento Personal',
        'combo': 'Combo'
    };
    return nombres[tipo] || tipo;
}

function obtenerNombreAplicacion(aplica) {
    const nombres = {
        'productos': 'Productos Específicos',
        'categorias': 'Categorías',
        'todos': 'Todos los Productos'
    };
    return nombres[aplica] || aplica;
}

// Cargar productos
function cargarProductos() {
    fetch('api/promotionsController/productos.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'ok') {
                productos = data.data;
                productosFiltrados = productos;
            } else {
                console.error('Error al cargar productos:', data);
            }
        })
        .catch(err => console.error('Error al cargar productos:', err));
}

// Cargar categorías
function cargarCategorias() {
    fetch('api/promotionsController/categorias.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'ok') {
                categorias = data.data;
            } else {
                console.error('Error al cargar categorías:', data);
            }
        })
        .catch(err => console.error('Error al cargar categorías:', err));
}

// Filtrar promociones
function filtrarPromociones() {
    mostrarPromociones();
}

// Abrir modal crear
function abrirModalCrear() {
    document.getElementById('modalTitle').textContent = 'Nueva Promoción';
    document.getElementById('formPromocion').reset();
    document.getElementById('promo_id').value = '';
    document.getElementById('promo_activa').checked = true;
    document.getElementById('promo_mayor_valor').checked = true;
    actualizarCamposTipo();
    actualizarCamposAplicacion();
    mostrarProductosEnModal();
    mostrarCategoriasEnModal();
    document.getElementById('modalPromocion').classList.remove('hidden');
}

// Cerrar modal
function cerrarModal() {
    document.getElementById('modalPromocion').classList.add('hidden');
}

// Actualizar campos según tipo
function actualizarCamposTipo() {
    const tipo = document.getElementById('promo_tipo').value;
    const campoValor = document.getElementById('campoValor');
    const labelValor = document.getElementById('labelValor');
    
    if (tipo === 'descuento_porcentaje' || tipo === 'descuento_personal') {
        campoValor.classList.remove('hidden');
        labelValor.textContent = '(%)';
    } else if (tipo === 'descuento_fijo') {
        campoValor.classList.remove('hidden');
        labelValor.textContent = '(MXN)';
    } else {
        campoValor.classList.add('hidden');
    }
}

// Actualizar campos según aplicación
function actualizarCamposAplicacion() {
    const aplica = document.getElementById('promo_aplica_a').value;
    const campoProductos = document.getElementById('campoProductos');
    const campoCategorias = document.getElementById('campoCategorias');
    
    if (aplica === 'productos') {
        campoProductos.classList.remove('hidden');
        campoCategorias.classList.add('hidden');
        mostrarProductosEnModal();
    } else if (aplica === 'categorias') {
        campoProductos.classList.add('hidden');
        campoCategorias.classList.remove('hidden');
        mostrarCategoriasEnModal();
    } else {
        campoProductos.classList.add('hidden');
        campoCategorias.classList.add('hidden');
    }
}

// Mostrar productos en modal
function mostrarProductosEnModal(seleccionados = []) {
    const container = document.getElementById('productosSeleccionables');
    container.innerHTML = productosFiltrados.map(prod => {
        const isSelected = seleccionados.includes(prod.id);
        return `
            <label class="flex items-center gap-2 p-2 rounded-lg bg-gray-800/50 hover:bg-gray-700/50 cursor-pointer transition-colors">
                <input type="checkbox" name="productos[]" value="${prod.id}" ${isSelected ? 'checked' : ''}
                       class="w-4 h-4 text-yellow-500 bg-gray-700 border-gray-600 rounded focus:ring-yellow-500">
                <span class="text-sm text-gray-300">${prod.nombre}</span>
            </label>
        `;
    }).join('');
}

// Mostrar categorías en modal
function mostrarCategoriasEnModal(seleccionadas = []) {
    const container = document.getElementById('categoriasSeleccionables');
    container.innerHTML = categorias.map(cat => {
        const isSelected = seleccionadas.includes(cat);
        return `
            <label class="flex items-center gap-2 p-2 rounded-lg bg-gray-800/50 hover:bg-gray-700/50 cursor-pointer transition-colors">
                <input type="checkbox" name="categorias[]" value="${cat}" ${isSelected ? 'checked' : ''}
                       class="w-4 h-4 text-yellow-500 bg-gray-700 border-gray-600 rounded focus:ring-yellow-500">
                <span class="text-sm text-gray-300 capitalize">${cat}</span>
            </label>
        `;
    }).join('');
}

// Filtrar productos en modal
function filtrarProductosModal() {
    const search = document.getElementById('searchProductos').value.toLowerCase();
    productosFiltrados = productos.filter(p => p.nombre.toLowerCase().includes(search));
    
    // Obtener productos actualmente seleccionados
    const seleccionados = Array.from(document.querySelectorAll('input[name="productos[]"]:checked')).map(cb => parseInt(cb.value));
    mostrarProductosEnModal(seleccionados);
}

// Guardar promoción
function guardarPromocion(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const tipo = formData.get('tipo');
    const valorRaw = formData.get('valor');
    
    // Convertir valor vacío a null para evitar errores de decimal
    let valor = null;
    if (valorRaw && valorRaw !== '' && !isNaN(valorRaw)) {
        valor = parseFloat(valorRaw);
    }
    
    const data = {
        nombre: formData.get('nombre'),
        descripcion: formData.get('descripcion'),
        tipo: tipo,
        valor: valor,
        aplica_a: formData.get('aplica_a'),
        activa: document.getElementById('promo_activa').checked ? 1 : 0,
        fecha_inicio: formData.get('fecha_inicio') || null,
        fecha_fin: formData.get('fecha_fin') || null,
        prioridad: formData.get('prioridad'),
        aplicar_mayor_valor: document.getElementById('promo_mayor_valor').checked ? 1 : 0,
        minimo_productos: formData.get('minimo_productos'),
        productos: Array.from(document.querySelectorAll('input[name="productos[]"]:checked')).map(cb => parseInt(cb.value)),
        categorias: Array.from(document.querySelectorAll('input[name="categorias[]"]:checked')).map(cb => cb.value)
    };
    
    const id = document.getElementById('promo_id').value;
    if (id) {
        data.id = parseInt(id);
    }
    
    const action = id ? 'update' : 'create';
    
    fetch(`api/promotionsController/api.php?action=${action}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.status === 'ok') {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: resp.msg,
                background: '#1f2937',
                color: '#fff'
            });
            cerrarModal();
            cargarPromociones();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: resp.msg,
                background: '#1f2937',
                color: '#fff'
            });
        }
    })
    .catch(err => {
        console.error('Error:', err);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo guardar la promoción',
            background: '#1f2937',
            color: '#fff'
        });
    });
}

// Editar promoción
function editarPromocion(id) {
    fetch(`api/promotionsController/api.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'ok') {
                const promo = data.data;
                
                document.getElementById('modalTitle').textContent = 'Editar Promoción';
                document.getElementById('promo_id').value = promo.id;
                document.getElementById('promo_nombre').value = promo.nombre;
                document.getElementById('promo_descripcion').value = promo.descripcion || '';
                document.getElementById('promo_tipo').value = promo.tipo;
                document.getElementById('promo_valor').value = promo.valor || '';
                document.getElementById('promo_aplica_a').value = promo.aplica_a;
                document.getElementById('promo_prioridad').value = promo.prioridad;
                document.getElementById('promo_minimo_productos').value = promo.minimo_productos;
                document.getElementById('promo_activa').checked = promo.activa == 1;
                document.getElementById('promo_mayor_valor').checked = promo.aplicar_mayor_valor == 1;
                document.getElementById('promo_fecha_inicio').value = promo.fecha_inicio ? promo.fecha_inicio.slice(0, 16) : '';
                document.getElementById('promo_fecha_fin').value = promo.fecha_fin ? promo.fecha_fin.slice(0, 16) : '';
                
                actualizarCamposTipo();
                actualizarCamposAplicacion();
                
                // Asegurar que productos y categorías son arrays
                const productosArray = Array.isArray(promo.productos) ? promo.productos : [];
                const categoriasArray = Array.isArray(promo.categorias) ? promo.categorias : [];
                
                if (promo.aplica_a === 'productos') {
                    mostrarProductosEnModal(productosArray);
                } else if (promo.aplica_a === 'categorias') {
                    mostrarCategoriasEnModal(categoriasArray);
                }
                
                document.getElementById('modalPromocion').classList.remove('hidden');
            }
        })
        .catch(err => console.error('Error al cargar promoción:', err));
}

// Toggle promoción
function togglePromocion(id) {
    fetch('api/promotionsController/api.php?action=toggle', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}`
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.status === 'ok') {
            cargarPromociones();
        }
    })
    .catch(err => console.error('Error:', err));
}

// Eliminar promoción
function eliminarPromocion(id) {
    Swal.fire({
        title: '¿Eliminar promoción?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        background: '#1f2937',
        color: '#fff'
    }).then(result => {
        if (result.isConfirmed) {
            fetch('api/promotionsController/api.php?action=delete', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${id}`
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.status === 'ok') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: resp.msg,
                        background: '#1f2937',
                        color: '#fff'
                    });
                    cargarPromociones();
                }
            })
            .catch(err => console.error('Error:', err));
        }
    });
}
</script>