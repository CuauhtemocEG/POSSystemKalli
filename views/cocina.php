<div class="view-wide pt-20">
<!-- Incluir sistema de notificaciones de sonido -->
<script src="js/notification-sound.js"></script>
<!-- Custom Styles for Enhanced Kitchen View -->
<style>
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
  
  @keyframes pulse-soft {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.7;
    }
  }
  
  .order-card {
    animation: slideInUp 0.4s ease-out;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .order-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  }
  
  .product-item {
    transition: all 0.3s ease;
  }
  
  .product-item:hover {
    background: rgba(99, 102, 241, 0.15);
    transform: translateX(2px);
    border-color: rgba(99, 102, 241, 0.5);
  }
  
  .status-badge {
    animation: pulse-soft 2s infinite;
  }
  
  .grid-horizontal {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .order-card-horizontal {
    width: 100%;
  }
  
  .products-horizontal {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
  }
  
  @media (min-width: 1280px) {
    .products-horizontal {
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    }
  }
  
  /* Animaciones para nuevas órdenes */
  @keyframes flash-bg {
    0%, 100% {
      background-color: transparent;
    }
    50% {
      background-color: rgba(239, 68, 68, 0.1);
    }
  }
  
  @keyframes slide-in-right {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  #nuevas-ordenes-alert-cocina {
    animation: slide-in-right 0.5s ease-out;
    cursor: pointer;
    transition: all 0.3s ease;
  }
  
  #nuevas-ordenes-alert-cocina:hover {
    background: rgba(239, 68, 68, 0.3);
    transform: scale(1.05);
  }
  
  /* Badge pulsante más visible */
  @keyframes pulse-scale {
    0%, 100% {
      transform: scale(1);
    }
    50% {
      transform: scale(1.2);
    }
  }
  
  #nuevas-ordenes-badge-cocina {
    animation: pulse-scale 1s infinite;
  }
</style>

<!-- Kitchen Status Cards - Compact Version -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-gradient-to-br from-orange-500/10 to-orange-600/5 backdrop-blur-sm rounded-xl border border-orange-500/20 p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-orange-400 text-xs font-medium mb-1">Pendientes</p>
        <h3 class="text-2xl font-bold text-white" id="ordenes-pendientes">0</h3>
      </div>
      <div class="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center">
        <i class="bi bi-clock text-orange-400"></i>
      </div>
    </div>
  </div>
  
  <div class="bg-gradient-to-br from-yellow-500/10 to-yellow-600/5 backdrop-blur-sm rounded-xl border border-yellow-500/20 p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-yellow-400 text-xs font-medium mb-1">Preparando</p>
        <h3 class="text-2xl font-bold text-white" id="ordenes-preparando">0</h3>
      </div>
      <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center">
        <i class="bi bi-hourglass-split text-yellow-400"></i>
      </div>
    </div>
  </div>
  
  <div class="bg-gradient-to-br from-green-500/10 to-green-600/5 backdrop-blur-sm rounded-xl border border-green-500/20 p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-green-400 text-xs font-medium mb-1">Listas</p>
        <h3 class="text-2xl font-bold text-white" id="ordenes-listas">0</h3>
      </div>
      <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
        <i class="bi bi-check-circle text-green-400"></i>
      </div>
    </div>
  </div>
  
  <div class="bg-gradient-to-br from-purple-500/10 to-purple-600/5 backdrop-blur-sm rounded-xl border border-purple-500/20 p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-purple-400 text-xs font-medium mb-1">Mesas</p>
        <h3 class="text-2xl font-bold text-white" id="mesas-activas">0</h3>
      </div>
      <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
        <i class="bi bi-grid-3x3 text-purple-400"></i>
      </div>
    </div>
  </div>
</div>

<!-- Auto Refresh Control - Compact with NEW Indicator -->
<div class="mb-6">
  <div class="bg-dark-700/20 backdrop-blur-sm rounded-xl border border-dark-600/30 p-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center relative">
          <i class="bi bi-arrow-clockwise text-white text-sm"></i>
          <span id="nuevas-ordenes-badge-cocina" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center animate-bounce">0</span>
        </div>
        <div>
          <h3 class="text-sm font-semibold text-white">Auto-actualización</h3>
          <p class="text-gray-400 text-xs">Cada <span id="refresh-interval-display-cocina">10</span> segundos</p>
        </div>
      </div>
      <div class="flex items-center space-x-3">
        <div id="nuevas-ordenes-alert-cocina" class="hidden items-center space-x-2 bg-red-500/20 border border-red-500/50 px-3 py-1 rounded-lg animate-pulse">
          <i class="bi bi-bell-fill text-red-400"></i>
          <span class="text-red-400 text-xs font-bold">¡<span id="contador-nuevas-cocina">0</span> NUEVA(S) ORDEN(ES)!</span>
        </div>
        <div class="flex items-center space-x-2">
          <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
          <span class="text-green-400 text-xs font-medium">En línea</span>
        </div>
        <button onclick="cargarCocina(true)" 
                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all duration-200 transform hover:scale-105 text-sm shadow-lg">
          <i class="bi bi-arrow-clockwise mr-1"></i>
          Actualizar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Kitchen Orders Content -->
<div id="cocina-content">
  <!-- Loading State -->
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-12 text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-orange-500/20 to-red-600/20 rounded-2xl mb-4">
      <i class="bi bi-arrow-clockwise text-orange-400 text-2xl animate-spin"></i>
    </div>
    <h3 class="text-xl font-semibold text-white mb-2">Cargando Vista de Cocina</h3>
    <p class="text-gray-400">Obteniendo órdenes pendientes...</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let refreshInterval;
let isUpdating = false;
let mesasConocidas = new Set();
let ultimaActualizacion = Date.now();
let refreshIntervalTime = 10000; // 10 segundos para tiempo real

function cargarCocina(showLoading = true) {
  // Evitar múltiples actualizaciones simultáneas
  if (isUpdating) return;
  isUpdating = true;

  // Solo mostrar loading en la primera carga o actualización manual
  if (showLoading) {
    document.getElementById('cocina-content').innerHTML = `
      <div class="flex items-center justify-center py-20">
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-orange-500/20 to-red-600/20 rounded-2xl mb-4">
            <i class="bi bi-arrow-clockwise text-orange-400 text-2xl animate-spin"></i>
          </div>
          <p class="text-gray-400">Cargando órdenes...</p>
        </div>
      </div>
    `;
  }

  fetch('controllers/cocina_ajax.php?_=' + Date.now()) // Anti-cache timestamp
    .then(res => res.json())
    .then(data => {
      // Separar productos normales y pendientes de cancelación
      let productosNormales = data.filter(item => item.tipo === 'normal');
      let productosPendientes = data.filter(item => item.tipo === 'pendiente_cancelacion');
      
      // Agrupar por mesa
      let mesas = {};
      let stats = {
        pendientes: 0,
        preparando: 0,
        listas: 0,
        mesasActivas: 0
      };

      productosNormales.forEach(item => {
        if (!mesas[item.mesa]) {
          mesas[item.mesa] = {
            nombre: item.mesa,
            productos: [],
            op_id: item.op_id,
            prioridad: item.op_id, // Menor op_id = mayor prioridad (llegó primero)
            tiempo_orden: item.op_id // Para referencia
          };
          stats.mesasActivas++;
        }
        mesas[item.mesa].productos.push(item);
        
        // Calculate stats
        stats.pendientes += parseInt(item.faltan);
        stats.preparando += parseInt(item.cantidad) - parseInt(item.preparado) - parseInt(item.cancelado) - parseInt(item.faltan);
        stats.listas += parseInt(item.preparado);
      });

      // Update stats
      document.getElementById('ordenes-pendientes').textContent = stats.pendientes;
      document.getElementById('ordenes-preparando').textContent = stats.preparando;
      document.getElementById('ordenes-listas').textContent = stats.listas;
      document.getElementById('mesas-activas').textContent = stats.mesasActivas;

      // Render orders
      let html = '';
      
      if (Object.keys(mesas).length === 0) {
        html = `
          <div class="flex items-center justify-center py-20">
            <div class="text-center">
              <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-green-500/20 to-emerald-600/20 rounded-3xl mb-4">
                <i class="bi bi-check-circle text-green-400 text-3xl"></i>
              </div>
              <h3 class="text-xl font-semibold text-white mb-2">¡Todo al día!</h3>
              <p class="text-gray-400">No hay órdenes pendientes en cocina</p>
            </div>
          </div>
        `;
      } else {
        html = '<div class="grid-horizontal">';
        
        // Ordenar mesas por prioridad (op_id más bajo primero = llegó primero)
        const mesasOrdenadas = Object.entries(mesas).sort((a, b) => {
          return a[1].prioridad - b[1].prioridad;
        });
        
        // Calcular índice de urgencia para cada mesa
        const totalMesas = mesasOrdenadas.length;
        
        mesasOrdenadas.forEach(([nombreMesa, mesa], index) => {
          // Determinar nivel de prioridad visual
          let prioridadBadge = '';
          let prioridadColor = '';
          
          if (index < totalMesas * 0.3) { // Top 30% - Alta prioridad
            prioridadBadge = `
              <div class="absolute top-2 right-2 z-10">
                <span class="inline-flex items-center px-2 py-1 bg-red-500/90 text-white rounded-lg text-xs font-bold shadow-lg animate-pulse">
                  <i class="bi bi-exclamation-triangle-fill mr-1"></i>URGENTE
                </span>
              </div>`;
            prioridadColor = 'border-red-500/50 shadow-red-500/20';
          } else if (index < totalMesas * 0.6) { // 30-60% - Prioridad media
            prioridadBadge = `
              <div class="absolute top-2 right-2 z-10">
                <span class="inline-flex items-center px-2 py-1 bg-orange-500/80 text-white rounded-lg text-xs font-semibold">
                  <i class="bi bi-clock-fill mr-1"></i>Atender pronto
                </span>
              </div>`;
            prioridadColor = 'border-orange-500/30';
          }
          
          // Calculate mesa status
          let totalFaltan = 0;
          let totalPreparado = 0;
          let totalProductos = 0;
          
          mesa.productos.forEach(item => {
            totalFaltan += parseInt(item.faltan);
            totalPreparado += parseInt(item.preparado);
            totalProductos += parseInt(item.cantidad) - parseInt(item.cancelado);
          });
          
          let statusColor = 'orange';
          let statusText = 'Pendiente';
          let statusIcon = 'clock';
          
          if (totalPreparado === totalProductos && totalProductos > 0) {
            statusColor = 'green';
            statusText = 'Completa';
            statusIcon = 'check-circle-fill';
          } else if (totalPreparado > 0) {
            statusColor = 'yellow';
            statusText = 'En Preparación';
            statusIcon = 'hourglass-split';
          }
          
          html += `
            <div class="order-card-horizontal">
              <div class="order-card bg-gradient-to-br from-dark-700/60 to-dark-800/60 backdrop-blur-xl rounded-2xl border ${prioridadColor || 'border-dark-600/50'} overflow-hidden shadow-2xl relative">
                ${prioridadBadge}
                
                <!-- Header compacto -->
                <div class="bg-gradient-to-r from-${statusColor}-500/20 to-${statusColor}-600/10 px-6 py-4 border-b border-dark-600/50">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                      <div class="w-14 h-14 bg-gradient-to-br from-${statusColor}-500 to-${statusColor}-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="bi bi-table text-white text-xl"></i>
                      </div>
                      <div>
                        <h3 class="text-xl font-bold text-white">${nombreMesa}</h3>
                        <p class="text-xs text-gray-400">Orden #${mesa.op_id}</p>
                      </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                      <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-300 font-medium">
                          <i class="bi bi-egg-fried mr-1.5"></i>${totalProductos} productos
                        </span>
                        ${totalPreparado > 0 ? `<span class="text-sm text-green-400 font-semibold"><i class="bi bi-check-lg mr-1.5"></i>${totalPreparado} listos</span>` : ''}
                        ${totalFaltan > 0 ? `<span class="text-sm text-orange-400 font-semibold animate-pulse"><i class="bi bi-clock mr-1.5"></i>${totalFaltan} pendientes</span>` : ''}
                      </div>
                      
                      <div class="status-badge px-4 py-2 bg-${statusColor}-500/20 border border-${statusColor}-500/30 rounded-full">
                        <div class="flex items-center space-x-2">
                          <i class="bi bi-${statusIcon} text-${statusColor}-400"></i>
                          <span class="text-${statusColor}-400 text-sm font-semibold">${statusText}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Products Grid Horizontal -->
                <div class="p-5 products-horizontal">
          `;
          
          mesa.productos.forEach(item => {
            const faltan = parseInt(item.faltan);
            const preparado = parseInt(item.preparado);
            const cantidad = parseInt(item.cantidad);
            const cancelado = parseInt(item.cancelado);
            
            html += `
              <div class="product-item bg-gradient-to-br from-dark-600/50 to-dark-700/30 rounded-xl p-4 border border-dark-500/40 hover:border-indigo-500/40 transition-all duration-200">
                <div class="flex items-start justify-between mb-3">
                  <div class="flex items-start space-x-3 flex-1">
                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-500/30 to-purple-600/30 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg">
                      <i class="bi bi-egg-fried text-indigo-400 text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-white font-semibold text-base leading-tight mb-2">${item.producto}</p>
                      
                      ${item.variedades && item.variedades.length > 0 ? `
                        <div class="mt-2 mb-2 pl-3 border-l-2 border-orange-500/50 bg-orange-500/5 rounded-r py-2 pr-2">
                          <p class="text-xs text-orange-400 font-bold mb-1.5 uppercase tracking-wide flex items-center">
                            <i class="bi bi-list-ul mr-1"></i>Especificaciones:
                          </p>
                          ${item.variedades.map(v => `
                            <div class="text-sm text-orange-200 mb-1 flex items-start">
                              <i class="bi bi-chevron-right text-orange-400 mr-1 mt-0.5"></i>
                              <span><span class="font-semibold text-orange-300">${v.grupo_nombre}:</span> ${v.opcion_nombre}</span>
                            </div>
                          `).join('')}
                        </div>
                      ` : ''}
                      
                      ${item.nota_adicional && item.nota_adicional.trim() !== '' ? `
                        <div class="mt-2 mb-2 pl-3 border-l-3 border-yellow-500/70 bg-yellow-500/10 rounded-r-lg pr-3 py-2.5">
                          <p class="text-xs text-yellow-400 font-bold mb-1.5 uppercase tracking-wide flex items-center">
                            <i class="bi bi-sticky-fill mr-1.5"></i>Nota especial:
                          </p>
                          <p class="text-sm text-yellow-100 italic font-medium leading-relaxed">"${item.nota_adicional}"</p>
                        </div>
                      ` : ''}
                      
                      <div class="flex items-center flex-wrap gap-2 mt-3">
                        <span class="inline-flex items-center px-3 py-1.5 bg-blue-500/20 border border-blue-500/30 text-blue-300 rounded-lg text-sm font-bold shadow-sm">
                          <i class="bi bi-cart-fill mr-1.5"></i>Total: ${cantidad}
                        </span>
                        ${preparado > 0 ? `
                          <span class="inline-flex items-center px-3 py-1.5 bg-green-500/20 border border-green-500/30 text-green-300 rounded-lg text-sm font-bold shadow-sm">
                            <i class="bi bi-check-circle-fill mr-1.5"></i>Listos: ${preparado}
                          </span>
                        ` : ''}
                        ${cancelado > 0 ? `
                          <span class="inline-flex items-center px-3 py-1.5 bg-red-500/20 border border-red-500/30 text-red-300 rounded-lg text-sm font-bold shadow-sm">
                            <i class="bi bi-x-circle-fill mr-1.5"></i>Cancelados: ${cancelado}
                          </span>
                        ` : ''}
                        ${faltan > 0 ? `
                          <span class="inline-flex items-center px-3 py-1.5 bg-orange-500/30 border border-orange-500/50 text-orange-200 rounded-lg text-sm font-bold shadow-sm animate-pulse">
                            <i class="bi bi-clock-fill mr-1.5"></i>Pendientes: ${faltan}
                          </span>
                        ` : ''}
                      </div>
                    </div>
                  </div>
                </div>
                
                ${faltan > 0 ? `
                  <form class="marcar-preparado-form-cocina mt-3 pt-3 border-t border-dark-500/40" data-op="${item.op_id}">
                    <div class="flex items-center space-x-3">
                      <div class="flex items-center space-x-2">
                        <label class="text-xs text-gray-400 font-semibold">Cantidad:</label>
                        <input type="number" 
                               name="marcar" 
                               value="${Math.min(faltan, 1)}" 
                               min="1" 
                               max="${faltan}" 
                               class="w-20 px-3 py-2 bg-dark-700/70 border border-dark-500/50 rounded-lg text-white text-base font-semibold text-center focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                      </div>
                      <button type="submit" 
                              class="flex-1 px-4 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold rounded-lg transition-all duration-200 transform hover:scale-105 text-sm shadow-lg hover:shadow-xl">
                        <i class="bi bi-check2-circle mr-2"></i>
                        Marcar como Listo
                      </button>
                    </div>
                  </form>
                ` : `
                  <div class="mt-3 pt-3 border-t border-dark-500/40 p-3 bg-gradient-to-r from-green-500/10 to-emerald-600/10 rounded-lg border border-green-500/30 text-center">
                    <span class="text-green-400 text-sm font-bold flex items-center justify-center">
                      <i class="bi bi-check-circle-fill mr-2 text-lg"></i>
                      ✓ Producto Completado
                    </span>
                  </div>
                `}
              </div>
            `;
          });
          
          html += `
                </div>
              </div>
            </div>
          `;
        }); // Cierre del forEach de mesasOrdenadas
        
        html += '</div>';
      }

      document.getElementById('cocina-content').innerHTML = html;

      // Detectar nuevas órdenes y mostrar notificación visual
      const mesasActuales = new Set(Object.keys(mesas));
      let nuevasMesas = 0;
      
      if (mesasConocidas.size > 0) {
        // Detectar mesas nuevas
        mesasActuales.forEach(mesa => {
          if (!mesasConocidas.has(mesa)) {
            nuevasMesas++;
          }
        });
        
        // Si hay mesas nuevas, mostrar alerta visual
        if (nuevasMesas > 0) {
          mostrarAlertaNuevasOrdenesCocina(nuevasMesas);
        }
      }
      
      // Actualizar mesas conocidas
      mesasConocidas = mesasActuales;

      // Verificar y notificar nuevos productos con sonido
      if (window.notificationSound) {
        const productosParaNotificar = [];
        for (const nombreMesa in mesas) {
          const mesa = mesas[nombreMesa];
          mesa.productos.forEach(producto => {
            productosParaNotificar.push({
              op_id: producto.op_id,
              mesa_id: mesa.nombre
            });
          });
        }
        window.notificationSound.checkAndNotify(productosParaNotificar);
      }

      // Add event listeners
      document.querySelectorAll('.marcar-preparado-form-cocina').forEach(form => {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          const op_id = this.getAttribute('data-op');
          const marcar = this.querySelector('input[name="marcar"]').value;
          const submitBtn = this.querySelector('button[type="submit"]');
          const originalContent = submitBtn.innerHTML;
          
          // Deshabilitar botón y mostrar loading
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="bi bi-arrow-clockwise animate-spin mr-1"></i>Marcando...';
          
          fetch('controllers/marcar_preparado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `op_id=${op_id}&marcar=${marcar}`
          })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'ok') {
              // Toast sutil sin bloquear la UI
              const toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: (toast) => {
                  toast.addEventListener('mouseenter', Swal.stopTimer);
                  toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
              });
              
              toast.fire({
                icon: 'success',
                title: 'Producto marcado como listo',
                background: '#1f2937',
                color: '#ffffff'
              });
              
              // Forzar actualización inmediata sin loading
              isUpdating = false; // Reset flag antes de actualizar
              
              // Dar tiempo para que la BD confirme el commit
              setTimeout(() => {
                console.log('🔄 Actualizando vista de cocina...');
                // Agregar timestamp para evitar caché
                fetch('controllers/cocina_ajax.php?_=' + Date.now())
                  .then(res => res.json())
                  .then(data => {
                    console.log('✅ Datos actualizados:', data.length, 'items');
                    // Llamar a cargarCocina con los datos frescos
                    cargarCocina(false);
                  })
                  .catch(err => {
                    console.error('❌ Error al actualizar:', err);
                    cargarCocina(false);
                  });
              }, 300);
            } else {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalContent;
              Swal.fire({
                title: 'Error',
                text: data.msg || 'No se pudo marcar',
                icon: 'error',
                background: '#1f2937',
                color: '#ffffff',
                confirmButtonColor: '#ef4444'
              });
            }
          })
          .catch(err => {
            console.error('Error:', err);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
            Swal.fire({
              title: 'Error',
              text: 'Error de conexión',
              icon: 'error',
              background: '#1f2937',
              color: '#ffffff',
              confirmButtonColor: '#ef4444'
            });
          });
        });
      });

      // Show cancellation alerts if any
      if (productosPendientes.length > 0) {
        mostrarAlertaCancelaciones(productosPendientes);
      }

      // Marcar actualización como completada
      isUpdating = false;
    })
    .catch(error => {
      console.error('Error:', error);
      isUpdating = false;
      document.getElementById('cocina-content').innerHTML = `
        <div class="flex items-center justify-center py-20">
          <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-500/20 to-pink-600/20 rounded-2xl mb-4">
              <i class="bi bi-exclamation-triangle text-red-400 text-2xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-white mb-2">Error al cargar datos</h3>
            <p class="text-gray-400 mb-4">No se pudieron obtener las órdenes de cocina</p>
            <button onclick="cargarCocina()" class="px-4 py-2 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-colors">
              <i class="bi bi-arrow-clockwise mr-2"></i>
              Reintentar
            </button>
          </div>
        </div>
      `;
    });
}

function mostrarAlertaCancelaciones(productos) {
  if (productos.length === 0) return;
  
  let html = '<div class="text-left space-y-2 max-h-96 overflow-y-auto">';
  html += '<p class="text-sm text-slate-300 mb-3">Productos con solicitud de cancelación:</p>';
  
  productos.forEach(item => {
    html += `
      <div class="bg-red-900/20 border border-red-500/30 rounded-lg p-3 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-2">
            <i class="bi bi-exclamation-triangle text-red-400"></i>
            <span class="text-white font-medium text-sm">${item.producto}</span>
          </div>
          <p class="text-xs text-slate-400 ml-6 mt-1">Mesa: ${item.mesa} | Cantidad: ${item.cantidad}</p>
        </div>
        <span class="text-red-400 text-xs font-bold">PENDIENTE</span>
      </div>
    `;
  });
  
  html += '</div>';

  Swal.fire({
    title: '⚠️ Cancelaciones Pendientes',
    html: html,
    icon: 'warning',
    confirmButtonText: 'Entendido',
    confirmButtonColor: '#dc2626',
    width: '500px',
    customClass: {
      container: 'swal-cancelaciones-cocina'
    }
  });
}

// Función para mostrar alerta visual de nuevas órdenes
function mostrarAlertaNuevasOrdenesCocina(cantidad) {
  const badge = document.getElementById('nuevas-ordenes-badge-cocina');
  const alert = document.getElementById('nuevas-ordenes-alert-cocina');
  const contador = document.getElementById('contador-nuevas-cocina');
  
  // Actualizar contador
  contador.textContent = cantidad;
  badge.textContent = cantidad;
  
  // Mostrar elementos
  badge.classList.remove('hidden');
  alert.classList.remove('hidden');
  alert.classList.add('flex');
  
  // Flash de fondo para llamar la atención
  document.body.style.animation = 'flash-bg 0.5s ease-in-out 3';
  
  // Ocultar después de 30 segundos o al hacer click
  setTimeout(() => {
    ocultarAlertaNuevasOrdenesCocina();
  }, 30000);
  
  // Permitir ocultar al hacer click en la alerta
  alert.onclick = function() {
    ocultarAlertaNuevasOrdenesCocina();
  };
}

function ocultarAlertaNuevasOrdenesCocina() {
  const badge = document.getElementById('nuevas-ordenes-badge-cocina');
  const alert = document.getElementById('nuevas-ordenes-alert-cocina');
  
  badge.classList.add('hidden');
  alert.classList.add('hidden');
  alert.classList.remove('flex');
  document.body.style.animation = '';
}

// Auto refresh cada 10 segundos para tiempo real
function startAutoRefresh() {
  refreshInterval = setInterval(() => {
    cargarCocina(false); // Actualización automática sin loading
  }, refreshIntervalTime);
}

function stopAutoRefresh() {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
}

// Load data when page loads
document.addEventListener('DOMContentLoaded', function() {
  cargarCocina();
  startAutoRefresh();
});

// Stop refresh when page is hidden
document.addEventListener('visibilitychange', function() {
  if (document.hidden) {
    stopAutoRefresh();
  } else {
    startAutoRefresh();
  }
});
</script>
</div>
