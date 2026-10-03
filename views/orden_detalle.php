<?php
// Este archivo es incluido desde index.php, por lo que las rutas son relativas al directorio raíz
// $pdo y $userInfo ya están disponibles desde index.php

require_once __DIR__ . '/../includes/OrdenDetalle.php';

$orden_id = intval($_GET['id'] ?? 0);
$detalle = obtenerDetalleOrden($pdo, $orden_id);

if (!$detalle) {
  echo "<div class='bg-red-500/10 border border-red-500/20 text-red-400 px-6 py-4 rounded-xl mb-6'>
          <i class='bi bi-exclamation-triangle mr-2'></i>
          Orden no encontrada
        </div>";
  exit;
}

$orden = $detalle['orden'];
$productos = $detalle['productos'];
$promociones_aplicadas = $detalle['promociones'];
$pagos_parciales = $detalle['pagos'];
$subtotal = $detalle['subtotal'];
$total_cancelado = $detalle['total_cancelado'];
$productos_activos = $detalle['productos_activos'];
$productos_cancelados = $detalle['productos_cancelados'];
$descuento = $detalle['descuento_promos'];
$descuento_pct = $detalle['descuento_pct'];
$descuento_pct_valor = $detalle['descuento_pct_valor'];
$ajuste = $detalle['ajuste'];
$total = $detalle['total'];
$ordenCerrada = $orden['estado'] !== 'abierta';
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
        <?= !empty($orden['mesero_nombre']) ? htmlspecialchars($orden['mesero_nombre']) : '<span class=\'text-gray-400\'>Sin asignar</span>' ?>
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

    <?php if ($ordenCerrada): ?>
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Fecha de Cierre</label>
      <p class="text-lg font-semibold text-white"><?= !empty($orden['cerrada_en']) ? date('d/m/Y H:i', strtotime($orden['cerrada_en'])) : '-' ?></p>
    </div>

    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Cobrada por</label>
      <p class="text-lg font-semibold text-white"><?= !empty($orden['mesero_nombre']) ? htmlspecialchars($orden['mesero_nombre']) : '<span class="text-gray-400">-</span>' ?></p>
    </div>

    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Método de Pago</label>
      <p class="text-lg font-semibold text-white">
        <?= empty($pagos_parciales) ? htmlspecialchars(textoMetodoPago($orden['metodo_pago'])) : 'Pago dividido (' . count($pagos_parciales) . ')' ?>
      </p>
    </div>
    <?php endif; ?>
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

              <?php if ($prod['nota_adicional'] !== ''): ?>
                <div class="mt-2 pl-3 border-l-2 border-yellow-500/50 text-xs text-yellow-200 flex items-start gap-1">
                  <i class="bi bi-sticky text-yellow-400 mt-0.5"></i>
                  <span class="italic"><?= htmlspecialchars($prod['nota_adicional']) ?></span>
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
              <?php 
              $precio_unitario = floatval($prod['precio']);
              // Sumar precio de variedades
              if (!empty($prod['variedades'])) {
                  foreach ($prod['variedades'] as $variedad) {
                      $precio_unitario += floatval($variedad['precio_adicional']);
                  }
              }
              ?>
              $<?= number_format($precio_unitario, 2) ?>
            </td>
            <td class="px-6 py-4 text-right text-sm font-bold">
              <div class="text-white">$<?= number_format($precio_unitario * $cantidad_activa, 2) ?></div>
              <?php if ($cancelado > 0): ?>
                <div class="text-red-400 text-xs line-through">
                  (Cancelado: $<?= number_format($precio_unitario * $cancelado, 2) ?>)
                </div>
              <?php endif; ?>
              <?php if ($pendiente_cancelacion > 0): ?>
                <div class="text-orange-400 text-xs">
                  (Pendiente: $<?= number_format($precio_unitario * $pendiente_cancelacion, 2) ?>)
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Promociones Aplicadas -->
<?php if (!empty($promociones_aplicadas)): ?>
<div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden mb-8">
  <div class="p-6 border-b border-dark-700/50 bg-gradient-to-r from-yellow-500/10 to-orange-500/10">
    <h2 class="text-xl font-montserrat-semibold text-white flex items-center">
      <i class="bi bi-tags-fill mr-2 text-yellow-400"></i>
      Promociones Aplicadas
    </h2>
  </div>
  
  <div class="p-6 space-y-3">
    <?php foreach ($promociones_aplicadas as $promo): ?>
      <div class="bg-gradient-to-r from-yellow-500/5 to-orange-500/5 border border-yellow-500/20 rounded-xl p-4 hover:border-yellow-500/40 transition-all duration-200">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-500/20 text-yellow-300 border border-yellow-500/30">
                <i class="bi bi-tag-fill mr-1"></i>
                PROMOCIÓN
              </span>
              <span class="text-xs text-gray-400">
                <i class="bi bi-clock mr-1"></i>
                <?= date('d/m/Y H:i', strtotime($promo['aplicada_en'])) ?>
              </span>
            </div>
            
            <h3 class="text-white font-semibold mb-1 flex items-center gap-2">
              <?= htmlspecialchars($promo['nombre_promocion']) ?>
            </h3>
            
            <div class="flex items-center gap-3 text-sm">
              <span class="text-gray-400">
                Tipo: 
                <span class="text-blue-300 font-medium">
                  <?= htmlspecialchars(textoTipoPromocion($promo)) ?>
                </span>
              </span>
            </div>
          </div>
          
          <div class="text-right">
            <div class="text-sm text-gray-400 mb-1">Descuento</div>
            <div class="text-2xl font-bold text-green-400">
              -$<?= number_format($promo['descuento_aplicado'], 2) ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    
    <div class="bg-yellow-900/20 border border-yellow-600/30 rounded-lg p-3 mt-4">
      <div class="flex items-center justify-between">
        <span class="text-yellow-300 font-semibold flex items-center">
          <i class="bi bi-piggy-bank mr-2"></i>
          Total Ahorrado con Promociones:
        </span>
        <span class="text-2xl font-bold text-yellow-400">
          -$<?= number_format($descuento, 2) ?>
        </span>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Pagos de cuenta dividida -->
<?php if (!empty($pagos_parciales)): ?>
<div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden mb-8">
  <div class="p-6 border-b border-dark-700/50">
    <h2 class="text-xl font-montserrat-semibold text-white flex items-center">
      <i class="bi bi-people-fill mr-2 text-cyan-400"></i>
      Pagos de la Cuenta Dividida
    </h2>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full">
      <thead class="bg-dark-700/50">
        <tr>
          <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase">No.</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Método</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase">Monto</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase">Recibido</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase">Cambio</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Registró</th>
          <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase">Hora</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-dark-700/50">
        <?php foreach ($pagos_parciales as $pago): ?>
          <?php $esEfectivo = $pago['metodo_pago'] === 'efectivo' && $pago['dinero_recibido'] !== null; ?>
          <tr class="hover:bg-dark-700/30 transition-colors duration-200">
            <td class="px-6 py-3 text-center text-white font-semibold"><?= intval($pago['numero_pago']) ?></td>
            <td class="px-6 py-3 text-gray-200"><?= htmlspecialchars(textoMetodoPago($pago['metodo_pago'])) ?></td>
            <td class="px-6 py-3 text-right text-green-400 font-semibold">$<?= number_format($pago['monto'], 2) ?></td>
            <td class="px-6 py-3 text-right text-gray-300"><?= $esEfectivo ? '$' . number_format($pago['dinero_recibido'], 2) : '-' ?></td>
            <td class="px-6 py-3 text-right text-gray-300"><?= $esEfectivo ? '$' . number_format($pago['cambio'], 2) : '-' ?></td>
            <td class="px-6 py-3 text-gray-300"><?= htmlspecialchars($pago['usuario_nombre'] ?? '-') ?></td>
            <td class="px-6 py-3 text-center text-gray-400 text-sm"><?= date('d/m/Y H:i', strtotime($pago['pagado_en'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

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
    
    <?php if ($descuento > 0): ?>
    <div class="flex justify-between items-center py-2 bg-yellow-500/5 -mx-2 px-2 rounded-lg">
      <span class="text-yellow-300 flex items-center">
        <i class="bi bi-tags-fill mr-2"></i>
        Descuento por Promociones:
      </span>
      <span class="text-lg font-semibold text-yellow-400">-$<?= number_format($descuento, 2) ?></span>
    </div>
    <?php else: ?>
    <div class="flex justify-between items-center py-2">
      <span class="text-gray-400">Descuento:</span>
      <span class="text-lg font-semibold text-white">$<?= number_format($descuento, 2) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($descuento_pct > 0): ?>
    <div class="flex justify-between items-center py-2 bg-orange-500/5 -mx-2 px-2 rounded-lg">
      <span class="text-orange-300 flex items-center">
        <i class="bi bi-percent mr-2"></i>
        Descuento manual (<?= rtrim(rtrim(number_format($descuento_pct_valor, 2), '0'), '.') ?>%):
      </span>
      <span class="text-lg font-semibold text-orange-400">-$<?= number_format($descuento_pct, 2) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($ajuste > 0): ?>
    <div class="flex justify-between items-center py-2">
      <span class="text-gray-400">Otros descuentos / ajustes:</span>
      <span class="text-lg font-semibold text-gray-300">-$<?= number_format($ajuste, 2) ?></span>
    </div>
    <?php endif; ?>

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