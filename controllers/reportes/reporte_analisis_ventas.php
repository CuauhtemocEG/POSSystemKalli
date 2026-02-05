<?php
// Suprimir TODOS los mensajes de error, warnings y notices para PDF
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../fpdf/fpdf.php';
require_once __DIR__ . '/../../conexion.php';

// Crear conexión a la base de datos
$pdo = conexion();

// Verificar que el usuario está autenticado (la sesión ya está iniciada en conexion.php)
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die(json_encode(['error' => 'No autorizado']));
}

// Función helper para convertir UTF-8 a ISO-8859-1 (para FPDF)
function utf8_to_latin1($text) {
    return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
}

// Obtener filtros de fecha
$fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-d');
$fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// Construir condición de fecha
$condicionFecha = "DATE(creada_en) BETWEEN '$fechaDesde' AND '$fechaHasta'";

// Clase PDF personalizada
class ReporteAnalisisPDF extends FPDF
{
    private $fechaDesde;
    private $fechaHasta;

    public function setFechas($desde, $hasta)
    {
        $this->fechaDesde = $desde;
        $this->fechaHasta = $hasta;
    }

    // Cabecera de página
    function Header()
    {
        // Logo
        $logoPath = __DIR__ . '/../../assets/img/logo.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 10, 6, 30);
        }

        // Título
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(33, 37, 41);
        $this->Cell(80);
        $this->Cell(30, 10, utf8_to_latin1('Análisis de Ventas'), 0, 0, 'C');
        $this->Ln(8);

        // Subtítulo con período
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(80);
        $periodoTexto = 'Período: ' . date('d/m/Y', strtotime($this->fechaDesde)) . ' al ' . date('d/m/Y', strtotime($this->fechaHasta));
        $this->Cell(30, 5, utf8_to_latin1($periodoTexto), 0, 0, 'C');
        $this->Ln(8);

        // Fecha y hora de generación
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, utf8_to_latin1('Generado: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
        $this->Ln(12);
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, utf8_to_latin1('Página ') . $this->PageNo() . ' - Kalli Jaguar POS © ' . date('Y'), 0, 0, 'C');
    }

    // Sección con título
    function SeccionTitulo($titulo)
    {
        $this->SetFillColor(59, 130, 246);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, utf8_to_latin1($titulo), 0, 1, 'L', true);
        $this->Ln(2);
    }

    // Tabla de estadísticas
    function TablaEstadistica($titulo, $valor, $color = [52, 211, 153])
    {
        $this->SetFillColor($color[0], $color[1], $color[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(90, 8, utf8_to_latin1($titulo), 1, 0, 'L', true);
        $this->Cell(100, 8, $valor, 1, 1, 'R', true);
    }

    // Tarjeta de resumen
    function TarjetaResumen($titulo, $valor, $subtitulo = '', $color = [59, 130, 246])
    {
        // Guardar posición
        $x = $this->GetX();
        $y = $this->GetY();

        // Marco
        $this->SetFillColor(248, 249, 250);
        $this->SetDrawColor($color[0], $color[1], $color[2]);
        $this->SetLineWidth(0.5);
        $this->RoundedRect($x, $y, 60, 25, 3, 'DF');

        // Título
        $this->SetXY($x + 3, $y + 3);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(54, 5, utf8_to_latin1($titulo), 0, 1, 'L');

        // Valor
        $this->SetX($x + 3);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor($color[0], $color[1], $color[2]);
        $this->Cell(54, 10, $valor, 0, 1, 'L');

        // Subtítulo
        if ($subtitulo) {
            $this->SetX($x + 3);
            $this->SetFont('Arial', '', 7);
            $this->SetTextColor(150, 150, 150);
            $this->Cell(54, 4, utf8_to_latin1($subtitulo), 0, 1, 'L');
        }

        return $x + 65; // Retornar posición X para siguiente tarjeta
    }

    // Rectángulo redondeado
    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));

        $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));

        $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));

        $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - $yc) * $k));

        $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1 * $this->k, ($h - $y1) * $this->k,
            $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
    }
}

// Crear instancia del PDF
$pdf = new ReporteAnalisisPDF();
$pdf->setFechas($fechaDesde, $fechaHasta);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 15);

// ============================================
// 1. RESUMEN EJECUTIVO
// ============================================
$pdf->SeccionTitulo('Resumen Ejecutivo');

// Consultas de datos
$totalOrdenes = $pdo->query("SELECT COUNT(*) FROM ordenes WHERE estado = 'cerrada' AND $condicionFecha")->fetchColumn();
$ventasTotales = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND $condicionFecha")->fetchColumn() ?? 0;
$subtotalTotal = $pdo->query("SELECT COALESCE(SUM(COALESCE(subtotal, total)), 0) FROM ordenes WHERE estado = 'cerrada' AND $condicionFecha")->fetchColumn() ?? 0;
$descuentosTotal = $subtotalTotal - $ventasTotales;
$ticketPromedio = $totalOrdenes > 0 ? $ventasTotales / $totalOrdenes : 0;

// Tarjetas de resumen
$pdf->SetY($pdf->GetY() + 2);
$xPos = 10;
$yPos = $pdf->GetY();

$pdf->SetXY($xPos, $yPos);
$xPos = $pdf->TarjetaResumen('Total de Ventas', '$' . number_format($ventasTotales, 2), '', [52, 211, 153]);

$pdf->SetXY($xPos, $yPos);
$xPos = $pdf->TarjetaResumen(utf8_to_latin1('Órdenes Cerradas'), number_format($totalOrdenes), '', [59, 130, 246]);

$pdf->SetXY($xPos, $yPos);
$pdf->TarjetaResumen('Ticket Promedio', '$' . number_format($ticketPromedio, 2), '', [147, 51, 234]);

$pdf->Ln(28);

// Tabla de desglose
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(120, 8, 'Concepto', 1, 0, 'L', true);
$pdf->Cell(70, 8, 'Monto', 1, 1, 'R', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);

$datos = [
    ['Subtotal (antes de descuentos)', '$' . number_format($subtotalTotal, 2)],
    ['Promociones y Descuentos', '-$' . number_format($descuentosTotal, 2)],
    ['Total Real Cobrado', '$' . number_format($ventasTotales, 2)],
];

foreach ($datos as $fila) {
    $pdf->Cell(120, 7, utf8_to_latin1($fila[0]), 1, 0, 'L');
    $pdf->Cell(70, 7, $fila[1], 1, 1, 'R');
}

$pdf->Ln(5);

// ============================================
// 2. ANÁLISIS POR MÉTODO DE PAGO
// ============================================
$pdf->SeccionTitulo(utf8_to_latin1('Análisis por Método de Pago'));

// Consultar métodos de pago
$metodosPago = $pdo->query("
    SELECT 
        metodo_pago,
        COUNT(*) as total_ordenes,
        COALESCE(SUM(total), 0) as total_ventas,
        COALESCE(AVG(total), 0) as promedio
    FROM ordenes 
    WHERE estado = 'cerrada' AND $condicionFecha
    GROUP BY metodo_pago
    ORDER BY total_ventas DESC
")->fetchAll(PDO::FETCH_ASSOC);

if (count($metodosPago) > 0) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->Cell(50, 8, utf8_to_latin1('Método'), 1, 0, 'C', true);
    $pdf->Cell(35, 8, utf8_to_latin1('Órdenes'), 1, 0, 'C', true);
    $pdf->Cell(55, 8, 'Total Ventas', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Promedio', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    foreach ($metodosPago as $metodo) {
        $nombreMetodo = ucfirst($metodo['metodo_pago'] ?? 'No especificado');
        $pdf->Cell(50, 7, utf8_to_latin1($nombreMetodo), 1, 0, 'L');
        $pdf->Cell(35, 7, number_format($metodo['total_ordenes']), 1, 0, 'C');
        $pdf->Cell(55, 7, '$' . number_format($metodo['total_ventas'], 2), 1, 0, 'R');
        $pdf->Cell(50, 7, '$' . number_format($metodo['promedio'], 2), 1, 1, 'R');
    }
} else {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 10, utf8_to_latin1('No hay datos de métodos de pago en el período seleccionado'), 0, 1, 'C');
}

$pdf->Ln(5);

// ============================================
// 3. PRODUCTOS MÁS VENDIDOS
// ============================================
$pdf->SeccionTitulo(utf8_to_latin1('Top 10 Productos Más Vendidos'));

// Consultar productos más vendidos
$productosVendidos = $pdo->query("
    SELECT 
        p.nombre, 
        SUM(op.cantidad) as total_vendido, 
        SUM(op.cantidad * p.precio) as total_ingresos,
        p.precio as precio_unitario
    FROM orden_productos op 
    JOIN productos p ON op.producto_id = p.id 
    JOIN ordenes o ON op.orden_id = o.id 
    WHERE o.estado = 'cerrada' 
    AND $condicionFecha
    AND (op.cancelado = 0 OR op.cancelado IS NULL)
    GROUP BY p.id, p.nombre, p.precio
    ORDER BY total_vendido DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

if (count($productosVendidos) > 0) {
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(80, 8, 'Producto', 1, 0, 'L', true);
    $pdf->Cell(30, 8, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Precio Unit.', 1, 0, 'R', true);
    $pdf->Cell(35, 8, 'Total', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    $contador = 1;
    foreach ($productosVendidos as $producto) {
        $pdf->Cell(10, 7, $contador++, 1, 0, 'C');
        $pdf->Cell(80, 7, utf8_to_latin1(substr($producto['nombre'], 0, 35)), 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($producto['total_vendido']), 1, 0, 'C');
        $pdf->Cell(35, 7, '$' . number_format($producto['precio_unitario'], 2), 1, 0, 'R');
        $pdf->Cell(35, 7, '$' . number_format($producto['total_ingresos'], 2), 1, 1, 'R');
    }
} else {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 10, utf8_to_latin1('No hay productos vendidos en el período seleccionado'), 0, 1, 'C');
}

$pdf->Ln(5);

// ============================================
// 4. DETALLE DE ÓRDENES CERRADAS
// ============================================
$pdf->AddPage();
$pdf->SeccionTitulo(utf8_to_latin1('Detalle de Órdenes Cerradas'));

// Consultar órdenes cerradas
$ordenesCerradas = $pdo->query("
    SELECT 
        o.id,
        o.codigo,
        m.nombre as mesa,
        o.total,
        o.metodo_pago,
        o.creada_en,
        o.cerrada_en,
        TIMESTAMPDIFF(MINUTE, o.creada_en, o.cerrada_en) as duracion_minutos
    FROM ordenes o
    JOIN mesas m ON o.mesa_id = m.id
    WHERE o.estado = 'cerrada' AND $condicionFecha
    ORDER BY o.cerrada_en DESC
")->fetchAll(PDO::FETCH_ASSOC);

if (count($ordenesCerradas) > 0) {
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->Cell(50, 7, utf8_to_latin1('Código'), 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Mesa', 1, 0, 'L', true);
    $pdf->Cell(28, 7, utf8_to_latin1('Método Pago'), 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Total', 1, 0, 'R', true);
    $pdf->Cell(35, 7, 'Cerrada', 1, 0, 'C', true);
    $pdf->Cell(22, 7, utf8_to_latin1('Durac.'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);

    $totalGeneral = 0;
    foreach ($ordenesCerradas as $orden) {
        $totalGeneral += $orden['total'];
        $pdf->Cell(50, 6, utf8_to_latin1($orden['codigo']), 1, 0, 'C');
        $pdf->Cell(25, 6, utf8_to_latin1(substr($orden['mesa'], 0, 12)), 1, 0, 'L');
        $pdf->Cell(28, 6, utf8_to_latin1(ucfirst($orden['metodo_pago'] ?? 'N/A')), 1, 0, 'L');
        $pdf->Cell(30, 6, '$' . number_format($orden['total'], 2), 1, 0, 'R');
        $pdf->Cell(35, 6, date('d/m/Y H:i', strtotime($orden['cerrada_en'])), 1, 0, 'C');
        $pdf->Cell(22, 6, $orden['duracion_minutos'], 1, 1, 'C');
    }

    // Total general
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(52, 211, 153);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(110, 7, 'TOTAL GENERAL', 1, 0, 'R', true);
    $pdf->Cell(30, 7, '$' . number_format($totalGeneral, 2), 1, 0, 'R', true);
    $pdf->Cell(50, 7, count($ordenesCerradas) . utf8_to_latin1(' órdenes'), 1, 1, 'C', true);
} else {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 10, utf8_to_latin1('No hay órdenes cerradas en el período seleccionado'), 0, 1, 'C');
}

// Generar el PDF
$pdf->Output('D', 'Analisis_Ventas_' . $fechaDesde . '_' . $fechaHasta . '.pdf');
