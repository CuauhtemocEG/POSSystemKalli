<?php
error_reporting(0);
ini_set('display_errors', '0');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '120');

if (ob_get_level()) {
    ob_clean();
}
ob_start();

require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../fpdf/fpdf.php';

$pdo = conexion();

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('reporte_analisis_promociones.php fatal: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
        }
        echo 'Error interno al generar el reporte de promociones.';
    }
});

function normalizarFecha($fecha, $default)
{
    if (is_string($fecha) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha;
    }
    return $default;
}

function limpiarTexto($texto)
{
    $texto = (string)$texto;
    $texto = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'ü', 'Ü', 'ç', 'Ç'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N', 'u', 'U', 'c', 'C'],
        $texto
    );

    return preg_replace('/[^a-zA-Z0-9\s\.,\-:$(){}\/#|@%&\+]/', '', $texto);
}

function tipoPromoTexto($tipo)
{
    $mapa = [
        '2x1' => '2x1',
        '3x2' => '3x2',
        'descuento_porcentaje' => 'Descuento %',
        'descuento_fijo' => 'Descuento Fijo',
        'descuento_manual_orden' => 'Descuento Manual',
        'descuento_personal' => 'Descuento Personal',
    ];

    return $mapa[$tipo] ?? ucfirst((string)$tipo);
}

$hoy = date('Y-m-d');
$fechaDesde = normalizarFecha($_GET['fecha_desde'] ?? null, $hoy);
$fechaHasta = normalizarFecha($_GET['fecha_hasta'] ?? null, $hoy);

if ($fechaDesde > $fechaHasta) {
    $tmp = $fechaDesde;
    $fechaDesde = $fechaHasta;
    $fechaHasta = $tmp;
}

class ReportePromocionesPDF extends FPDF
{
    private $fechaDesde;
    private $fechaHasta;

    public function setFechas($desde, $hasta)
    {
        $this->fechaDesde = $desde;
        $this->fechaHasta = $hasta;
    }

    function Header()
    {
        $logoPath = __DIR__ . '/../../assets/img/LogoBlack.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 12, 8, 32);
        }

        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 8, limpiarTexto('Kalli Jaguar POS'), 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(0, 5, limpiarTexto('Reporte de Analisis de Promociones'), 0, 1, 'C');

        $periodo = 'Periodo: ' . date('d/m/Y', strtotime($this->fechaDesde)) . ' al ' . date('d/m/Y', strtotime($this->fechaHasta));
        $this->Cell(0, 5, limpiarTexto($periodo), 0, 1, 'C');

        $this->SetDrawColor(229, 231, 235);
        $this->SetLineWidth(0.5);
        $this->Line(10, 34, 200, 34);

        $this->Ln(6);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(127, 140, 141);
        $texto = 'Generado: ' . date('d/m/Y H:i:s') . '  |  Pagina ' . $this->PageNo() . ' de {nb}';
        $this->Cell(0, 10, limpiarTexto($texto), 0, 0, 'C');
    }

    function Seccion($titulo)
    {
        $this->SetFillColor(99, 102, 241);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, limpiarTexto($titulo), 0, 1, 'L', true);
        $this->Ln(2);
    }

    function FilaClaveValor($clave, $valor, $fill = false)
    {
        if ($fill) {
            $this->SetFillColor(248, 250, 252);
        }

        $this->SetTextColor(31, 41, 55);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(120, 7, limpiarTexto($clave), 1, 0, 'L', $fill);

        $this->SetFont('Arial', '', 9);
        $this->Cell(70, 7, limpiarTexto($valor), 1, 1, 'R', $fill);
    }
}

$params = [
    ':fecha_desde' => $fechaDesde,
    ':fecha_hasta' => $fechaHasta,
];

$resumen = [];
try {
    $stmtResumen = $pdo->prepare("
        SELECT
            COUNT(*) AS total_ordenes_cerradas,
            COALESCE(SUM(COALESCE(o.subtotal, o.total)), 0) AS subtotal_total,
            COALESCE(SUM(o.total), 0) AS total_cobrado,
            COALESCE(SUM(
                CASE
                    WHEN COALESCE(o.aplicar_descuento_porcentaje, 0) = 1
                         AND COALESCE(o.descuento_porcentaje_valor, 0) > 0
                    THEN (COALESCE(o.subtotal, o.total) * COALESCE(o.descuento_porcentaje_valor, 0) / 100)
                    ELSE 0
                END
            ), 0) AS monto_descuento_manual,
            SUM(
                CASE
                    WHEN COALESCE(o.aplicar_descuento_porcentaje, 0) = 1
                         AND COALESCE(o.descuento_porcentaje_valor, 0) > 0
                    THEN 1 ELSE 0
                END
            ) AS ordenes_descuento_manual
        FROM ordenes o
        WHERE o.estado = 'cerrada'
          AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
    ");
    $stmtResumen->execute($params);
    $resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $stmtResumenSimple = $pdo->prepare("
        SELECT
            COUNT(*) AS total_ordenes_cerradas,
            COALESCE(SUM(o.total), 0) AS subtotal_total,
            COALESCE(SUM(o.total), 0) AS total_cobrado,
            0 AS monto_descuento_manual,
            0 AS ordenes_descuento_manual
        FROM ordenes o
        WHERE o.estado = 'cerrada'
          AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
    ");
    $stmtResumenSimple->execute($params);
    $resumen = $stmtResumenSimple->fetch(PDO::FETCH_ASSOC) ?: [];
}

$promoGlobal = [];
try {
    $stmtPromoGlobal = $pdo->prepare("
        SELECT
            COUNT(DISTINCT pa.orden_id) AS ordenes_con_promocion,
            COALESCE(SUM(pa.descuento_aplicado), 0) AS descuento_promociones
        FROM promociones_aplicadas pa
        INNER JOIN ordenes o ON o.id = pa.orden_id
        WHERE o.estado = 'cerrada'
          AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
    ");
    $stmtPromoGlobal->execute($params);
    $promoGlobal = $stmtPromoGlobal->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $promoGlobal = [
        'ordenes_con_promocion' => 0,
        'descuento_promociones' => 0,
    ];
}

$subtotalTotal = (float)($resumen['subtotal_total'] ?? 0);
$totalCobrado = (float)($resumen['total_cobrado'] ?? 0);
$descuentoPromociones = (float)($promoGlobal['descuento_promociones'] ?? 0);
$descuentoManual = (float)($resumen['monto_descuento_manual'] ?? 0);
$impactoTotal = $descuentoPromociones + $descuentoManual;
$ordenesCerradas = (int)($resumen['total_ordenes_cerradas'] ?? 0);
$ordenesConPromocion = (int)($promoGlobal['ordenes_con_promocion'] ?? 0);
$ordenesConManual = (int)($resumen['ordenes_descuento_manual'] ?? 0);

$promocionesDetalle = [];
try {
    $stmtTipos = $pdo->prepare("
        SELECT
            p.tipo,
            p.nombre,
            COUNT(pa.id) AS aplicaciones,
            COUNT(DISTINCT pa.orden_id) AS ordenes_afectadas,
            COALESCE(SUM(pa.descuento_aplicado), 0) AS descuento_total,
            COALESCE(AVG(pa.descuento_aplicado), 0) AS descuento_promedio
        FROM promociones_aplicadas pa
        INNER JOIN promociones p ON p.id = pa.promocion_id
        INNER JOIN ordenes o ON o.id = pa.orden_id
        WHERE o.estado = 'cerrada'
          AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
        GROUP BY p.id, p.nombre, p.tipo
        ORDER BY descuento_total DESC
        LIMIT 120
    ");
    $stmtTipos->execute($params);
    $promocionesDetalle = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $promocionesDetalle = [];
}

try {
    $stmtManualDetalle = $pdo->prepare(" 
        SELECT
            COUNT(*) AS aplicaciones,
            COUNT(*) AS ordenes_afectadas,
            COALESCE(SUM(ROUND(COALESCE(o.subtotal, o.total) * COALESCE(o.descuento_porcentaje_valor, 0) / 100, 2)), 0) AS descuento_total,
            COALESCE(AVG(ROUND(COALESCE(o.subtotal, o.total) * COALESCE(o.descuento_porcentaje_valor, 0) / 100, 2)), 0) AS descuento_promedio
        FROM ordenes o
        WHERE o.estado = 'cerrada'
          AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
          AND COALESCE(o.aplicar_descuento_porcentaje, 0) = 1
          AND COALESCE(o.descuento_porcentaje_valor, 0) > 0
    ");
    $stmtManualDetalle->execute($params);
    $manualDetalle = $stmtManualDetalle->fetch(PDO::FETCH_ASSOC) ?: [];

    if ((int)($manualDetalle['aplicaciones'] ?? 0) > 0) {
        $promocionesDetalle[] = [
            'tipo' => 'descuento_manual_orden',
            'nombre' => 'Descuento manual por orden',
            'aplicaciones' => (int)$manualDetalle['aplicaciones'],
            'ordenes_afectadas' => (int)$manualDetalle['ordenes_afectadas'],
            'descuento_total' => (float)$manualDetalle['descuento_total'],
            'descuento_promedio' => (float)$manualDetalle['descuento_promedio'],
        ];
    }
} catch (Exception $e) {
}

if (!empty($promocionesDetalle)) {
    usort($promocionesDetalle, function ($a, $b) {
        return ((float)$b['descuento_total']) <=> ((float)$a['descuento_total']);
    });
}

$tiposAgrupados = [];
try {
    $stmtTiposAgrupados = $pdo->prepare("
        SELECT
            p.tipo,
            COUNT(pa.id) AS aplicaciones,
            COUNT(DISTINCT pa.orden_id) AS ordenes_afectadas,
            COALESCE(SUM(pa.descuento_aplicado), 0) AS descuento_total
        FROM promociones_aplicadas pa
        INNER JOIN promociones p ON p.id = pa.promocion_id
        INNER JOIN ordenes o ON o.id = pa.orden_id
        WHERE o.estado = 'cerrada'
          AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
        GROUP BY p.tipo
        ORDER BY descuento_total DESC
    ");
    $stmtTiposAgrupados->execute($params);
    $tiposAgrupados = $stmtTiposAgrupados->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tiposAgrupados = [];
}

try {
    $stmtManualTipo = $pdo->prepare(" 
        SELECT
            COUNT(*) AS aplicaciones,
            COUNT(*) AS ordenes_afectadas,
            COALESCE(SUM(ROUND(COALESCE(o.subtotal, o.total) * COALESCE(o.descuento_porcentaje_valor, 0) / 100, 2)), 0) AS descuento_total
        FROM ordenes o
        WHERE o.estado = 'cerrada'
          AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
          AND COALESCE(o.aplicar_descuento_porcentaje, 0) = 1
          AND COALESCE(o.descuento_porcentaje_valor, 0) > 0
    ");
    $stmtManualTipo->execute($params);
    $manualTipo = $stmtManualTipo->fetch(PDO::FETCH_ASSOC) ?: [];

    if ((int)($manualTipo['aplicaciones'] ?? 0) > 0) {
        $tiposAgrupados[] = [
            'tipo' => 'descuento_manual_orden',
            'aplicaciones' => (int)$manualTipo['aplicaciones'],
            'ordenes_afectadas' => (int)$manualTipo['ordenes_afectadas'],
            'descuento_total' => (float)$manualTipo['descuento_total'],
        ];
    }
} catch (Exception $e) {
}

if (!empty($tiposAgrupados)) {
    usort($tiposAgrupados, function ($a, $b) {
        return ((float)$b['descuento_total']) <=> ((float)$a['descuento_total']);
    });
}

$ordenesImpactadas = [];
try {
    $stmtOrdenesImpactadas = $pdo->prepare("
        SELECT
            o.id,
            o.codigo,
            m.nombre AS mesa,
            o.creada_en,
            COALESCE(o.subtotal, o.total) AS subtotal,
            o.total,
            (COALESCE(o.subtotal, o.total) - o.total) AS impacto_precio,
            (
                SELECT COALESCE(SUM(pa2.descuento_aplicado), 0)
                FROM promociones_aplicadas pa2
                WHERE pa2.orden_id = o.id
            ) AS descuento_promociones,
            CASE
                WHEN COALESCE(o.aplicar_descuento_porcentaje, 0) = 1
                     AND COALESCE(o.descuento_porcentaje_valor, 0) > 0
                THEN ROUND(COALESCE(o.subtotal, o.total) * COALESCE(o.descuento_porcentaje_valor, 0) / 100, 2)
                ELSE 0
            END AS descuento_manual,
            COALESCE(o.descuento_porcentaje_valor, 0) AS porcentaje_manual,
            SUBSTRING(
                (
                    SELECT GROUP_CONCAT(
                        DISTINCT CONCAT(
                            p2.nombre,
                            ' [',
                            p2.tipo,
                            '] -$',
                            ROUND(pa2.descuento_aplicado, 2)
                        )
                        ORDER BY p2.nombre
                        SEPARATOR ' | '
                    )
                    FROM promociones_aplicadas pa2
                    INNER JOIN promociones p2 ON p2.id = pa2.promocion_id
                    WHERE pa2.orden_id = o.id
                ),
                1,
                220
            ) AS promociones,
            SUBSTRING(
                (
                    SELECT GROUP_CONCAT(
                        DISTINCT prod2.nombre
                        ORDER BY prod2.nombre
                        SEPARATOR ', '
                    )
                    FROM orden_productos op2
                    INNER JOIN productos prod2 ON prod2.id = op2.producto_id
                    WHERE op2.orden_id = o.id
                      AND (op2.cancelado = 0 OR op2.cancelado IS NULL)
                      AND EXISTS (
                          SELECT 1
                          FROM promociones_aplicadas pa3
                          WHERE pa3.orden_id = o.id
                            AND (
                                pa3.productos_afectados IS NULL
                                OR pa3.productos_afectados = ''
                                OR FIND_IN_SET(op2.id, pa3.productos_afectados) > 0
                            )
                      )
                ),
                1,
                240
            ) AS productos_descuento,
            SUBSTRING(
                (
                    SELECT GROUP_CONCAT(
                        DISTINCT prod3.nombre
                        ORDER BY prod3.nombre
                        SEPARATOR ', '
                    )
                    FROM orden_productos op3
                    INNER JOIN productos prod3 ON prod3.id = op3.producto_id
                    WHERE op3.orden_id = o.id
                      AND (op3.cancelado = 0 OR op3.cancelado IS NULL)
                ),
                1,
                240
            ) AS productos_orden
        FROM ordenes o
        INNER JOIN mesas m ON m.id = o.mesa_id
        WHERE o.estado = 'cerrada'
          AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
          AND (
              (
                  SELECT COALESCE(SUM(pa4.descuento_aplicado), 0)
                  FROM promociones_aplicadas pa4
                  WHERE pa4.orden_id = o.id
              ) > 0
              OR (
                  COALESCE(o.aplicar_descuento_porcentaje, 0) = 1
                  AND COALESCE(o.descuento_porcentaje_valor, 0) > 0
              )
          )
        ORDER BY o.creada_en DESC
        LIMIT 120
    ");
    $stmtOrdenesImpactadas->execute($params);
    $ordenesImpactadas = $stmtOrdenesImpactadas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $stmtOrdenesImpactadasSimple = $pdo->prepare("
            SELECT
                o.id,
                o.codigo,
                m.nombre AS mesa,
                o.creada_en,
                o.total AS subtotal,
                o.total,
                0 AS impacto_precio,
                0 AS descuento_promociones,
                0 AS descuento_manual,
                0 AS porcentaje_manual,
                '' AS promociones,
                '' AS productos_descuento,
                GROUP_CONCAT(DISTINCT prod.nombre ORDER BY prod.nombre SEPARATOR ', ') AS productos_orden
            FROM ordenes o
            INNER JOIN mesas m ON m.id = o.mesa_id
            LEFT JOIN orden_productos op ON op.orden_id = o.id
            LEFT JOIN productos prod ON prod.id = op.producto_id
            WHERE o.estado = 'cerrada'
              AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
            GROUP BY o.id, o.codigo, m.nombre, o.creada_en, o.total
            ORDER BY o.creada_en DESC
            LIMIT 100
        ");
        $stmtOrdenesImpactadasSimple->execute($params);
        $ordenesImpactadas = $stmtOrdenesImpactadasSimple->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $ordenesImpactadas = [];
    }
}

$productosImpactados = [];
try {
    $stmtProductosImpactados = $pdo->prepare("
        SELECT
            pr.nombre AS producto,
            p.tipo,
            COUNT(*) AS veces_con_promocion,
            COALESCE(SUM(op.cantidad), 0) AS cantidad_total
        FROM promociones_aplicadas pa
        INNER JOIN promociones p ON p.id = pa.promocion_id
        INNER JOIN ordenes o ON o.id = pa.orden_id
        INNER JOIN orden_productos op ON op.orden_id = pa.orden_id
            AND (pa.productos_afectados IS NULL OR pa.productos_afectados = '' OR FIND_IN_SET(op.id, pa.productos_afectados) > 0)
            AND (op.cancelado = 0 OR op.cancelado IS NULL)
        INNER JOIN productos pr ON pr.id = op.producto_id
        WHERE o.estado = 'cerrada'
          AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
        GROUP BY pr.id, pr.nombre, p.tipo
        ORDER BY veces_con_promocion DESC, cantidad_total DESC
        LIMIT 25
    ");
    $stmtProductosImpactados->execute($params);
    $productosImpactados = $stmtProductosImpactados->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $stmtProductosSimple = $pdo->prepare("
            SELECT
                pr.nombre AS producto,
                'general' AS tipo,
                COUNT(*) AS veces_con_promocion,
                COALESCE(SUM(op.cantidad), 0) AS cantidad_total
            FROM orden_productos op
            INNER JOIN ordenes o ON o.id = op.orden_id
            INNER JOIN productos pr ON pr.id = op.producto_id
            WHERE o.estado = 'cerrada'
              AND DATE(o.creada_en) BETWEEN :fecha_desde AND :fecha_hasta
            GROUP BY pr.id, pr.nombre
            ORDER BY cantidad_total DESC
            LIMIT 25
        ");
        $stmtProductosSimple->execute($params);
        $productosImpactados = $stmtProductosSimple->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $productosImpactados = [];
    }
}

$pdf = new ReportePromocionesPDF('P', 'mm', 'A4');
$pdf->setFechas($fechaDesde, $fechaHasta);
$pdf->AliasNbPages();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$pdf->Seccion('1) Resumen de impacto de promociones');
$pdf->FilaClaveValor('Ordenes cerradas en el periodo', number_format($ordenesCerradas), true);
$pdf->FilaClaveValor('Ordenes con promociones aplicadas', number_format($ordenesConPromocion), false);
$pdf->FilaClaveValor('Ordenes con descuento manual', number_format($ordenesConManual), true);
$pdf->FilaClaveValor('Subtotal sin descuentos', '$' . number_format($subtotalTotal, 2), false);
$pdf->FilaClaveValor('Total real cobrado', '$' . number_format($totalCobrado, 2), true);
$pdf->FilaClaveValor('Impacto total en precios (ahorro cliente)', '$' . number_format($impactoTotal, 2), false);
$pdf->FilaClaveValor('Impacto por promociones configuradas', '$' . number_format($descuentoPromociones, 2), true);
$pdf->FilaClaveValor('Impacto por descuento manual', '$' . number_format($descuentoManual, 2), false);

$porcentajeImpacto = $subtotalTotal > 0 ? (($impactoTotal / $subtotalTotal) * 100) : 0;
$pdf->FilaClaveValor('Porcentaje total de impacto', number_format($porcentajeImpacto, 2) . '%', true);

$pdf->Ln(4);
$pdf->Seccion('2) Tipos de promociones y efecto economico');

if (!empty($tiposAgrupados)) {
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(229, 231, 235);
    $pdf->SetTextColor(31, 41, 55);
    $pdf->Cell(55, 8, 'Tipo', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Aplicaciones', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Ordenes', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Descuento', 1, 0, 'R', true);
    $pdf->Cell(30, 8, '% Ventas', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 9);
    foreach ($tiposAgrupados as $row) {
        $pct = $totalCobrado > 0 ? (((float)$row['descuento_total'] / $totalCobrado) * 100) : 0;
        $pdf->Cell(55, 7, limpiarTexto(tipoPromoTexto($row['tipo'])), 1, 0, 'L');
        $pdf->Cell(35, 7, number_format($row['aplicaciones']), 1, 0, 'C');
        $pdf->Cell(35, 7, number_format($row['ordenes_afectadas']), 1, 0, 'C');
        $pdf->Cell(35, 7, '$' . number_format($row['descuento_total'], 2), 1, 0, 'R');
        $pdf->Cell(30, 7, number_format($pct, 2) . '%', 1, 1, 'R');
    }
} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(127, 140, 141);
    $pdf->Cell(0, 7, limpiarTexto('Sin datos de tipos de promociones en el periodo.'), 0, 1, 'L');
}

$pdf->Ln(3);
if (!empty($promocionesDetalle)) {
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(229, 231, 235);
    $pdf->SetTextColor(31, 41, 55);
    $pdf->Cell(70, 8, 'Promocion', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Tipo', 1, 0, 'L', true);
    $pdf->Cell(25, 8, 'Ordenes', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Descuento', 1, 0, 'R', true);
    $pdf->Cell(30, 8, 'Promedio', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 8);
    foreach ($promocionesDetalle as $promo) {
        if ($pdf->GetY() > 265) {
            $pdf->AddPage();
            $pdf->Seccion('2) Tipos de promociones y efecto economico (continuacion)');
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(229, 231, 235);
            $pdf->SetTextColor(31, 41, 55);
            $pdf->Cell(70, 8, 'Promocion', 1, 0, 'L', true);
            $pdf->Cell(35, 8, 'Tipo', 1, 0, 'L', true);
            $pdf->Cell(25, 8, 'Ordenes', 1, 0, 'C', true);
            $pdf->Cell(30, 8, 'Descuento', 1, 0, 'R', true);
            $pdf->Cell(30, 8, 'Promedio', 1, 1, 'R', true);
            $pdf->SetFont('Arial', '', 8);
        }

        $pdf->Cell(70, 7, limpiarTexto(substr($promo['nombre'], 0, 45)), 1, 0, 'L');
        $pdf->Cell(35, 7, limpiarTexto(tipoPromoTexto($promo['tipo'])), 1, 0, 'L');
        $pdf->Cell(25, 7, number_format($promo['ordenes_afectadas']), 1, 0, 'C');
        $pdf->Cell(30, 7, '$' . number_format($promo['descuento_total'], 2), 1, 0, 'R');
        $pdf->Cell(30, 7, '$' . number_format($promo['descuento_promedio'], 2), 1, 1, 'R');
    }
} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(127, 140, 141);
    $pdf->Cell(0, 7, limpiarTexto('Sin desglose de promociones para este periodo.'), 0, 1, 'L');
}

$pdf->AddPage();
$pdf->Seccion('3) Ordenes con promociones y descuentos');

if (empty($ordenesImpactadas)) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(127, 140, 141);
    $pdf->Cell(0, 8, limpiarTexto('No se encontraron ordenes con promociones o descuento manual en el periodo.'), 0, 1, 'C');
} else {
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(229, 231, 235);
    $pdf->SetTextColor(31, 41, 55);
    $pdf->Cell(24, 7, 'Codigo', 1, 0, 'C', true);
    $pdf->Cell(18, 7, 'Mesa', 1, 0, 'L', true);
    $pdf->Cell(40, 7, 'Promo aplicada', 1, 0, 'L', true);
    $pdf->Cell(50, 7, 'Productos descontados', 1, 0, 'L', true);
    $pdf->Cell(18, 7, 'Sin desc.', 1, 0, 'R', true);
    $pdf->Cell(18, 7, 'Desc.', 1, 0, 'R', true);
    $pdf->Cell(22, 7, 'Total', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 7);
    foreach ($ordenesImpactadas as $orden) {
        $descuentoManual = (float)$orden['descuento_manual'];
        $descuentoPromo = (float)$orden['descuento_promociones'];

        $promocionesItems = array_values(array_filter(array_map('trim', explode('|', (string)($orden['promociones'] ?? '')))));
        if ($descuentoManual > 0) {
            $promocionesItems[] = 'Descuento manual ' . number_format((float)$orden['porcentaje_manual'], 1) . '% -$' . number_format($descuentoManual, 2);
        }
        if (empty($promocionesItems)) {
            $promocionesItems = ['Sin promociones'];
        }

        $limitePromos = 4;
        if (count($promocionesItems) > $limitePromos) {
            $promocionesItems = array_slice($promocionesItems, 0, $limitePromos);
            $promocionesItems[] = '...';
        }

        $promocionesLista = '';
        foreach ($promocionesItems as $index => $promoItem) {
            $linea = '- ' . limpiarTexto(substr((string)$promoItem, 0, 44));
            $promocionesLista .= ($index === 0 ? '' : "\n") . $linea;
        }

        if ($descuentoManual > 0 && $descuentoPromo <= 0) {
            $productosLista = '- No aplica (descuento manual)';
        } else {
            $productosRaw = trim((string)($orden['productos_descuento'] ?? ''));
            if ($productosRaw === '') {
                $productosRaw = 'Sin detalle';
            }

            $productosArr = array_values(array_filter(array_map('trim', explode(',', $productosRaw))));
            if (empty($productosArr)) {
                $productosArr = ['Sin detalle'];
            }

            $limiteProductos = 6;
            if (count($productosArr) > $limiteProductos) {
                $productosArr = array_slice($productosArr, 0, $limiteProductos);
                $productosArr[] = '...';
            }

            $productosLista = '';
            foreach ($productosArr as $index => $productoItem) {
                $linea = '- ' . limpiarTexto(substr((string)$productoItem, 0, 36));
                $productosLista .= ($index === 0 ? '' : "\n") . $linea;
            }
        }

        $lineasPromo = substr_count($promocionesLista, "\n") + 1;
        $lineasProductos = substr_count($productosLista, "\n") + 1;
        $altoFila = max(8, max($lineasPromo, $lineasProductos) * 4);

        if ($pdf->GetY() + $altoFila > 275) {
            $pdf->AddPage();
            $pdf->Seccion('3) Ordenes con promociones y descuentos (continuacion)');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(229, 231, 235);
            $pdf->SetTextColor(31, 41, 55);
            $pdf->Cell(24, 7, 'Codigo', 1, 0, 'C', true);
            $pdf->Cell(18, 7, 'Mesa', 1, 0, 'L', true);
            $pdf->Cell(40, 7, 'Promo aplicada', 1, 0, 'L', true);
            $pdf->Cell(50, 7, 'Productos descontados', 1, 0, 'L', true);
            $pdf->Cell(18, 7, 'Sin desc.', 1, 0, 'R', true);
            $pdf->Cell(18, 7, 'Desc.', 1, 0, 'R', true);
            $pdf->Cell(22, 7, 'Total', 1, 1, 'R', true);
            $pdf->SetFont('Arial', '', 7);
        }

        $descuentoTotalOrden = $descuentoPromo + $descuentoManual;

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Cell(24, $altoFila, limpiarTexto(substr($orden['codigo'], 0, 15)), 1, 0, 'C');
        $pdf->Cell(18, $altoFila, limpiarTexto(substr($orden['mesa'], 0, 10)), 1, 0, 'L');

        $pdf->SetXY($x + 42, $y);
        $pdf->MultiCell(40, 4, $promocionesLista, 1, 'L');

        $pdf->SetXY($x + 82, $y);
        $pdf->MultiCell(50, 4, $productosLista, 1, 'L');

        $pdf->SetXY($x + 132, $y);
        $pdf->Cell(18, $altoFila, '$' . number_format((float)$orden['subtotal'], 2), 1, 0, 'R');
        $pdf->Cell(18, $altoFila, '$' . number_format($descuentoTotalOrden, 2), 1, 0, 'R');
        $pdf->Cell(22, $altoFila, '$' . number_format((float)$orden['total'], 2), 1, 0, 'R');

        $pdf->SetXY($x, $y + $altoFila);
    }
}

$pdf->Ln(3);
$pdf->Seccion('4) Productos relacionados con promociones');

if (empty($productosImpactados)) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(127, 140, 141);
    $pdf->Cell(0, 8, limpiarTexto('No hay productos asociados a promociones en el periodo.'), 0, 1, 'C');
} else {
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(229, 231, 235);
    $pdf->SetTextColor(31, 41, 55);
    $pdf->Cell(75, 8, 'Producto', 1, 0, 'L', true);
    $pdf->Cell(40, 8, 'Tipo promocion', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'Veces promo', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Cantidad total', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 8);
    foreach ($productosImpactados as $prod) {
        if ($pdf->GetY() > 266) {
            $pdf->AddPage();
            $pdf->Seccion('4) Productos relacionados con promociones (continuacion)');
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(229, 231, 235);
            $pdf->SetTextColor(31, 41, 55);
            $pdf->Cell(75, 8, 'Producto', 1, 0, 'L', true);
            $pdf->Cell(40, 8, 'Tipo promocion', 1, 0, 'L', true);
            $pdf->Cell(35, 8, 'Veces promo', 1, 0, 'C', true);
            $pdf->Cell(40, 8, 'Cantidad total', 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 8);
        }

        $pdf->Cell(75, 7, limpiarTexto(substr($prod['producto'], 0, 46)), 1, 0, 'L');
        $pdf->Cell(40, 7, limpiarTexto(tipoPromoTexto($prod['tipo'])), 1, 0, 'L');
        $pdf->Cell(35, 7, number_format($prod['veces_con_promocion']), 1, 0, 'C');
        $pdf->Cell(40, 7, number_format($prod['cantidad_total']), 1, 1, 'C');
    }
}

if (ob_get_length()) {
    ob_end_clean();
}

$nombreArchivo = 'Analisis_Promociones_' . $fechaDesde . '_' . $fechaHasta . '.pdf';
$pdf->Output('D', $nombreArchivo);
exit;
