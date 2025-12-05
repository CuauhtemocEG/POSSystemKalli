<?php
require_once 'conexion.php';
$pdo = conexion();

$date_filter = $_GET['date_filter'] ?? '30days';
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

switch ($date_filter) {
  case '1day':
    $where[] = "o.creada_en >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
    break;
  case '7days':
    $where[] = "o.creada_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    break;
  case '30days':
    $where[] = "o.creada_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    break;
  case 'month':
    $where[] = "YEAR(o.creada_en) = YEAR(NOW()) AND MONTH(o.creada_en) = MONTH(NOW())";
    break;
  case 'year':
    $where[] = "YEAR(o.creada_en) = YEAR(NOW())";
    break;
}

if ($search !== '') {
  $where[] = "(o.codigo LIKE :search OR m.nombre LIKE :search OR o.estado LIKE :search)";
  $params[':search'] = "%$search%";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT o.*, m.nombre AS mesa_nombre
    FROM ordenes o
    JOIN mesas m ON m.id = o.mesa_id
    $where_sql
    ORDER BY o.creada_en DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Filters Section -->
<div class="mb-8 mt-8">
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 shadow-xl">
    <div class="flex items-center mb-6">
      <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mr-4">
        <i class="bi bi-funnel text-white"></i>
      </div>
      <h3 class="text-xl font-semibold text-white">Filtros de Búsqueda</h3>
    </div>
    
    <form id="filtros-form" class="flex flex-col lg:flex-row gap-4 items-end">
      <!-- Date Filter -->
      <div class="flex-1">
        <label class="block text-sm font-medium text-gray-300 mb-2">Período de Tiempo</label>
        <select name="date_filter"
                class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
          <option value="1day" <?= $date_filter === '1day' ? 'selected' : '' ?>>Último día</option>
          <option value="7days" <?= $date_filter === '7days' ? 'selected' : '' ?>>Últimos 7 días</option>
          <option value="30days" <?= $date_filter === '30days' ? 'selected' : '' ?>>Últimos 30 días</option>
          <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>Este mes</option>
          <option value="year" <?= $date_filter === 'year' ? 'selected' : '' ?>>Este año</option>
        </select>
      </div>
      
      <!-- Search Input -->
      <div class="flex-1">
        <label class="block text-sm font-medium text-gray-300 mb-2">Buscar Órdenes</label>
        <input type="text" 
               name="search" 
               value="<?= htmlspecialchars($search) ?>"
               autocomplete="off" 
               placeholder="Código, mesa o estado..."
               class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" />
      </div>
      
      <!-- Filter Button -->
      <div>
        <button type="submit" 
                class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
          <i class="bi bi-search mr-2"></i>
          Filtrar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Orders Table Container -->
<div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 shadow-xl overflow-hidden">
  <div class="p-6 border-b border-dark-600/50">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
          <i class="bi bi-list-ul text-white"></i>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-white">Lista de Órdenes</h3>
          <p class="text-gray-400 text-sm">Todas las órdenes registradas en el sistema</p>
        </div>
      </div>
      <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-400">Total:</span>
        <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-sm font-semibold">
          <?= count($ordenes) ?> órdenes
        </span>
      </div>
    </div>
  </div>
  
  <div id="tabla-ordenes" class="overflow-x-auto">
    <!-- Loading State -->
    <div class="p-12 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-2xl mb-4">
        <i class="bi bi-arrow-clockwise text-blue-400 text-2xl animate-spin"></i>
      </div>
      <p class="text-gray-400">Cargando órdenes...</p>
    </div>
  </div>
</div>

<script>
function cargarOrdenes(page = 1) {
  const form = document.getElementById('filtros-form');
  const data = new FormData(form);
  data.append('page', page);

  // Show loading state
  document.getElementById('tabla-ordenes').innerHTML = `
    <div class="p-12 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-2xl mb-4">
        <i class="bi bi-arrow-clockwise text-blue-400 text-2xl animate-spin"></i>
      </div>
      <p class="text-gray-400">Cargando órdenes...</p>
    </div>
  `;

  fetch('controllers/orders/ordenes_list.php', {
      method: 'POST',
      body: data
    })
    .then(resp => resp.text())
    .then(html => {
      document.getElementById('tabla-ordenes').innerHTML = html;
      
      // Add event listeners for pagination
      document.querySelectorAll('.paginacion-ordenes').forEach(el => {
        el.addEventListener('click', function(e) {
          e.preventDefault();
          const page = this.getAttribute('data-page');
          if (page) cargarOrdenes(page);
        });
      });
    })
    .catch(error => {
      console.error('Error:', error);
      document.getElementById('tabla-ordenes').innerHTML = `
        <div class="p-12 text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-500/20 to-pink-600/20 rounded-2xl mb-4">
            <i class="bi bi-exclamation-triangle text-red-400 text-2xl"></i>
          </div>
          <p class="text-red-400 mb-4">Error al cargar las órdenes</p>
          <button onclick="cargarOrdenes()" class="px-4 py-2 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-colors">
            <i class="bi bi-arrow-clockwise mr-2"></i>
            Reintentar
          </button>
        </div>
      `;
    });
}

// Form submission handler
document.getElementById('filtros-form').addEventListener('submit', function(e) {
  e.preventDefault();
  cargarOrdenes(1);
});

// Auto-submit when filters change
document.querySelector('select[name="date_filter"]').addEventListener('change', function() {
  cargarOrdenes(1);
});

// Search input with debounce
let searchTimeout;
document.querySelector('input[name="search"]').addEventListener('input', function() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    cargarOrdenes(1);
  }, 500);
});

// Load orders on page load
document.addEventListener('DOMContentLoaded', function() {
  cargarOrdenes(1);
});
</script>