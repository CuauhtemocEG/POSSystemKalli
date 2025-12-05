<?php
// Este archivo es incluido desde index.php, por lo que las rutas son relativas al directorio raíz
// $pdo y $userInfo ya están disponibles desde index.php

// Manejar filtros de fecha
$fechaDesde = $_GET['fecha_desde'] ?? null;
$fechaHasta = $_GET['fecha_hasta'] ?? null;

// Construir condiciones de fecha
$condicionFecha = "";
$condicionFechaHoy = "DATE(creada_en) = CURDATE()";
$condicionFechaSemana = "creada_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$condicionFechaMes = "creada_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

// Texto descriptivo del período
$textoPeriodo = "Hoy";
if ($fechaDesde && $fechaHasta) {
  $condicionFecha = "DATE(creada_en) BETWEEN '$fechaDesde' AND '$fechaHasta'";
  // Si hay filtro personalizado, usar solo ese filtro para todas las consultas
  $condicionFechaHoy = $condicionFecha;
  $condicionFechaSemana = $condicionFecha;
  $condicionFechaMes = $condicionFecha;
  $textoPeriodo = "del " . date('d/m/Y', strtotime($fechaDesde)) . " al " . date('d/m/Y', strtotime($fechaHasta));
}

// Estadísticas básicas con filtros aplicados
$totalOrdenes = $pdo->query("SELECT COUNT(*) FROM ordenes WHERE estado = 'cerrada'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn();
$ventasHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada'")->fetchColumn() ?? 0;
$ventasSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada'")->fetchColumn() ?? 0;
$ventasMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada'")->fetchColumn() ?? 0;
$ordenesActivas = $pdo->query("SELECT COUNT(*) FROM ordenes WHERE estado = 'abierta'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn();

// Totales por método de pago - Respetan filtro de fecha personalizado
$ventasEfectivo = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND metodo_pago = 'efectivo'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn() ?? 0;
$ventasDebito = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND metodo_pago = 'debito'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn() ?? 0;
$ventasCredito = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND metodo_pago = 'credito'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn() ?? 0;
$ventasTransferencia = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND metodo_pago = 'transferencia'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn() ?? 0;
$ventasTarjeta = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND metodo_pago = 'tarjeta'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn() ?? 0; // Para compatibilidad

// Totales por método de pago - HOY
$ventasEfectivoHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada' AND metodo_pago = 'efectivo'")->fetchColumn() ?? 0;
$ventasDebitoHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada' AND metodo_pago = 'debito'")->fetchColumn() ?? 0;
$ventasCreditoHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada' AND metodo_pago = 'credito'")->fetchColumn() ?? 0;
$ventasTransferenciaHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada' AND metodo_pago = 'transferencia'")->fetchColumn() ?? 0;
$ventasTarjetaHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada' AND metodo_pago = 'tarjeta'")->fetchColumn() ?? 0;

// Totales por método de pago - SEMANA
$ventasEfectivoSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada' AND metodo_pago = 'efectivo'")->fetchColumn() ?? 0;
$ventasDebitoSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada' AND metodo_pago = 'debito'")->fetchColumn() ?? 0;
$ventasCreditoSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada' AND metodo_pago = 'credito'")->fetchColumn() ?? 0;
$ventasTransferenciaSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada' AND metodo_pago = 'transferencia'")->fetchColumn() ?? 0;
$ventasTarjetaSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada' AND metodo_pago = 'tarjeta'")->fetchColumn() ?? 0;

// Totales por método de pago - MES
$ventasEfectivoMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada' AND metodo_pago = 'efectivo'")->fetchColumn() ?? 0;
$ventasDebitoMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada' AND metodo_pago = 'debito'")->fetchColumn() ?? 0;
$ventasCreditoMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada' AND metodo_pago = 'credito'")->fetchColumn() ?? 0;
$ventasTransferenciaMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada' AND metodo_pago = 'transferencia'")->fetchColumn() ?? 0;
$ventasTarjetaMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada' AND metodo_pago = 'tarjeta'")->fetchColumn() ?? 0;

// Estadísticas detalladas por método de pago
$estadisticasMetodoPago = $pdo->query("
    SELECT 
        metodo_pago,
        COUNT(*) as total_ordenes,
        COALESCE(SUM(total), 0) as total_ventas,
        COALESCE(AVG(total), 0) as promedio_venta,
        MIN(total) as venta_minima,
        MAX(total) as venta_maxima
    FROM ordenes 
    WHERE estado = 'cerrada' " . ($condicionFecha ? " AND $condicionFecha" : "") . "
    GROUP BY metodo_pago
    ORDER BY total_ventas DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Ventas por método de pago por día (últimos 7 días)
$ventasDiariasMetodoPago = $pdo->query("
    SELECT 
        DATE(creada_en) as fecha,
        metodo_pago,
        COUNT(*) as ordenes,
        COALESCE(SUM(total), 0) as total
    FROM ordenes 
    WHERE estado = 'cerrada' 
    AND creada_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(creada_en), metodo_pago
    ORDER BY fecha DESC, metodo_pago
")->fetchAll(PDO::FETCH_ASSOC);

// Productos más vendidos - Solo productos preparados y no cancelados
try {
  // Intentar con campo cancelado y preparado
  $productosVendidos = $pdo->query("
        SELECT p.nombre, SUM(op.cantidad) as total_vendido, SUM(op.cantidad * p.precio) as total_ingresos
        FROM orden_productos op 
        JOIN productos p ON op.producto_id = p.id 
        JOIN ordenes o ON op.orden_id = o.id 
        WHERE o.estado = 'cerrada' AND op.preparado = 1 AND (op.cancelado = 0 OR op.cancelado IS NULL)" .
    ($condicionFecha ? " AND $condicionFecha" : "") . "
        GROUP BY p.id, p.nombre 
        ORDER BY total_vendido DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  // Si falla con preparado, intentar solo con cancelado
  try {
    $productosVendidos = $pdo->query("
            SELECT p.nombre, SUM(op.cantidad) as total_vendido, SUM(op.cantidad * p.precio) as total_ingresos
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            JOIN ordenes o ON op.orden_id = o.id 
            WHERE o.estado = 'cerrada' AND (op.cancelado = 0 OR op.cancelado IS NULL)" .
      ($condicionFecha ? " AND $condicionFecha" : "") . "
            GROUP BY p.id, p.nombre 
            ORDER BY total_vendido DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    // Si falla completamente, usar sin campos adicionales
    $productosVendidos = $pdo->query("
            SELECT p.nombre, SUM(op.cantidad) as total_vendido, SUM(op.cantidad * p.precio) as total_ingresos
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            JOIN ordenes o ON op.orden_id = o.id 
            WHERE o.estado = 'cerrada'" .
      ($condicionFecha ? " AND $condicionFecha" : "") . "
            GROUP BY p.id, p.nombre 
            ORDER BY total_vendido DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
  }
}

// Ventas por mesa (en lugar de por usuario ya que no existe usuario_id)
$ventasPorMesa = $pdo->query("
    SELECT 
        m.nombre as mesa,
        COUNT(o.id) as ordenes_cerradas,
        COALESCE(SUM(o.total), 0) as total_ventas
    FROM ordenes o
    JOIN mesas m ON o.mesa_id = m.id
    WHERE o.estado = 'cerrada'" .
  ($condicionFecha ? " AND $condicionFecha" : " AND o.creada_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)") . "
    GROUP BY o.mesa_id, m.nombre
    ORDER BY total_ventas DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Órdenes activas (abiertas) - datos detallados
$ordenesActivasDetalle = $pdo->query("
    SELECT 
        o.id,
        o.codigo,
        o.mesa_id,
        m.nombre as mesa,
        o.total,
        o.metodo_pago,
        o.creada_en,
        COUNT(op.id) as productos_count,
        TIMESTAMPDIFF(MINUTE, o.creada_en, NOW()) as minutos_abierta
    FROM ordenes o
    JOIN mesas m ON o.mesa_id = m.id
    LEFT JOIN orden_productos op ON o.id = op.orden_id
    WHERE o.estado = 'abierta'
    GROUP BY o.id, o.codigo, o.mesa_id, m.nombre, o.total, o.metodo_pago, o.creada_en
    ORDER BY o.creada_en ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Órdenes cerradas recientes - con paginación
$paginaOrdenes = isset($_GET['pagina_ordenes']) ? (int)$_GET['pagina_ordenes'] : 1;
$ordenesPorPagina = 10;
$offsetOrdenes = ($paginaOrdenes - 1) * $ordenesPorPagina;

// Contar total de órdenes cerradas para paginación
$totalOrdenesQuery = "
    SELECT COUNT(DISTINCT o.id) as total
    FROM ordenes o
    WHERE o.estado = 'cerrada'" .
  ($condicionFecha ? " AND $condicionFecha" : " AND o.creada_en >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");

$totalOrdenesCerradas = $pdo->query($totalOrdenesQuery)->fetchColumn();
$totalPaginasOrdenes = ceil($totalOrdenesCerradas / $ordenesPorPagina);

// Órdenes cerradas recientes (con paginación) - datos detallados
$ordenesCerradasDetalle = $pdo->query("
    SELECT 
        o.id,
        o.codigo,
        o.mesa_id,
        m.nombre as mesa,
        o.total,
        o.metodo_pago,
        o.creada_en,
        o.cerrada_en,
        CASE 
            WHEN o.cerrada_en IS NOT NULL THEN
                TIMESTAMPDIFF(MINUTE, o.creada_en, o.cerrada_en)
            ELSE 
                NULL
        END as tiempo_total_minutos,
        COUNT(op.id) as productos_count
    FROM ordenes o
    JOIN mesas m ON o.mesa_id = m.id
    LEFT JOIN orden_productos op ON o.id = op.orden_id
    WHERE o.estado = 'cerrada'" .
  ($condicionFecha ? " AND $condicionFecha" : " AND o.creada_en >= DATE_SUB(NOW(), INTERVAL 24 HOUR)") . "
    GROUP BY o.id, o.codigo, o.mesa_id, m.nombre, o.total, o.metodo_pago, o.creada_en, o.cerrada_en
    ORDER BY o.cerrada_en DESC
    LIMIT $ordenesPorPagina OFFSET $offsetOrdenes
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Header de Página -->
<div class="mb-8 mt-8">
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                <i class="bi bi-graph-up text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-white">Reportes y Estadísticas</h1>
                <p class="text-gray-400 text-sm">Análisis completo del rendimiento del restaurante</p>
            </div>
        </div>
    </div>
</div>
<!-- Filtros y Acciones -->
<div class="mb-8">
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mr-3">
                <i class="bi bi-funnel text-white"></i>
            </div>
            <h2 class="text-xl font-semibold text-white">Filtros de Búsqueda</h2>
        </div>
        
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-4">
            <!-- Filtros de Fecha -->
            <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3 flex-1">
                <div class="w-full sm:w-auto">
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        <i class="bi bi-calendar mr-1"></i>Desde
                    </label>
                    <input type="date" id="fecha-desde"
                        class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                        value="<?= date('Y-m-d') ?>">
                </div>

                <div class="w-full sm:w-auto">
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        <i class="bi bi-calendar mr-1"></i>Hasta
                    </label>
                    <input type="date" id="fecha-hasta"
                        class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                        value="<?= date('Y-m-d') ?>">
                </div>

                <button onclick="filtrarPorFecha()"
                    class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                    <i class="bi bi-funnel"></i>
                    <span>Filtrar</span>
                </button>

                <button onclick="limpiarFiltros()"
                    class="w-full sm:w-auto px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Limpiar</span>
                </button>
            </div>

            <?php
            $userInfo = getUserInfo();
            $esAdministrador = $userInfo['rol'] === 'administrador';
            if ($esAdministrador || hasPermission('reportes', 'ver')):
            ?>
            <!-- Botón de Reportes PDF -->
            <div>
                <button onclick="toggleReportesPDF()" 
                    class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold transition-colors flex items-center gap-2">
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Reportes PDF</span>
                    <i id="pdf-toggle-icon" class="bi bi-chevron-down transition-transform"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Total Órdenes -->
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-600/20 rounded-xl flex items-center justify-center">
                    <i class="bi bi-receipt text-2xl text-blue-400"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-white"><?= number_format($totalOrdenes) ?></div>
                    <p class="text-gray-400 text-sm">Órdenes</p>
                </div>
            </div>
            <h3 class="text-sm font-medium text-gray-300">Órdenes Totales</h3>
        </div>
    </div>

    <!-- Órdenes Activas -->
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-orange-600/20 rounded-xl flex items-center justify-center">
                    <i class="bi bi-clock text-2xl text-orange-400"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-white"><?= number_format($ordenesActivas) ?></div>
                    <p class="text-gray-400 text-sm">Activas</p>
                </div>
            </div>
            <h3 class="text-sm font-medium text-gray-300">Órdenes Activas</h3>
        </div>
    </div>

    <!-- Ventas Hoy -->
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-600/20 rounded-xl flex items-center justify-center">
                    <i class="bi bi-cash text-2xl text-green-400"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-white">$<?= number_format($ventasHoy, 2) ?></div>
                    <p class="text-gray-400 text-sm">Hoy</p>
                </div>
            </div>
            <h3 class="text-sm font-medium text-gray-300">Ventas Hoy</h3>
        </div>
    </div>

    <!-- Ventas Semana -->
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-600/20 rounded-xl flex items-center justify-center">
                    <i class="bi bi-calendar-week text-2xl text-purple-400"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-white">$<?= number_format($ventasSemana, 2) ?></div>
                    <p class="text-gray-400 text-sm">7 días</p>
                </div>
            </div>
            <h3 class="text-sm font-medium text-gray-300">Ventas Semana</h3>
        </div>
    </div>

    <!-- Ventas Mes -->
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-indigo-600/20 rounded-xl flex items-center justify-center">
                    <i class="bi bi-calendar-month text-2xl text-indigo-400"></i>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-white">$<?= number_format($ventasMes, 2) ?></div>
                    <p class="text-gray-400 text-sm">30 días</p>
                </div>
            </div>
            <h3 class="text-sm font-medium text-gray-300">Ventas Mes</h3>
        </div>
    </div>
</div>

<!-- Resumen Visual de Corte de Caja -->
<div class="mb-8">
    <div class="bg-gradient-to-br from-dark-700/40 to-dark-800/40 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                    <i class="bi bi-cash-stack text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-white">Resumen de Cierre del Día</h2>
                    <p class="text-gray-400 text-sm">Corte de caja • <?= date('d/m/Y') ?> • <?= date('H:i') ?> hrs</p>
                </div>
            </div>
            <div class="hidden sm:block bg-green-600/20 px-4 py-2 rounded-xl border border-green-600/30">
                <p class="text-green-400 text-2xl font-bold">$<?= number_format($ventasHoy, 2) ?></p>
                <p class="text-green-400/70 text-xs text-center">Total Hoy</p>
            </div>
        </div>

        <!-- Grid de Totales por Método -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php 
            $totalHoy = $ventasEfectivoHoy + $ventasDebitoHoy + $ventasCreditoHoy + $ventasTransferenciaHoy + $ventasTarjetaHoy;
            $metodosPago = [
                [
                    'nombre' => 'Efectivo',
                    'icon' => 'cash-coin',
                    'color' => 'green',
                    'monto' => $ventasEfectivoHoy,
                    'porcentaje' => $totalHoy > 0 ? ($ventasEfectivoHoy / $totalHoy) * 100 : 0
                ],
                [
                    'nombre' => 'Débito',
                    'icon' => 'credit-card',
                    'color' => 'blue',
                    'monto' => $ventasDebitoHoy,
                    'porcentaje' => $totalHoy > 0 ? ($ventasDebitoHoy / $totalHoy) * 100 : 0
                ],
                [
                    'nombre' => 'Crédito',
                    'icon' => 'credit-card-fill',
                    'color' => 'purple',
                    'monto' => $ventasCreditoHoy,
                    'porcentaje' => $totalHoy > 0 ? ($ventasCreditoHoy / $totalHoy) * 100 : 0
                ],
                [
                    'nombre' => 'Transferencia',
                    'icon' => 'bank',
                    'color' => 'indigo',
                    'monto' => $ventasTransferenciaHoy,
                    'porcentaje' => $totalHoy > 0 ? ($ventasTransferenciaHoy / $totalHoy) * 100 : 0
                ]
            ];

            foreach ($metodosPago as $metodo):
                if ($metodo['monto'] > 0):
            ?>
                <div class="bg-dark-600/30 rounded-xl p-4 border border-dark-500/50 hover:border-<?= $metodo['color'] ?>-500/30 transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 bg-<?= $metodo['color'] ?>-600/20 rounded-lg flex items-center justify-center">
                            <i class="bi bi-<?= $metodo['icon'] ?> text-<?= $metodo['color'] ?>-400 text-lg"></i>
                        </div>
                        <span class="text-xs text-<?= $metodo['color'] ?>-400 font-semibold bg-<?= $metodo['color'] ?>-600/10 px-2 py-1 rounded">
                            <?= number_format($metodo['porcentaje'], 0) ?>%
                        </span>
                    </div>
                    <h4 class="text-gray-400 text-xs mb-1"><?= $metodo['nombre'] ?></h4>
                    <p class="text-white text-xl font-bold">$<?= number_format($metodo['monto'], 2) ?></p>
                    
                    <!-- Mini barra de progreso -->
                    <div class="mt-3 w-full bg-dark-700 rounded-full h-1">
                        <div class="bg-<?= $metodo['color'] ?>-500 h-1 rounded-full transition-all" 
                             style="width: <?= $metodo['porcentaje'] ?>%"></div>
                    </div>
                </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>

        <!-- Estadísticas Adicionales -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-dark-600/50">
            <div class="text-center">
                <p class="text-gray-400 text-xs mb-1">Órdenes Cerradas</p>
                <p class="text-white text-2xl font-bold"><?= $totalOrdenes ?></p>
            </div>
            <div class="text-center">
                <p class="text-gray-400 text-xs mb-1">Ticket Promedio</p>
                <p class="text-blue-400 text-2xl font-bold">
                    $<?= $totalOrdenes > 0 ? number_format($ventasHoy / $totalOrdenes, 2) : '0.00' ?>
                </p>
            </div>
            <div class="text-center">
                <p class="text-gray-400 text-xs mb-1">Productos Vendidos</p>
                <p class="text-purple-400 text-2xl font-bold">
                    <?= array_sum(array_column($productosVendidos, 'total_vendido')) ?>
                </p>
            </div>
            <div class="text-center">
                <p class="text-gray-400 text-xs mb-1">Órdenes Activas</p>
                <p class="text-orange-400 text-2xl font-bold"><?= $ordenesActivas ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Sección de Reportes PDF -->
<?php
$userInfo = getUserInfo();
$esAdministrador = $userInfo['rol'] === 'administrador';
if ($esAdministrador || hasPermission('reportes', 'ver')):
?>
<div id="reportes-pdf-section" class="mb-8 overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0; opacity: 0;">
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-8">
        <div class="mb-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center">
                    <i class="bi bi-file-earmark-pdf text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-white">Reportes Ejecutivos PDF</h2>
                    <p class="text-gray-400 text-sm">Genere reportes profesionales con el logo de Kalli</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Reporte de Productos Vendidos -->
            <div class="bg-dark-600/30 rounded-xl p-6 border border-dark-500/50">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-blue-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-box-seam text-blue-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-white mb-2">Productos Vendidos del Día</h3>
                        <p class="text-gray-400 text-sm mb-4">Reporte detallado de todos los productos vendidos, cantidades, subtotales por categoría y resumen total del día.</p>
                        <button onclick="generarReporteProductos()" 
                            class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                            <i class="bi bi-download"></i>
                            <span>Generar PDF</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Reporte de Órdenes del Día -->
            <div class="bg-dark-600/30 rounded-xl p-6 border border-dark-500/50">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-green-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-receipt text-green-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-white mb-2">Órdenes del Día</h3>
                        <p class="text-gray-400 text-sm mb-4">Desglose completo de todas las órdenes, métodos de pago, estados y total de ventas del día.</p>
                        <button onclick="generarReporteOrdenes()" 
                            class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                            <i class="bi bi-download"></i>
                            <span>Generar PDF</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-amber-500/10 rounded-xl border border-amber-500/30">
            <div class="flex items-center gap-3">
                <i class="bi bi-info-circle text-amber-400 text-lg"></i>
                <div class="text-sm text-amber-200">
                    <strong>Nota:</strong> Los reportes incluyen el logo de Kalli y están diseñados para presentaciones profesionales. Todos los datos corresponden al día actual.
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Sección de Métodos de Pago -->
<div class="mb-8">
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-3">
                <i class="bi bi-credit-card-2-front text-white text-lg"></i>
            </div>
            <h2 class="text-xl font-semibold text-white">Análisis por Método de Pago <?= $textoPeriodo ?></h2>
        </div>

        <!-- Tarjetas de totales por método de pago -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <?php $totalTodos = $ventasEfectivo + $ventasDebito + $ventasCredito + $ventasTransferencia + $ventasTarjeta; ?>
            
            <!-- Efectivo Total -->
            <div class="bg-dark-600/30 backdrop-blur-xl rounded-xl border border-dark-500/50 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-600/20 rounded-xl flex items-center justify-center">
                            <i class="bi bi-cash-coin text-2xl text-green-400"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-white">$<?= number_format($ventasEfectivo, 2) ?></div>
                            <p class="text-gray-400 text-xs mt-1">
                                <?= $totalTodos > 0 ? number_format(($ventasEfectivo / $totalTodos) * 100, 1) : 0 ?>% del total
                            </p>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-gray-300">Efectivo</h3>
                </div>
            </div>

            <!-- Débito -->
            <div class="bg-dark-600/30 backdrop-blur-xl rounded-xl border border-dark-500/50 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-600/20 rounded-xl flex items-center justify-center">
                            <i class="bi bi-credit-card text-2xl text-blue-400"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-white">$<?= number_format($ventasDebito, 2) ?></div>
                            <p class="text-gray-400 text-xs mt-1">
                                <?= $totalTodos > 0 ? number_format(($ventasDebito / $totalTodos) * 100, 1) : 0 ?>% del total
                            </p>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-gray-300">Débito</h3>
                </div>
            </div>

            <!-- Crédito -->
            <div class="bg-dark-600/30 backdrop-blur-xl rounded-xl border border-dark-500/50 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-600/20 rounded-xl flex items-center justify-center">
                            <i class="bi bi-credit-card-fill text-2xl text-purple-400"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-white">$<?= number_format($ventasCredito, 2) ?></div>
                            <p class="text-gray-400 text-xs mt-1">
                                <?= $totalTodos > 0 ? number_format(($ventasCredito / $totalTodos) * 100, 1) : 0 ?>% del total
                            </p>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-gray-300">Crédito</h3>
                </div>
            </div>

            <!-- Transferencia -->
            <div class="bg-dark-600/30 backdrop-blur-xl rounded-xl border border-dark-500/50 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-indigo-600/20 rounded-xl flex items-center justify-center">
                            <i class="bi bi-bank text-2xl text-indigo-400"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-white">$<?= number_format($ventasTransferencia, 2) ?></div>
                            <p class="text-gray-400 text-xs mt-1">
                                <?= $totalTodos > 0 ? number_format(($ventasTransferencia / $totalTodos) * 100, 1) : 0 ?>% del total
                            </p>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-gray-300">Transferencia</h3>
                </div>
            </div>

            <!-- Tarjeta (Legacy) - Solo mostrar si hay datos -->
            <?php if ($ventasTarjeta > 0): ?>
            <div class="bg-dark-600/30 backdrop-blur-xl rounded-xl border border-dark-500/50 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gray-600/20 rounded-xl flex items-center justify-center">
                            <i class="bi bi-credit-card-2-front text-2xl text-gray-400"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-white">$<?= number_format($ventasTarjeta, 2) ?></div>
                            <p class="text-gray-400 text-xs mt-1">
                                <?= $totalTodos > 0 ? number_format(($ventasTarjeta / $totalTodos) * 100, 1) : 0 ?>% del total
                            </p>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-gray-300">Tarjeta (Legacy)</h3>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tabla detallada de métodos de pago -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Estadísticas por método de pago -->
            <div class="bg-dark-700/30 backdrop-blur-xl rounded-xl border border-dark-600/50 overflow-hidden">
                <div class="p-6 border-b border-dark-600/50">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="bi bi-bar-chart text-green-400 mr-2"></i>
                        Estadísticas por Método
                    </h3>
                </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-dark-700/50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Método</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Órdenes</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Total</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Promedio</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-dark-700/50">
            <?php if (empty($estadisticasMetodoPago)): ?>
              <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                  <i class="bi bi-inbox text-3xl mb-2"></i>
                  <p>No hay datos de ventas disponibles</p>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($estadisticasMetodoPago as $metodo): ?>
                <tr class="hover:bg-dark-700/30 transition-colors">
                  <td class="px-6 py-4">
                    <div class="flex items-center">
                      <?php if ($metodo['metodo_pago'] == 'efectivo'): ?>
                        <i class="bi bi-cash-coin text-green-400 mr-2"></i>
                        <span class="text-white font-medium">Efectivo</span>
                      <?php elseif ($metodo['metodo_pago'] == 'debito'): ?>
                        <i class="bi bi-credit-card text-blue-400 mr-2"></i>
                        <span class="text-white font-medium">Débito</span>
                      <?php elseif ($metodo['metodo_pago'] == 'credito'): ?>
                        <i class="bi bi-credit-card-fill text-purple-400 mr-2"></i>
                        <span class="text-white font-medium">Crédito</span>
                      <?php elseif ($metodo['metodo_pago'] == 'transferencia'): ?>
                        <i class="bi bi-bank text-indigo-400 mr-2"></i>
                        <span class="text-white font-medium">Transferencia</span>
                      <?php elseif ($metodo['metodo_pago'] == 'tarjeta'): ?>
                        <i class="bi bi-credit-card-2-front text-slate-400 mr-2"></i>
                        <span class="text-white font-medium">Tarjeta (Legacy)</span>
                      <?php else: ?>
                        <i class="bi bi-question-circle text-gray-400 mr-2"></i>
                        <span class="text-white font-medium"><?= ucfirst($metodo['metodo_pago']) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span class="text-white font-medium"><?= number_format($metodo['total_ordenes']) ?></span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <span class="text-white font-bold">$<?= number_format($metodo['total_ventas'], 2) ?></span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <span class="text-gray-300">$<?= number_format($metodo['promedio_venta'], 2) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Comparativa por períodos -->
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-xl border border-dark-600/50 overflow-hidden">
      <div class="p-6 border-b border-dark-600/50">
        <h3 class="text-lg font-semibold text-white flex items-center">
          <i class="bi bi-calendar-range text-blue-400 mr-2"></i>
          Comparativa por Períodos
        </h3>
      </div>

      <div class="p-6 space-y-6">
        <!-- Hoy -->
        <div>
          <h4 class="text-white font-medium mb-3 flex items-center">
            <i class="bi bi-calendar-day text-yellow-400 mr-2"></i>
            Hoy
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-cash-coin text-green-400 mr-2 text-sm"></i>
                Efectivo
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasEfectivoHoy, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card text-blue-400 mr-2 text-sm"></i>
                Débito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasDebitoHoy, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card-fill text-purple-400 mr-2 text-sm"></i>
                Crédito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasCreditoHoy, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-bank text-indigo-400 mr-2 text-sm"></i>
                Transferencia
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasTransferenciaHoy, 2) ?></span>
            </div>
            <?php if ($ventasTarjetaHoy > 0): ?>
              <div class="flex justify-between items-center">
                <span class="text-gray-300 flex items-center">
                  <i class="bi bi-credit-card-2-front text-slate-400 mr-2 text-sm"></i>
                  Tarjeta (Legacy)
                </span>
                <span class="text-white font-bold">$<?= number_format($ventasTarjetaHoy, 2) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Semana -->
        <div>
          <h4 class="text-white font-medium mb-3 flex items-center">
            <i class="bi bi-calendar-week text-orange-400 mr-2"></i>
            Esta Semana
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-cash-coin text-green-400 mr-2 text-sm"></i>
                Efectivo
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasEfectivoSemana, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card text-blue-400 mr-2 text-sm"></i>
                Débito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasDebitoSemana, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card-fill text-purple-400 mr-2 text-sm"></i>
                Crédito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasCreditoSemana, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-bank text-indigo-400 mr-2 text-sm"></i>
                Transferencia
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasTransferenciaSemana, 2) ?></span>
            </div>
            <?php if ($ventasTarjetaSemana > 0): ?>
              <div class="flex justify-between items-center">
                <span class="text-gray-300 flex items-center">
                  <i class="bi bi-credit-card-2-front text-slate-400 mr-2 text-sm"></i>
                  Tarjeta (Legacy)
                </span>
                <span class="text-white font-bold">$<?= number_format($ventasTarjetaSemana, 2) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Mes -->
        <div>
          <h4 class="text-white font-medium mb-3 flex items-center">
            <i class="bi bi-calendar-month text-purple-400 mr-2"></i>
            Este Mes
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-cash-coin text-green-400 mr-2 text-sm"></i>
                Efectivo
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasEfectivoMes, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card text-blue-400 mr-2 text-sm"></i>
                Débito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasDebitoMes, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card-fill text-purple-400 mr-2 text-sm"></i>
                Crédito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasCreditoMes, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-bank text-indigo-400 mr-2 text-sm"></i>
                Transferencia
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasTransferenciaMes, 2) ?></span>
            </div>
            <?php if ($ventasTarjetaMes > 0): ?>
              <div class="flex justify-between items-center">
                <span class="text-gray-300 flex items-center">
                  <i class="bi bi-credit-card-2-front text-slate-400 mr-2 text-sm"></i>
                  Tarjeta (Legacy)
                </span>
                <span class="text-white font-bold">$<?= number_format($ventasTarjetaMes, 2) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráfico de barras visual para métodos de pago -->
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-xl border border-dark-600/50 p-6">
    <h3 class="text-lg font-semibold text-white mb-6 flex items-center">
      <i class="bi bi-graph-up text-purple-400 mr-2"></i>
      Distribución Visual por Método de Pago <?= $textoPeriodo ?>
    </h3>

    <div class="space-y-4">
      <?php
      $totalGeneral = $ventasEfectivo + $ventasDebito + $ventasCredito + $ventasTransferencia + $ventasTarjeta;
      $porcentajeEfectivo = $totalGeneral > 0 ? ($ventasEfectivo / $totalGeneral) * 100 : 0;
      $porcentajeDebito = $totalGeneral > 0 ? ($ventasDebito / $totalGeneral) * 100 : 0;
      $porcentajeCredito = $totalGeneral > 0 ? ($ventasCredito / $totalGeneral) * 100 : 0;
      $porcentajeTransferencia = $totalGeneral > 0 ? ($ventasTransferencia / $totalGeneral) * 100 : 0;
      $porcentajeTarjeta = $totalGeneral > 0 ? ($ventasTarjeta / $totalGeneral) * 100 : 0;
      ?>

      <!-- Barra Efectivo -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <span class="text-white font-medium flex items-center">
            <i class="bi bi-cash-coin text-green-400 mr-2"></i>
            Efectivo
          </span>
          <span class="text-gray-300">
            $<?= number_format($ventasEfectivo, 2) ?> (<?= number_format($porcentajeEfectivo, 1) ?>%)
          </span>
        </div>
        <div class="w-full bg-dark-700 rounded-full h-3">
          <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300"
            style="width: <?= $porcentajeEfectivo ?>%"></div>
        </div>
      </div>

      <!-- Barra Débito -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <span class="text-white font-medium flex items-center">
            <i class="bi bi-credit-card text-blue-400 mr-2"></i>
            Débito
          </span>
          <span class="text-gray-300">
            $<?= number_format($ventasDebito, 2) ?> (<?= number_format($porcentajeDebito, 1) ?>%)
          </span>
        </div>
        <div class="w-full bg-dark-700 rounded-full h-3">
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300"
            style="width: <?= $porcentajeDebito ?>%"></div>
        </div>
      </div>

      <!-- Barra Crédito -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <span class="text-white font-medium flex items-center">
            <i class="bi bi-credit-card-fill text-purple-400 mr-2"></i>
            Crédito
          </span>
          <span class="text-gray-300">
            $<?= number_format($ventasCredito, 2) ?> (<?= number_format($porcentajeCredito, 1) ?>%)
          </span>
        </div>
        <div class="w-full bg-dark-700 rounded-full h-3">
          <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-3 rounded-full transition-all duration-300"
            style="width: <?= $porcentajeCredito ?>%"></div>
        </div>
      </div>

      <!-- Barra Transferencia -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <span class="text-white font-medium flex items-center">
            <i class="bi bi-bank text-indigo-400 mr-2"></i>
            Transferencia
          </span>
          <span class="text-gray-300">
            $<?= number_format($ventasTransferencia, 2) ?> (<?= number_format($porcentajeTransferencia, 1) ?>%)
          </span>
        </div>
        <div class="w-full bg-dark-700 rounded-full h-3">
          <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-3 rounded-full transition-all duration-300"
            style="width: <?= $porcentajeTransferencia ?>%"></div>
        </div>
      </div>

      <!-- Barra Tarjeta (Legacy) - Solo si hay datos -->
      <?php if ($ventasTarjeta > 0): ?>
        <div>
          <div class="flex justify-between items-center mb-2">
            <span class="text-white font-medium flex items-center">
              <i class="bi bi-credit-card-2-front text-slate-400 mr-2"></i>
              Tarjeta (Legacy)
            </span>
            <span class="text-gray-300">
              $<?= number_format($ventasTarjeta, 2) ?> (<?= number_format($porcentajeTarjeta, 1) ?>%)
            </span>
          </div>
          <div class="w-full bg-dark-700 rounded-full h-3">
            <div class="bg-gradient-to-r from-slate-500 to-slate-600 h-3 rounded-full transition-all duration-300"
              style="width: <?= $porcentajeTarjeta ?>%"></div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-6 p-4 bg-dark-600/30 rounded-xl border border-dark-500/50">
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-center sm:text-left">
          <p class="text-gray-400 text-sm">Total General</p>
          <p class="text-2xl font-bold text-white">$<?= number_format($totalGeneral, 2) ?></p>
        </div>
        <div class="flex flex-wrap gap-4 justify-center">
          <div class="text-center">
            <p class="text-green-400 font-medium text-sm">Efectivo</p>
            <p class="text-lg font-bold text-white"><?= number_format($porcentajeEfectivo, 1) ?>%</p>
          </div>
          <div class="text-center">
            <p class="text-blue-400 font-medium text-sm">Débito</p>
            <p class="text-lg font-bold text-white"><?= number_format($porcentajeDebito, 1) ?>%</p>
          </div>
          <div class="text-center">
            <p class="text-purple-400 font-medium text-sm">Crédito</p>
            <p class="text-lg font-bold text-white"><?= number_format($porcentajeCredito, 1) ?>%</p>
          </div>
          <div class="text-center">
            <p class="text-indigo-400 font-medium text-sm">Transferencia</p>
            <p class="text-lg font-bold text-white"><?= number_format($porcentajeTransferencia, 1) ?>%</p>
          </div>
          <?php if ($ventasTarjeta > 0): ?>
            <div class="text-center">
              <p class="text-slate-400 font-medium text-sm">Legacy</p>
              <p class="text-lg font-bold text-white"><?= number_format($porcentajeTarjeta, 1) ?>%</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Productos Más Vendidos y Ventas por Mesa -->
<div class="mb-8">
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center mr-3">
                <i class="bi bi-bar-chart-fill text-white"></i>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-white">Rendimiento de Ventas</h2>
                <p class="text-gray-400 text-sm">Top productos y análisis por mesa • Hoy: <?= date('d/m/Y') ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- PRODUCTOS MÁS VENDIDOS - Diseño Ranking -->
            <div class="bg-dark-600/30 backdrop-blur-xl rounded-xl border border-dark-500/50 overflow-hidden">
                <div class="p-5 border-b border-dark-500/50 bg-gradient-to-r from-yellow-600/10 to-orange-600/10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-yellow-600/20 rounded-lg flex items-center justify-center">
                                <i class="bi bi-trophy-fill text-yellow-400 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Top Productos del Día</h3>
                                <p class="text-xs text-gray-400">Los más vendidos de hoy</p>
                            </div>
                        </div>
                        <div class="bg-yellow-600/20 px-3 py-1 rounded-lg">
                            <span class="text-yellow-400 text-sm font-bold"><?= count($productosVendidos) ?></span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <?php if (empty($productosVendidos)): ?>
                        <div class="flex flex-col items-center justify-center py-12">
                            <div class="w-16 h-16 bg-gray-600/20 rounded-full flex items-center justify-center mb-4">
                                <i class="bi bi-inbox text-3xl text-gray-500"></i>
                            </div>
                            <p class="text-gray-400 text-center mb-1">No hay productos vendidos hoy</p>
                            <p class="text-gray-500 text-sm">Las ventas aparecerán aquí</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($productosVendidos, 0, 8) as $index => $producto): ?>
                                <div class="group hover:bg-dark-500/30 rounded-lg p-3 transition-colors">
                                    <div class="flex items-center gap-4">
                                        <!-- Ranking Badge -->
                                        <div class="flex-shrink-0">
                                            <?php if ($index === 0): ?>
                                                <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center shadow-lg">
                                                    <i class="bi bi-trophy-fill text-white text-lg"></i>
                                                </div>
                                            <?php elseif ($index === 1): ?>
                                                <div class="w-10 h-10 bg-gradient-to-br from-gray-300 to-gray-500 rounded-lg flex items-center justify-center shadow-lg">
                                                    <i class="bi bi-trophy-fill text-white text-lg"></i>
                                                </div>
                                            <?php elseif ($index === 2): ?>
                                                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center shadow-lg">
                                                    <i class="bi bi-trophy-fill text-white text-lg"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-10 h-10 bg-dark-500/50 rounded-lg flex items-center justify-center">
                                                    <span class="text-gray-400 font-bold"><?= $index + 1 ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Producto Info -->
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-white font-medium truncate mb-1"><?= htmlspecialchars($producto['nombre']) ?></h4>
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex items-center gap-1 text-xs text-blue-400">
                                                    <i class="bi bi-box"></i>
                                                    <span class="font-semibold"><?= number_format($producto['total_vendido']) ?></span> vendidos
                                                </span>
                                                <span class="text-gray-500">•</span>
                                                <span class="text-xs text-green-400 font-bold">
                                                    $<?= number_format($producto['total_ingresos'], 2) ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Barra de Progreso -->
                                        <div class="hidden sm:block flex-shrink-0 w-24">
                                            <?php 
                                            $maxVentas = !empty($productosVendidos) ? max(array_column($productosVendidos, 'total_vendido')) : 1;
                                            $porcentaje = ($producto['total_vendido'] / $maxVentas) * 100;
                                            ?>
                                            <div class="w-full bg-dark-700 rounded-full h-2">
                                                <div class="bg-gradient-to-r from-yellow-500 to-orange-500 h-2 rounded-full transition-all duration-300" 
                                                     style="width: <?= $porcentaje ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($productosVendidos) > 8): ?>
                            <div class="mt-4 pt-4 border-t border-dark-500/50 text-center">
                                <p class="text-gray-400 text-sm">
                                    Y <?= count($productosVendidos) - 8 ?> productos más...
                                </p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- VENTAS POR MESA - Diseño Grid con Cards -->
            <div class="bg-dark-600/30 backdrop-blur-xl rounded-xl border border-dark-500/50 overflow-hidden">
                <div class="p-5 border-b border-dark-500/50 bg-gradient-to-r from-blue-600/10 to-purple-600/10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-600/20 rounded-lg flex items-center justify-center">
                                <i class="bi bi-grid-3x3-gap-fill text-blue-400 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Ventas por Mesa</h3>
                                <p class="text-xs text-gray-400">Rendimiento de hoy</p>
                            </div>
                        </div>
                        <div class="bg-blue-600/20 px-3 py-1 rounded-lg">
                            <span class="text-blue-400 text-sm font-bold"><?= count($ventasPorMesa) ?></span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <?php if (empty($ventasPorMesa)): ?>
                        <div class="flex flex-col items-center justify-center py-12">
                            <div class="w-16 h-16 bg-gray-600/20 rounded-full flex items-center justify-center mb-4">
                                <i class="bi bi-table text-3xl text-gray-500"></i>
                            </div>
                            <p class="text-gray-400 text-center mb-1">No hay ventas por mesa hoy</p>
                            <p class="text-gray-500 text-sm">Las ventas aparecerán aquí</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php 
                            $maxVentasMesa = !empty($ventasPorMesa) ? max(array_column($ventasPorMesa, 'total_ventas')) : 1;
                            foreach ($ventasPorMesa as $venta): 
                                $porcentajeMesa = ($venta['total_ventas'] / $maxVentasMesa) * 100;
                            ?>
                                <div class="bg-dark-500/30 hover:bg-dark-500/50 rounded-lg p-4 border border-dark-400/30 transition-colors">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 bg-blue-600/20 rounded-lg flex items-center justify-center">
                                                <i class="bi bi-table text-blue-400"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-white font-semibold"><?= htmlspecialchars($venta['mesa']) ?></h4>
                                                <p class="text-xs text-gray-400"><?= $venta['ordenes_cerradas'] ?> órdenes</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-green-400 font-bold text-lg">$<?= number_format($venta['total_ventas'], 0) ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Barra de rendimiento -->
                                    <div class="w-full bg-dark-700 rounded-full h-1.5">
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-1.5 rounded-full transition-all duration-300" 
                                             style="width: <?= $porcentajeMesa ?>%"></div>
                                    </div>
                                    
                                    <div class="mt-2 flex items-center justify-between text-xs">
                                        <span class="text-gray-500">Rendimiento</span>
                                        <span class="text-gray-400 font-semibold"><?= number_format($porcentajeMesa, 0) ?>%</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Resumen Total -->
                        <div class="mt-5 pt-5 border-t border-dark-500/50">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gradient-to-br from-blue-600/10 to-blue-600/5 rounded-lg p-3 border border-blue-600/20">
                                    <p class="text-gray-400 text-xs mb-1">Total Órdenes</p>
                                    <p class="text-white text-xl font-bold">
                                        <?= array_sum(array_column($ventasPorMesa, 'ordenes_cerradas')) ?>
                                    </p>
                                </div>
                                <div class="bg-gradient-to-br from-green-600/10 to-green-600/5 rounded-lg p-3 border border-green-600/20">
                                    <p class="text-gray-400 text-xs mb-1">Total Ventas</p>
                                    <p class="text-green-400 text-xl font-bold">
                                        $<?= number_format(array_sum(array_column($ventasPorMesa, 'total_ventas')), 0) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Nueva sección: Órdenes Activas -->
<div class="mt-8">
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-xl border border-dark-600/50 overflow-hidden">
    <div class="p-6 border-b border-dark-600/50">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-white flex items-center">
          <i class="bi bi-clock text-orange-400 mr-2"></i>
          Órdenes Activas
        </h3>
        <div class="bg-orange-600 text-white px-3 py-1 rounded-full text-sm font-bold">
          <?= count($ordenesActivasDetalle) ?> abiertas
        </div>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-dark-700/50">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Código</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Mesa</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Productos</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Tiempo Abierta</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Método Pago</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Total Actual</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-dark-700/50">
          <?php if (empty($ordenesActivasDetalle)): ?>
            <tr>
              <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                <div class="flex flex-col items-center">
                  <i class="bi bi-check-circle text-4xl mb-2 text-green-500"></i>
                  <p>No hay órdenes activas</p>
                  <p class="text-sm">¡Todas las órdenes están cerradas!</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($ordenesActivasDetalle as $orden): ?>
              <tr class="hover:bg-dark-700/30 transition-colors duration-200">
                <td class="px-6 py-4 text-sm font-medium text-white">
                  <?= htmlspecialchars($orden['codigo']) ?>
                </td>
                <td class="px-6 py-4 text-sm text-white">
                  <span class="inline-flex items-center">
                    <i class="bi bi-table text-blue-400 mr-2"></i>
                    <?= htmlspecialchars($orden['mesa']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400">
                    <?= $orden['productos_count'] ?> items
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <?php
                  $horas = floor($orden['minutos_abierta'] / 60);
                  $minutos = $orden['minutos_abierta'] % 60;
                  $tiempo_color = $orden['minutos_abierta'] > 60 ? 'text-red-400' : ($orden['minutos_abierta'] > 30 ? 'text-yellow-400' : 'text-green-400');
                  ?>
                  <span class="<?= $tiempo_color ?> font-medium">
                    <?= $horas > 0 ? "{$horas}h " : "" ?><?= $minutos ?>min
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm">
                  <?php
                  $metodo = $orden['metodo_pago'] ?? 'efectivo';
                  $metodo_icon = $metodo == 'tarjeta' ? 'bi-credit-card' : 'bi-cash';
                  $metodo_color = $metodo == 'tarjeta' ? 'text-blue-400' : 'text-green-400';
                  ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/20 <?= $metodo_color ?>">
                    <i class="<?= $metodo_icon ?> mr-1"></i>
                    <?= ucfirst($metodo) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-right text-sm font-bold text-green-400">
                  $<?= number_format($orden['total'], 2) ?>
                </td>
                <td class="px-6 py-4 text-center">
                  <a href="index.php?page=mesa&id=<?= $orden['id'] ?>"
                    class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition-colors">
                    <i class="bi bi-eye mr-1"></i>Ver
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Nueva sección: Órdenes Cerradas Recientes -->
<div class="mt-8">
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-xl border border-dark-600/50 overflow-hidden">
    <!-- Encabezado de la sección -->
    <div class="p-6 border-b border-dark-600/50">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h3 class="text-lg font-semibold text-white flex items-center">
            <i class="bi bi-check-circle text-green-400 mr-2"></i>
            Órdenes Cerradas Recientes
          </h3>
          <p id="ordenes-info" class="text-gray-400 text-sm mt-1">
            <?= $totalOrdenesCerradas ?> órdenes encontradas • Página <?= $paginaOrdenes ?> de <?= $totalPaginasOrdenes ?>
          </p>
        </div>
        <div id="ordenes-periodo" class="bg-green-600 text-white px-3 py-1 rounded-full text-sm font-bold">
          <?= $textoPeriodo ?>
        </div>
      </div>
    </div>

    <!-- Loading indicator -->
    <div id="ordenes-loading" class="hidden p-8 text-center">
      <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-indigo-500 transition ease-in-out duration-150 cursor-not-allowed">
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Cargando órdenes...
      </div>
    </div>

    <!-- Tabla de órdenes -->
    <div id="ordenes-container" class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-dark-700/50">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Código</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Mesa</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Productos</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider" title="Tiempo entre creación y cierre de la orden">
              <i class="bi bi-clock mr-1"></i>
              Tiempo Total
            </th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Método Pago</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Hora</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Total</th>
          </tr>
        </thead>
        <tbody id="ordenes-tbody" class="divide-y divide-dark-700/50">
          <?php if (empty($ordenesCerradasDetalle)): ?>
            <tr>
              <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                <div class="flex flex-col items-center">
                  <i class="bi bi-inbox text-4xl mb-2"></i>
                  <p>No hay órdenes cerradas <?= strtolower($textoPeriodo) ?></p>
                  <p class="text-sm">Las órdenes cerradas aparecerán aquí</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($ordenesCerradasDetalle as $orden): ?>
              <tr class="hover:bg-dark-700/30 transition-colors duration-200">
                <td class="px-6 py-4 text-sm font-medium text-white">
                  <?= htmlspecialchars($orden['codigo']) ?>
                </td>
                <td class="px-6 py-4 text-sm text-white">
                  <span class="inline-flex items-center">
                    <i class="bi bi-table text-blue-400 mr-2"></i>
                    <?= htmlspecialchars($orden['mesa']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-400">
                    <?= $orden['productos_count'] ?> items
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <?php if ($orden['tiempo_total_minutos'] !== null && $orden['tiempo_total_minutos'] >= 0): ?>
                    <?php
                    $horas_total = floor($orden['tiempo_total_minutos'] / 60);
                    $minutos_total = $orden['tiempo_total_minutos'] % 60;
                    ?>
                    <span class="text-blue-400" title="Tiempo entre creación y cierre de la orden">
                      <?= $horas_total > 0 ? "{$horas_total}h " : "" ?><?= $minutos_total ?>min
                    </span>
                  <?php else: ?>
                    <span class="text-gray-500">--</span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-center text-sm">
                  <?php
                  $metodo = $orden['metodo_pago'] ?? 'efectivo';
                  $metodos_config = [
                    'efectivo' => ['icon' => 'bi-cash', 'color' => 'text-green-400'],
                    'debito' => ['icon' => 'bi-credit-card', 'color' => 'text-blue-400'],
                    'credito' => ['icon' => 'bi-credit-card', 'color' => 'text-purple-400'],
                    'transferencia' => ['icon' => 'bi-bank', 'color' => 'text-indigo-400'],
                    'tarjeta' => ['icon' => 'bi-credit-card', 'color' => 'text-blue-400'] // Para compatibilidad
                  ];
                  $metodo_config = $metodos_config[$metodo] ?? $metodos_config['efectivo'];
                  ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/20 <?= $metodo_config['color'] ?>">
                    <i class="<?= $metodo_config['icon'] ?> mr-1"></i>
                    <?= ucfirst($metodo) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <?= date('H:i', strtotime($orden['creada_en'])) ?>
                </td>
                <td class="px-6 py-4 text-right text-sm font-bold text-green-400">
                  $<?= number_format($orden['total'], 2) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Controles de paginación -->
    <div id="ordenes-paginacion" class="p-6 border-t border-dark-700/50" style="<?= $totalPaginasOrdenes <= 1 ? 'display: none;' : '' ?>">
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <!-- Información de paginación -->
        <div id="ordenes-paginacion-info" class="text-sm text-gray-400">
          Mostrando <?= (($paginaOrdenes - 1) * $ordenesPorPagina) + 1 ?> -
          <?= min($paginaOrdenes * $ordenesPorPagina, $totalOrdenesCerradas) ?>
          de <?= $totalOrdenesCerradas ?> órdenes
        </div>

        <!-- Controles de navegación -->
        <nav id="ordenes-controles-paginacion" class="flex items-center space-x-1">
          <!-- Se generará dinámicamente con JavaScript -->
        </nav>
      </div>
    </div>
  </div>
</div>


<script>
  // Función para mostrar/ocultar sección de reportes PDF
  function toggleReportesPDF() {
    const section = document.getElementById('reportes-pdf-section');
    const icon = document.getElementById('pdf-toggle-icon');

    if (section.style.maxHeight === '0px' || section.style.maxHeight === '') {
      // Mostrar
      section.style.maxHeight = '800px';
      section.style.opacity = '1';
      icon.style.transform = 'rotate(180deg)';
    } else {
      // Ocultar
      section.style.maxHeight = '0px';
      section.style.opacity = '0';
      icon.style.transform = 'rotate(0deg)';
    }
  }

  // Funciones para generar reportes PDF
  <?php
  $userInfo = getUserInfo();
  $esAdministrador = $userInfo['rol'] === 'administrador';
  if ($esAdministrador || hasPermission('reportes', 'ver')):
  ?>

    function generarReporteProductos() {
      // Mostrar loading
      const btn = event.target.closest('button');
      const originalContent = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-hourglass-split animate-spin mr-2"></i>Generando...';
      btn.disabled = true;

      // Obtener parámetros de fecha de la URL actual
      const urlParams = new URLSearchParams(window.location.search);
      const fechaDesde = urlParams.get('fecha_desde');
      const fechaHasta = urlParams.get('fecha_hasta');

      // Obtener datos de corte de caja (con fallback a 0 si no están definidos)
      const efectivo = <?php echo isset($ventasEfectivo) ? $ventasEfectivo : 0; ?>;
      const debito = <?php echo isset($ventasDebito) ? $ventasDebito : 0; ?>;
      const credito = <?php echo isset($ventasCredito) ? $ventasCredito : 0; ?>;
      const transferencia = <?php echo isset($ventasTransferencia) ? $ventasTransferencia : 0; ?>;
      const tarjeta = <?php echo isset($ventasTarjeta) ? $ventasTarjeta : 0; ?>;

      console.log('Datos de corte de caja:', { efectivo, debito, credito, transferencia, tarjeta });

      // Construir URL con parámetros
      const params = new URLSearchParams();
      if (fechaDesde && fechaHasta) {
        params.append('fecha_desde', fechaDesde);
        params.append('fecha_hasta', fechaHasta);
      }
      
      // Añadir datos de corte de caja
      params.append('efectivo', efectivo);
      params.append('debito', debito);
      params.append('credito', credito);
      params.append('transferencia', transferencia);
      params.append('tarjeta', tarjeta);
      
      const reportUrl = 'controllers/reportes/reporte_productos_vendidos.php?' + params.toString();
      console.log('URL del reporte:', reportUrl);

      // Opción simple: abrir directamente la URL
      // Esto funciona mejor en algunos navegadores que tienen problemas con blobs
      window.location.href = reportUrl;
      
      // Mostrar mensaje de éxito
      setTimeout(() => {
        btn.innerHTML = '<i class="bi bi-check-circle mr-2"></i>Descargado!';
        btn.classList.add('bg-green-600');

        setTimeout(() => {
          btn.innerHTML = originalContent;
          btn.classList.remove('bg-green-600');
          btn.disabled = false;
        }, 2000);
      }, 500);
    }

    function generarReporteOrdenes() {
      // Mostrar loading
      const btn = event.target.closest('button');
      const originalContent = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-hourglass-split animate-spin mr-2"></i>Generando...';
      btn.disabled = true;

      // Obtener parámetros de fecha de la URL actual
      const urlParams = new URLSearchParams(window.location.search);
      const fechaDesde = urlParams.get('fecha_desde');
      const fechaHasta = urlParams.get('fecha_hasta');

      // Obtener datos de corte de caja (con fallback a 0 si no están definidos)
      const efectivo = <?php echo isset($ventasEfectivo) ? $ventasEfectivo : 0; ?>;
      const debito = <?php echo isset($ventasDebito) ? $ventasDebito : 0; ?>;
      const credito = <?php echo isset($ventasCredito) ? $ventasCredito : 0; ?>;
      const transferencia = <?php echo isset($ventasTransferencia) ? $ventasTransferencia : 0; ?>;
      const tarjeta = <?php echo isset($ventasTarjeta) ? $ventasTarjeta : 0; ?>;

      console.log('Datos de corte de caja (Órdenes):', { efectivo, debito, credito, transferencia, tarjeta });

      // Construir URL con parámetros
      const params = new URLSearchParams();
      if (fechaDesde && fechaHasta) {
        params.append('fecha_desde', fechaDesde);
        params.append('fecha_hasta', fechaHasta);
      }
      
      // Añadir datos de corte de caja
      params.append('efectivo', efectivo);
      params.append('debito', debito);
      params.append('credito', credito);
      params.append('transferencia', transferencia);
      params.append('tarjeta', tarjeta);
      
      const reportUrl = 'controllers/reportes/reporte_ordenes_del_dia.php?' + params.toString();
      console.log('URL del reporte (Órdenes):', reportUrl);

      // Opción simple: abrir directamente la URL
      // Esto funciona mejor en algunos navegadores que tienen problemas con blobs
      window.location.href = reportUrl;
      
      // Mostrar mensaje de éxito
      setTimeout(() => {
        btn.innerHTML = '<i class="bi bi-check-circle mr-2"></i>Descargado!';
        btn.classList.add('bg-green-600');

        setTimeout(() => {
          btn.innerHTML = originalContent;
          btn.classList.remove('bg-green-600');
          btn.disabled = false;
        }, 2000);
      }, 500);
    }

    // Función de exportar original (para compatibilidad)
    function exportarReporte() {
      // Mostrar la sección de reportes si está oculta
      const section = document.getElementById('reportes-pdf-section');
      if (section.style.maxHeight === '0px' || section.style.maxHeight === '') {
        toggleReportesPDF();
      }
    }

  <?php endif; ?>

  // Funciones para filtrar por fecha
  function filtrarPorFecha() {
    const fechaDesde = document.getElementById('fecha-desde').value;
    const fechaHasta = document.getElementById('fecha-hasta').value;

    if (!fechaDesde || !fechaHasta) {
      alert('Por favor selecciona ambas fechas');
      return;
    }

    if (fechaDesde > fechaHasta) {
      alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta"');
      return;
    }

    // Construir URL con parámetros de fecha
    const params = new URLSearchParams();
    params.append('fecha_desde', fechaDesde);
    params.append('fecha_hasta', fechaHasta);

    // Recargar la página con los filtros aplicados
    window.location.href = 'index.php?page=reportes&' + params.toString();
  }

  function limpiarFiltros() {
    // Volver a la página sin filtros
    window.location.href = 'index.php?page=reportes';
  }

  // Función para establecer fechas desde URL y cargar datos
  document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const fechaDesde = urlParams.get('fecha_desde');
    const fechaHasta = urlParams.get('fecha_hasta');
    const paginaOrdenes = urlParams.get('pagina_ordenes') || 1;

    // Establecer valores en los campos de fecha
    if (fechaDesde) {
      const fechaDesdeInput = document.getElementById('fecha-desde');
      if (fechaDesdeInput) {
        fechaDesdeInput.value = fechaDesde;
      }
    }
    if (fechaHasta) {
      const fechaHastaInput = document.getElementById('fecha-hasta');
      if (fechaHastaInput) {
        fechaHastaInput.value = fechaHasta;
      }
    }

    // Si hay filtros de fecha aplicados, cargar las órdenes vía AJAX
    if (fechaDesde && fechaHasta) {
      // Dar tiempo a que se cargue completamente la página
      setTimeout(() => {
        cargarOrdenesAjax(parseInt(paginaOrdenes));
      }, 100);
    }
  });

  // Función para cambiar página de órdenes manteniendo filtros
  function cambiarPaginaOrdenes(pagina) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('pagina_ordenes', pagina);
    window.location.href = 'index.php?' + urlParams.toString();
  }

  // Variables globales para paginación de órdenes
  let paginaActualOrdenes = <?= $paginaOrdenes ?>;
  let totalPaginasOrdenes = <?= $totalPaginasOrdenes ?>;

  // Función para cargar órdenes via AJAX
  function cargarOrdenesAjax(pagina = 1) {
    // Mostrar loading
    document.getElementById('ordenes-loading').classList.remove('hidden');
    document.getElementById('ordenes-container').style.opacity = '0.5';

    // Obtener filtros de fecha actuales
    const urlParams = new URLSearchParams(window.location.search);
    const fechaDesde = urlParams.get('fecha_desde');
    const fechaHasta = urlParams.get('fecha_hasta');

    // Construir URL de la petición
    let ajaxUrl = 'controllers/ajax_ordenes_cerradas.php?pagina=' + pagina;
    if (fechaDesde && fechaHasta) {
      ajaxUrl += '&fecha_desde=' + fechaDesde + '&fecha_hasta=' + fechaHasta;
    }

    fetch(ajaxUrl)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Actualizar contenido de la tabla
          actualizarTablaOrdenes(data.ordenes, data.periodo);

          // Actualizar información de paginación
          actualizarInfoPaginacion(data.paginacion);

          // Actualizar controles de paginación
          actualizarControlesPaginacion(data.paginacion);

          // Actualizar variables globales
          paginaActualOrdenes = data.paginacion.pagina_actual;
          totalPaginasOrdenes = data.paginacion.total_paginas;

        } else {
          console.error('Error al cargar órdenes:', data.error);
          mostrarError('Error al cargar las órdenes');
        }
      })
      .catch(error => {
        console.error('Error de conexión:', error);
        mostrarError('Error de conexión al servidor');
      })
      .finally(() => {
        // Ocultar loading
        document.getElementById('ordenes-loading').classList.add('hidden');
        document.getElementById('ordenes-container').style.opacity = '1';
      });
  }

  // Función para actualizar la tabla de órdenes
  function actualizarTablaOrdenes(ordenes, periodo) {
    const tbody = document.getElementById('ordenes-tbody');

    if (ordenes.length === 0) {
      tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                    <div class="flex flex-col items-center">
                        <i class="bi bi-inbox text-4xl mb-2"></i>
                        <p>No hay órdenes cerradas ${periodo.toLowerCase()}</p>
                        <p class="text-sm">Las órdenes cerradas aparecerán aquí</p>
                    </div>
                </td>
            </tr>
        `;
      return;
    }

    let html = '';
    ordenes.forEach(orden => {
      // Formatear el tiempo total (entre creación y cierre)
      let tiempoTexto = '';
      if (orden.tiempo_total_minutos !== null && orden.tiempo_total_minutos >= 0) {
        const horasTotal = Math.floor(orden.tiempo_total_minutos / 60);
        const minutosTotal = orden.tiempo_total_minutos % 60;
        
        if (horasTotal > 0) {
          tiempoTexto = `${horasTotal}h ${minutosTotal}min`;
        } else {
          tiempoTexto = `${minutosTotal}min`;
        }
      } else {
        tiempoTexto = '<span class="text-gray-500">--</span>';
      }

      const metodo = orden.metodo_pago || 'efectivo';
      const metodosConfig = {
        'efectivo': {
          icon: 'bi-cash',
          color: 'text-green-400'
        },
        'debito': {
          icon: 'bi-credit-card',
          color: 'text-blue-400'
        },
        'credito': {
          icon: 'bi-credit-card',
          color: 'text-purple-400'
        },
        'transferencia': {
          icon: 'bi-bank',
          color: 'text-indigo-400'
        },
        'tarjeta': {
          icon: 'bi-credit-card',
          color: 'text-blue-400'
        }
      };
      const metodoConfig = metodosConfig[metodo] || metodosConfig['efectivo'];

      html += `
            <tr class="hover:bg-dark-700/30 transition-colors duration-200">
                <td class="px-6 py-4 text-sm font-medium text-white">
                    ${orden.codigo}
                </td>
                <td class="px-6 py-4 text-sm text-white">
                    <span class="inline-flex items-center">
                        <i class="bi bi-table text-blue-400 mr-2"></i>
                        ${orden.mesa}
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-400">
                        ${orden.productos_count} items
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                    <span class="text-blue-400" title="Tiempo entre creación y cierre de la orden">
                        ${tiempoTexto}
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/20 ${metodoConfig.color}">
                        <i class="${metodoConfig.icon} mr-1"></i>
                        ${metodo.charAt(0).toUpperCase() + metodo.slice(1)}
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                    ${new Date(orden.creada_en).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}
                </td>
                <td class="px-6 py-4 text-right text-sm font-bold text-green-400">
                    $${parseFloat(orden.total).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
  }

  // Función para actualizar información de paginación
  function actualizarInfoPaginacion(paginacion) {
    const infoElement = document.getElementById('ordenes-info');
    const periodoElement = document.getElementById('ordenes-periodo');
    const paginacionInfoElement = document.getElementById('ordenes-paginacion-info');

    // Verificar si hay filtros activos
    const urlParams = new URLSearchParams(window.location.search);
    const fechaDesde = urlParams.get('fecha_desde');
    const fechaHasta = urlParams.get('fecha_hasta');

    let textoFiltro = '';
    if (fechaDesde && fechaHasta) {
      textoFiltro = ` (filtrado del ${fechaDesde.split('-').reverse().join('/')} al ${fechaHasta.split('-').reverse().join('/')})`;
    }

    infoElement.innerHTML = `
        <span>${paginacion.total_ordenes} órdenes encontradas${textoFiltro}</span>
        <span class="mx-2">•</span>
        <span>Página ${paginacion.pagina_actual} de ${paginacion.total_paginas}</span>
        ${fechaDesde && fechaHasta ? '<span class="ml-2 px-2 py-1 bg-blue-600 text-xs rounded-full"><i class="bi bi-funnel"></i> Filtrado</span>' : ''}
    `;

    paginacionInfoElement.textContent = `Mostrando ${paginacion.desde} - ${paginacion.hasta} de ${paginacion.total_ordenes} órdenes`;

    // Mostrar/ocultar paginación
    const paginacionDiv = document.getElementById('ordenes-paginacion');
    if (paginacion.total_paginas <= 1) {
      paginacionDiv.style.display = 'none';
    } else {
      paginacionDiv.style.display = 'block';
    }
  }

  // Función para actualizar controles de paginación
  function actualizarControlesPaginacion(paginacion) {
    const controlesElement = document.getElementById('ordenes-controles-paginacion');

    let html = '';

    // Botón anterior
    if (paginacion.pagina_actual > 1) {
      html += `
            <button onclick="cargarOrdenesAjax(${paginacion.pagina_actual - 1})" 
                    class="px-3 py-2 rounded-lg bg-dark-700/50 text-gray-300 hover:bg-dark-600/50 hover:text-white transition-colors duration-200">
                <i class="bi bi-chevron-left"></i>
            </button>
        `;
    } else {
      html += `
            <span class="px-3 py-2 rounded-lg bg-dark-600/30 text-gray-500 cursor-not-allowed">
                <i class="bi bi-chevron-left"></i>
            </span>
        `;
    }

    // Números de página
    const inicio = Math.max(1, paginacion.pagina_actual - 2);
    const fin = Math.min(paginacion.total_paginas, paginacion.pagina_actual + 2);

    // Primera página si es necesario
    if (inicio > 1) {
      html += `
            <button onclick="cargarOrdenesAjax(1)" 
                    class="px-3 py-2 rounded-lg bg-dark-700/50 text-gray-300 hover:bg-dark-600/50 hover:text-white transition-colors duration-200">
                1
            </button>
        `;
      if (inicio > 2) {
        html += '<span class="px-2 text-gray-500">...</span>';
      }
    }

    // Páginas del rango
    for (let i = inicio; i <= fin; i++) {
      if (i === paginacion.pagina_actual) {
        html += `
                <span class="px-3 py-2 rounded-lg bg-green-600 text-white font-bold">
                    ${i}
                </span>
            `;
      } else {
        html += `
                <button onclick="cargarOrdenesAjax(${i})" 
                        class="px-3 py-2 rounded-lg bg-dark-700/50 text-gray-300 hover:bg-dark-600/50 hover:text-white transition-colors duration-200">
                    ${i}
                </button>
            `;
      }
    }

    // Última página si es necesario
    if (fin < paginacion.total_paginas) {
      if (fin < paginacion.total_paginas - 1) {
        html += '<span class="px-2 text-gray-500">...</span>';
      }
      html += `
            <button onclick="cargarOrdenesAjax(${paginacion.total_paginas})" 
                    class="px-3 py-2 rounded-lg bg-dark-700/50 text-gray-300 hover:bg-dark-600/50 hover:text-white transition-colors duration-200">
                ${paginacion.total_paginas}
            </button>
        `;
    }

    // Botón siguiente
    if (paginacion.pagina_actual < paginacion.total_paginas) {
      html += `
            <button onclick="cargarOrdenesAjax(${paginacion.pagina_actual + 1})" 
                    class="px-3 py-2 rounded-lg bg-dark-700/50 text-gray-300 hover:bg-dark-600/50 hover:text-white transition-colors duration-200">
                <i class="bi bi-chevron-right"></i>
            </button>
        `;
    } else {
      html += `
            <span class="px-3 py-2 rounded-lg bg-dark-600/30 text-gray-500 cursor-not-allowed">
                <i class="bi bi-chevron-right"></i>
            </span>
        `;
    }

    controlesElement.innerHTML = html;
  }

  // Función para mostrar errores
  function mostrarError(mensaje) {
    const tbody = document.getElementById('ordenes-tbody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="px-6 py-8 text-center text-red-400">
                <div class="flex flex-col items-center">
                    <i class="bi bi-exclamation-triangle text-4xl mb-2"></i>
                    <p>${mensaje}</p>
                    <button onclick="cargarOrdenesAjax(paginaActualOrdenes)" class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Reintentar
                    </button>
                </div>
            </td>
        </tr>
    `;
  }

  // Modificar la función de filtrar por fecha para recargar solo órdenes via AJAX
  function filtrarPorFecha() {
    const fechaDesde = document.getElementById('fecha-desde').value;
    const fechaHasta = document.getElementById('fecha-hasta').value;

    if (!fechaDesde || !fechaHasta) {
      alert('Por favor selecciona ambas fechas');
      return;
    }

    if (fechaDesde > fechaHasta) {
      alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta"');
      return;
    }

    // Actualizar URL sin recargar toda la página
    const params = new URLSearchParams(window.location.search);
    params.set('fecha_desde', fechaDesde);
    params.set('fecha_hasta', fechaHasta);
    params.delete('pagina_ordenes'); // Resetear a página 1

    // Actualizar URL en el navegador
    const newUrl = 'index.php?page=reportes&' + params.toString();
    window.history.pushState({}, '', newUrl);

    // Solo recargar las órdenes via AJAX (página 1)
    cargarOrdenesAjax(1);

    // Actualizar el badge de período en la sección de órdenes
    const textoPeriodo = `del ${fechaDesde.split('-').reverse().join('/')} al ${fechaHasta.split('-').reverse().join('/')}`;
    document.getElementById('ordenes-periodo').textContent = textoPeriodo;

    // Para el resto de las secciones, necesitamos recargar la página completa
    // Esto lo haremos solo si el usuario lo solicita específicamente
    mostrarOpcionRecargaCompleta();
  }

  // Función para mostrar opción de recarga completa
  function mostrarOpcionRecargaCompleta() {
    // Crear un toast/notificación para preguntar si quiere actualizar todo
    const existingToast = document.getElementById('filter-toast');
    if (existingToast) {
      existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.id = 'filter-toast';
    toast.className = 'fixed top-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50 max-w-sm';
    toast.innerHTML = `
        <div class="flex items-start gap-3">
            <i class="bi bi-info-circle text-xl mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold mb-2">Filtro aplicado</p>
                <p class="text-sm text-blue-100 mb-3">Las órdenes se han actualizado. ¿Deseas actualizar también el resto de reportes?</p>
                <div class="flex gap-2">
                    <button onclick="recargarTodosLosReportes()" class="px-3 py-1 bg-white text-blue-600 rounded text-sm font-semibold hover:bg-blue-50 transition-colors">
                        Sí, actualizar todo
                    </button>
                    <button onclick="cerrarToast()" class="px-3 py-1 bg-blue-700 text-white rounded text-sm hover:bg-blue-800 transition-colors">
                        Solo órdenes
                    </button>
                </div>
            </div>
            <button onclick="cerrarToast()" class="text-blue-200 hover:text-white">
                <i class="bi bi-x text-lg"></i>
            </button>
        </div>
    `;

    document.body.appendChild(toast);

    // Auto-cerrar después de 10 segundos
    setTimeout(() => {
      if (document.getElementById('filter-toast')) {
        cerrarToast();
      }
    }, 10000);
  }

  // Función para recargar todos los reportes
  function recargarTodosLosReportes() {
    cerrarToast();
    window.location.reload();
  }

  // Función para limpiar filtros de fecha
  function limpiarFiltros() {
    // Limpiar campos de fecha
    document.getElementById('fecha-desde').value = '';
    document.getElementById('fecha-hasta').value = '';

    // Limpiar parámetros de la URL
    const params = new URLSearchParams(window.location.search);
    params.delete('fecha_desde');
    params.delete('fecha_hasta');
    params.delete('pagina_ordenes');

    // Actualizar URL
    const newUrl = 'index.php?page=reportes&' + params.toString();
    window.history.pushState({}, '', newUrl);

    // Recargar órdenes (página 1)
    cargarOrdenesAjax(1);

    // Actualizar texto del período
    document.getElementById('ordenes-periodo').textContent = 'del día de hoy';

    // Mostrar mensaje de confirmación
    mostrarMensajeExito('Filtros eliminados. Mostrando órdenes del día de hoy.');
  }

  // Función para mostrar mensaje de éxito
  function mostrarMensajeExito(mensaje) {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 bg-green-600 text-white p-3 rounded-lg shadow-lg z-50 transition-all duration-300';
    toast.innerHTML = `
        <div class="flex items-center gap-2">
            <i class="bi bi-check-circle"></i>
            <span>${mensaje}</span>
        </div>
    `;

    document.body.appendChild(toast);

    // Auto-remover después de 3 segundos
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // Función para cerrar el toast
  function cerrarToast() {
    const toast = document.getElementById('filter-toast');
    if (toast) {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      setTimeout(() => toast.remove(), 300);
    }
  }
</script>