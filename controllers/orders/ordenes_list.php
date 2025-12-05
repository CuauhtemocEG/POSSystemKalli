<?php
require_once '../../conexion.php';
$pdo = conexion();

$date_filter = $_POST['date_filter'] ?? '30days';
$search      = trim($_POST['search'] ?? '');
$page        = max(1, intval($_POST['page'] ?? 1));
$por_pagina  = 10;

$where = [];
$params = [];

switch ($date_filter) {
    case '1day':   $where[] = "o.creada_en >= DATE_SUB(NOW(), INTERVAL 1 DAY)"; break;
    case '7days':  $where[] = "o.creada_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; break;
    case '30days': $where[] = "o.creada_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)"; break;
    case 'month':  $where[] = "YEAR(o.creada_en) = YEAR(NOW()) AND MONTH(o.creada_en) = MONTH(NOW())"; break;
    case 'year':   $where[] = "YEAR(o.creada_en) = YEAR(NOW())"; break;
}

if ($search !== '') {
    $where[] = "(o.codigo LIKE :search OR m.nombre LIKE :search OR o.estado LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql_count = "
    SELECT COUNT(*) FROM ordenes o
    JOIN mesas m ON m.id = o.mesa_id
    $where_sql
";
$stmt = $pdo->prepare($sql_count);
$stmt->execute($params);
$total = $stmt->fetchColumn();

$total_paginas = max(1, ceil($total / $por_pagina));
$offset = ($page - 1) * $por_pagina;

$sql = "
    SELECT o.*, m.nombre AS mesa_nombre
    FROM ordenes o
    JOIN mesas m ON m.id = o.mesa_id
    $where_sql
    ORDER BY o.creada_en DESC
    LIMIT $por_pagina OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-3">
  <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
    <tr>
      <th class="px-4 py-3">Código</th>
      <th class="px-4 py-3">Mesa</th>
      <th class="px-4 py-3">Estado</th>
      <th class="px-4 py-3">Creada</th>
      <th class="px-4 py-3">Acción</th>
    </tr>
  </thead>
  <tbody>
    <?php if (count($ordenes) === 0): ?>
      <tr><td colspan="5" class="text-center py-6">No hay órdenes.</td></tr>
    <?php else: foreach ($ordenes as $orden): ?>
    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
      <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($orden['codigo']) ?></td>
      <td class="px-4 py-3"><?= htmlspecialchars($orden['mesa_nombre']) ?></td>
      <td class="px-4 py-3">
        <?php
            $estado = $orden['estado'];
            $badgeClass = match($estado) {
              'pagada' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
              'cerrada' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
              'cancelada' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
              'abierta' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
              default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
            };
        ?>
        <span class="px-2 py-1 text-xs font-semibold rounded <?= $badgeClass ?>">
          <?= ucfirst($estado) ?>
        </span>
      </td>
      <td class="px-4 py-3"><?= htmlspecialchars($orden['creada_en']) ?></td>
      <td class="px-4 py-3">
        <button 
           onclick="window.location.href='index.php?page=detalleOrder&id=<?= $orden['id'] ?>'"
           class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-medium text-center transition">
          Ver Detalle
        </button>
      </td>
    </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>

<?php if ($total_paginas > 1): ?>
<div class="flex justify-center items-center gap-1 py-4 text-sm">
  <?php
  $start = max(1, $page-2);
  $end = min($total_paginas, $page+2);
  if ($page > 1)
    echo '<a href="#" data-page="'.($page-1).'" class="paginacion-ordenes px-2 py-1 rounded hover:bg-blue-100 dark:hover:bg-blue-800">&laquo;</a>';
  for ($i = $start; $i <= $end; $i++) {
    $active = $i == $page ? 'bg-blue-600 text-white' : 'hover:bg-blue-100 dark:hover:bg-blue-800';
    echo '<a href="#" data-page="'.$i.'" class="paginacion-ordenes px-3 py-1 rounded '.$active.'">'.$i.'</a>';
  }
  if ($page < $total_paginas)
    echo '<a href="#" data-page="'.($page+1).'" class="paginacion-ordenes px-2 py-1 rounded hover:bg-blue-100 dark:hover:bg-blue-800">&raquo;</a>';
  ?>
</div>
<?php endif; ?>