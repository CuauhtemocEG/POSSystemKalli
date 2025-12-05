<?php
// Este archivo es incluido desde index.php, por lo que las rutas son relativas al directorio raíz
// $pdo y $userInfo ya están disponibles desde index.php

$orden_id = intval($_GET['id'] ?? 0);
$orden = $pdo->prepare("
    SELECT o.*, m.nombre AS mesa_nombre
    FROM ordenes o
    JOIN mesas m ON m.id = o.mesa_id
    WHERE o.id = ?
");
$orden->execute([$orden_id]);
$orden = $orden->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
  echo "<div class='bg-red-500/10 border border-red-500/20 text-red-400 px-6 py-4 rounded-xl mb-6'>
          <i class='bi bi-exclamation-triangle mr-2'></i>
          Orden no encontrada
        </div>";
  exit;
}

// Productos - Incluir item_index y variedades para auditoría detallada
$productos = $pdo->prepare("
    SELECT op.id, 
           p.nombre, 
           op.cantidad, 
           op.preparado, 
           COALESCE(op.cancelado, 0) as cancelado,
           COALESCE(op.pendiente_cancelacion, 0) as pendiente_cancelacion,
           COALESCE(op.item_index, 1) as item_index,
           op.producto_id,
           p.precio
    FROM orden_productos op
    JOIN productos p ON op.producto_id = p.id
    WHERE op.orden_id = ? AND op.estado != 'eliminado'
    ORDER BY p.nombre, op.item_index
");
$productos->execute([$orden_id]);
$productos_raw = $productos->fetchAll(PDO::FETCH_ASSOC);

// Obtener variedades para cada producto
$productos = [];
foreach ($productos_raw as $prod) {
  // Obtener variedades de este item específico
  $stmtVariedades = $pdo->prepare("
    SELECT grupo_nombre, opcion_nombre, precio_adicional
    FROM orden_producto_variedades
    WHERE orden_id = ? AND producto_id = ? AND item_index = ?
    ORDER BY id
  ");
  $stmtVariedades->execute([$orden_id, $prod['producto_id'], $prod['item_index']]);
  $variedades = $stmtVariedades->fetchAll(PDO::FETCH_ASSOC);

  // Generar una clave única por producto, item_index y variedades
  $variedades_key = '';
  if (!empty($variedades)) {
    foreach ($variedades as $v) {
      $variedades_key .= $v['grupo_nombre'] . ':' . $v['opcion_nombre'] . ';';
    }
  }
  $key = $prod['producto_id'] . '|' . $prod['item_index'] . '|' . md5($variedades_key);

  if (!isset($productos[$key])) {
    $prod['variedades'] = $variedades;
    $productos[$key] = $prod;
  } else {
    // Sumar cantidades si ya existe (caso raro, pero por consistencia)
    $productos[$key]['cantidad'] += $prod['cantidad'];
    $productos[$key]['preparado'] += $prod['preparado'];
    $productos[$key]['cancelado'] += $prod['cancelado'];
    $productos[$key]['pendiente_cancelacion'] += $prod['pendiente_cancelacion'];
  }
}
$productos = array_values($productos);

$subtotal = 0;
$total_cancelado = 0;
$productos_activos = 0;
$productos_cancelados = 0;

foreach ($productos as $prod) {
    $cantidad = intval($prod['cantidad']);
    $cancelado = intval($prod['cancelado']);
    $pendiente_cancelacion = intval($prod['pendiente_cancelacion']);
    $precio = floatval($prod['precio']);
    
    // Calcular cantidad activa (no cancelada ni pendiente de cancelación)
    $cantidad_activa = $cantidad - $cancelado - $pendiente_cancelacion;
    
    // Subtotal solo de productos activos
    $subtotal += $precio * $cantidad_activa;
    $productos_activos += $cantidad_activa;
    
    // Total cancelado (productos ya cancelados)
    $total_cancelado += $precio * $cancelado;
    $productos_cancelados += $cancelado;
}


$descuento = 0;
$total = $subtotal - $descuento;

// Actualizar el total en la base de datos si es diferente al calculado
if (isset($orden['total']) && abs($orden['total'] - $total) > 0.01) {
    $update_total = $pdo->prepare("UPDATE ordenes SET total = ? WHERE id = ?");
    $update_total->execute([$total, $orden_id]);
    
    // Actualizar el array orden para mostrar el total correcto
    $orden['total'] = $total;
}
?>

<!-- Action Bar -->
<div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-8 mt-4">
  <a href="index.php?page=ordenes" 
     class="flex items-center space-x-2 px-6 py-3 bg-dark-600 hover:bg-dark-500 text-gray-300 rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
    <i class="bi bi-arrow-left"></i>
    <span>Volver al Listado</span>
  </a>
  
  <a href="controllers/exportar_order_pdf.php?id=<?= $orden['id'] ?>" 
     target="_blank"
     class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
    <i class="bi bi-file-pdf"></i>
    <span>Exportar PDF</span>
  </a>
</div>

<!-- Order Information Card -->
<div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl p-6 mb-8">
  <h2 class="text-xl font-montserrat-semibold text-white mb-6 flex items-center">
    <i class="bi bi-info-circle mr-2 text-blue-400"></i>
    Información de la Orden
  </h2>
  
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Código</label>
      <p class="text-lg font-semibold text-white"><?= htmlspecialchars($orden['codigo']) ?></p>
    </div>
    

    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Mesa</label>
      <p class="text-lg font-semibold text-white"><?= htmlspecialchars($orden['mesa_nombre']) ?></p>
    </div>

    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Mesero</label>
      <p class="text-lg font-semibold text-blue-300">
        <?php
        // Mostrar el nombre del mesero si existe
        if (!empty($orden['usuario_id'])) {
          $stmtMesero = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
          $stmtMesero->execute([$orden['usuario_id']]);
          $mesero = $stmtMesero->fetchColumn();
          echo $mesero ? htmlspecialchars($mesero) : '<span class=\'text-gray-400\'>Sin asignar</span>';
        } else {
          echo '<span class=\'text-gray-400\'>Sin asignar</span>';
        }
        ?>
      </p>
    </div>
    
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Estado</label>
      <div>
        <?php
        $estado = $orden['estado'];
        $badgeClass = match($estado) {
          'pagada' => 'bg-green-500/20 text-green-400 border-green-500/30',
          'cerrada' => 'bg-green-500/20 text-green-400 border-green-500/30',
          'cancelada' => 'bg-red-500/20 text-red-400 border-red-500/30',
          'abierta' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
          default => 'bg-blue-500/20 text-blue-400 border-blue-500/30'
        };
        ?>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border <?= $badgeClass ?>">
          <i class="bi bi-circle-fill mr-2 text-xs"></i>
          <?= ucfirst($estado) ?>
        </span>
      </div>
    </div>
    
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Total</label>
      <p class="text-lg font-semibold text-green-400">$<?= number_format($total, 2) ?></p>
      <?php if ($productos_cancelados > 0): ?>
        <p class="text-xs text-red-400">
          <i class="bi bi-exclamation-triangle mr-1"></i>
          <?= $productos_cancelados ?> producto(s) cancelado(s)
        </p>
      <?php endif; ?>
    </div>
    
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Fecha de Creación</label>
      <p class="text-lg font-semibold text-white"><?= date('d/m/Y H:i', strtotime($orden['creada_en'])) ?></p>
    </div>
  </div>
</div>

<!-- Products Table -->
<div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden mb-8">
  <div class="p-6 border-b border-dark-700/50">
    <h2 class="text-xl font-montserrat-semibold text-white flex items-center">
      <i class="bi bi-bag mr-2 text-purple-400"></i>
      Productos de la Orden
    </h2>
  </div>
  
  <div class="overflow-x-auto">
    <table class="w-full">
      <thead class="bg-dark-700/50">
        <tr>
          <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Producto</th>
          <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Cantidad</th>
          <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Preparado</th>
          <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Cancelado</th>
          <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Pendiente Cancel.</th>
          <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Precio Unit.</th>
          <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Subtotal</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-dark-700/50">
        <?php foreach ($productos as $prod): ?>
          <?php 
          $cantidad = intval($prod['cantidad']);
          $cancelado = intval($prod['cancelado']);
          $pendiente_cancelacion = intval($prod['pendiente_cancelacion']);
          $cantidad_activa = $cantidad - $cancelado - $pendiente_cancelacion;
          
          $hasCancelados = $cancelado > 0;
          $hasPendientes = $pendiente_cancelacion > 0;
          $rowClass = ($hasCancelados || $hasPendientes) ? 'hover:bg-orange-900/10 transition-colors duration-200' : 'hover:bg-dark-700/30 transition-colors duration-200';
          $textClass = $cantidad_activa > 0 ? 'text-white' : 'text-red-300';
          ?>
          <tr class="<?= $rowClass ?>">
            <td class="px-6 py-4">
              <div class="text-sm font-medium <?= $textClass ?> flex items-center">
                <?= htmlspecialchars($prod['nombre']) ?>
                <?php if ($hasCancelados): ?>
                  <span class="ml-2 text-xs bg-red-600 text-white px-2 py-1 rounded-full">CANCELADOS</span>
                <?php endif; ?>
                <?php if ($hasPendientes): ?>
                  <span class="ml-2 text-xs bg-orange-500 text-white px-2 py-1 rounded-full">PENDIENTES</span>
                <?php endif; ?>
              </div>
              
              <?php if (!empty($prod['variedades'])): ?>
                <div class="mt-2 pl-3 border-l-2 border-orange-500/50">
                  <?php foreach ($prod['variedades'] as $variedad): ?>
                    <div class="text-xs text-orange-300 flex items-center gap-1 mt-1">
                      <i class="bi bi-arrow-return-right text-orange-400"></i>
                      <span class="font-semibold"><?= htmlspecialchars($variedad['grupo_nombre']) ?>:</span>
                      <span><?= htmlspecialchars($variedad['opcion_nombre']) ?></span>
                      <?php if ($variedad['precio_adicional'] > 0): ?>
                        <span class="text-green-400 font-semibold">(+$<?= number_format($variedad['precio_adicional'], 2) ?>)</span>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </td>
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-500/20 text-blue-400 rounded-full text-sm font-semibold">
                <?= $cantidad ?>
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center justify-center w-8 h-8 bg-green-500/20 text-green-400 rounded-full text-sm font-semibold">
                <?= intval($prod['preparado']) ?>
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center justify-center w-8 h-8 <?= $cancelado > 0 ? 'bg-red-500/20 text-red-400' : 'bg-gray-500/20 text-gray-400' ?> rounded-full text-sm font-semibold">
                <?= $cancelado ?>
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center justify-center w-8 h-8 <?= $pendiente_cancelacion > 0 ? 'bg-orange-500/20 text-orange-400' : 'bg-gray-500/20 text-gray-400' ?> rounded-full text-sm font-semibold">
                <?= $pendiente_cancelacion ?>
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm font-medium text-gray-300">
              $<?= number_format($prod['precio'], 2) ?>
            </td>
            <td class="px-6 py-4 text-right text-sm font-bold">
              <div class="text-white">$<?= number_format($prod['precio'] * $cantidad_activa, 2) ?></div>
              <?php if ($cancelado > 0): ?>
                <div class="text-red-400 text-xs line-through">
                  (Cancelado: $<?= number_format($prod['precio'] * $cancelado, 2) ?>)
                </div>
              <?php endif; ?>
              <?php if ($pendiente_cancelacion > 0): ?>
                <div class="text-orange-400 text-xs">
                  (Pendiente: $<?= number_format($prod['precio'] * $pendiente_cancelacion, 2) ?>)
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Order Summary -->
<div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl p-6">
  <h2 class="text-xl font-montserrat-semibold text-white mb-6 flex items-center">
    <i class="bi bi-calculator mr-2 text-green-400"></i>
    Resumen de la Orden
  </h2>
  
  <div class="space-y-4">
    <div class="flex justify-between items-center py-2">
      <span class="text-gray-400">Subtotal (Productos Activos):</span>
      <span class="text-lg font-semibold text-white">$<?= number_format($subtotal, 2) ?></span>
    </div>
    
    <?php if ($total_cancelado > 0): ?>
    <div class="flex justify-between items-center py-2">
      <span class="text-red-400">Total Cancelado:</span>
      <span class="text-lg font-semibold text-red-400 line-through">-$<?= number_format($total_cancelado, 2) ?></span>
    </div>
    <?php endif; ?>
    
    <div class="flex justify-between items-center py-2">
      <span class="text-gray-400">Descuento:</span>
      <span class="text-lg font-semibold text-white">$<?= number_format($descuento, 2) ?></span>
    </div>
    

    
    <?php if ($productos_cancelados > 0): ?>
    <div class="bg-red-900/20 border border-red-600/30 rounded-lg p-3 mb-4">
      <div class="flex items-center justify-between text-sm">
        <span class="text-red-400 flex items-center">
          <i class="bi bi-exclamation-triangle mr-2"></i>
          Productos Cancelados:
        </span>
        <span class="text-red-400 font-semibold"><?= $productos_cancelados ?> unidad(es)</span>
      </div>
    </div>
    <?php endif; ?>
    
    <div class="border-t border-dark-700/50 pt-4">
      <div class="flex justify-between items-center">
        <span class="text-xl font-bold text-white">Total:</span>
        <span class="text-2xl font-bold bg-gradient-to-r from-green-400 to-emerald-400 bg-clip-text text-transparent">
          $<?= number_format($total, 2) ?>
        </span>
      </div>
      <div class="text-xs text-gray-500 mt-1 text-right">
        (<?= $productos_activos ?> producto(s) activo(s)<?= $productos_cancelados > 0 ? ', ' . $productos_cancelados . ' cancelado(s)' : '' ?>)
      </div>
    </div>
  </div>
</div>