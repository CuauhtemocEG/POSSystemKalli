<?php
// === CONFIGURACIÓN DE MEMORIA Y OUTPUT ===
ini_set('memory_limit', '256M'); // Aumentar límite de memoria para reportes grandes
ini_set('max_execution_time', '120'); // 2 minutos máximo

// Limpiar cualquier output previo
ob_clean();
ob_start();

// Definir la ruta base
define('BASE_PATH', dirname(dirname(__DIR__)) . '/');

// Solo incluir la conexión, que ya maneja la configuración
require_once BASE_PATH . 'conexion.php';
require_once BASE_PATH . 'fpdf/fpdf.php';

// Crear la conexión a la base de datos
$pdo = conexion();

// Obtener filtros de fecha desde parámetros GET
$fechaDesde = $_GET['fecha_desde'] ?? null;
$fechaHasta = $_GET['fecha_hasta'] ?? null;

// Construir condición de fecha
$condicionFecha = "";
$textoPeriodo = "del dia de hoy";

if ($fechaDesde && $fechaHasta) {
    $condicionFecha = "AND DATE(o.creada_en) BETWEEN '$fechaDesde' AND '$fechaHasta'";
    $textoPeriodo = "del " . date('d/m/Y', strtotime($fechaDesde)) . " al " . date('d/m/Y', strtotime($fechaHasta));
} else {
    $condicionFecha = "AND DATE(o.creada_en) = CURDATE()";
}

// Función para limpiar texto y convertir a ASCII seguro
function limpiarTexto($texto) {
    // Primero reemplazar caracteres acentuados ANTES de filtrar
    $texto = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'ü', 'Ü', 'ç', 'Ç'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N', 'u', 'U', 'c', 'C'],
        $texto
    );
    
    // Mantener caracteres básicos, números, espacios y puntuación común
    // Agregamos {} para que {nb} no se elimine
    $texto = preg_replace('/[^a-zA-Z0-9\s\.,\-:$(){}\/#|@]/', '', $texto);
    
    return $texto;
}

class ReporteProductosVendidos extends FPDF {
    private $empresa = "Kalli Jaguar POS";
    private $fecha_reporte;
    private $total_ventas = 0;
    private $total_productos = 0;
    private $periodo_texto;
    
    function __construct($textoPeriodo = "del día de hoy") {
        parent::__construct();
        $this->fecha_reporte = date('d/m/Y');
        $this->periodo_texto = $textoPeriodo;
        // Configuración UTF-8
        $this->SetTitle('Reporte de Productos Vendidos');
        $this->SetAuthor('Kalli Jaguar POS');
    }
    
    // Encabezado
    function Header() {
        // Logo
        $logoPath = BASE_PATH . 'assets/img/LogoBlack.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 15, 10, 40);
        }
        
        // Información de la empresa
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 8, limpiarTexto($this->empresa), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 5, limpiarTexto('Sistema de Punto de Venta'), 0, 1, 'C');
        $this->Cell(0, 5, 'Tel: 756-112-7119 | Email: cencarnacion@kallijaguar-inventory.com', 0, 1, 'C');
        
        // Línea decorativa
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.8);
        $this->Line(15, 35, 195, 35);
        
        // Título del reporte
        $this->Ln(8);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 8, limpiarTexto('Reporte de Productos Vendidos'), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 5, limpiarTexto('Periodo: ' . $this->periodo_texto), 0, 1, 'C');
        
        $this->Ln(5);
    }
    
    // Pie de página
    function Footer() {
        $this->SetY(-25);
        
        // Línea decorativa
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.5);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        
        $this->Ln(3);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 4, limpiarTexto('Generado el ' . date('d/m/Y H:i:s') . ' | Kalli Jaguar POS'), 0, 1, 'C');
        $this->Cell(0, 4, limpiarTexto('Pagina ' . $this->PageNo() . ' de {nb}'), 0, 0, 'C');
    }
    
    // Encabezado de tabla
    function TablaHeader() {
        $this->SetFillColor(52, 152, 219);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        
        $this->Cell(15, 10, '#', 1, 0, 'C', true);
        $this->Cell(70, 10, 'Producto', 1, 0, 'L', true);
        $this->Cell(25, 10, 'Cantidad', 1, 0, 'C', true);
        $this->Cell(25, 10, 'Precio Unit.', 1, 0, 'C', true);
        $this->Cell(25, 10, 'Subtotal', 1, 0, 'C', true);
        $this->Cell(30, 10, 'Tipo', 1, 1, 'C', true);
        
        $this->SetTextColor(44, 62, 80);
    }
    
    // Fila de datos
    function TablaFila($num, $producto, $cantidad, $precio, $subtotal, $tipo, $isEven = false) {
        $this->SetFont('Arial', '', 9);
        
        // Color alternado para las filas
        if ($isEven) {
            $this->SetFillColor(248, 249, 250);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        // Limpiar texto para compatibilidad con PDF
        $producto = limpiarTexto($producto);
        $tipo = limpiarTexto($tipo);
        
        $this->Cell(15, 8, $num, 1, 0, 'C', true);
        $this->Cell(70, 8, substr($producto, 0, 32), 1, 0, 'L', true);
        $this->Cell(25, 8, number_format($cantidad), 1, 0, 'C', true);
        $this->Cell(25, 8, '$' . number_format($precio, 2), 1, 0, 'R', true);
        $this->Cell(25, 8, '$' . number_format($subtotal, 2), 1, 0, 'R', true);
        $this->Cell(30, 8, substr($tipo, 0, 15), 1, 1, 'C', true);
    }
    
    // Resumen final
    function ResumenFinal($total_productos, $total_ventas, $periodo_texto) {
        $this->Ln(5);
        
        // Cuadro de resumen
        $this->SetFillColor(46, 204, 113);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        
        $this->Cell(0, 10, 'Resumen ' . $periodo_texto, 1, 1, 'C', true);
        
        $this->SetFillColor(236, 240, 241);
        $this->SetTextColor(44, 62, 80);
        $this->SetFont('Arial', 'B', 10);
        
        $this->Cell(95, 8, 'Total de productos vendidos:', 1, 0, 'L', true);
        $this->Cell(95, 8, number_format($total_productos) . ' unidades', 1, 1, 'R', true);
        
        $this->Cell(95, 8, 'Total de ventas ' . $periodo_texto . ':', 1, 0, 'L', true);
        $this->SetTextColor(46, 204, 113);
        $this->Cell(95, 8, '$' . number_format($total_ventas, 2), 1, 1, 'R', true);
    }
    
    // Corte de Caja
    function CorteDeCaja($efectivo, $debito, $credito, $transferencia, $tarjeta) {
        $this->Ln(8);
        
        // Título de Corte de Caja
        $this->SetFillColor(52, 152, 219);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, limpiarTexto('CORTE DE CAJA'), 1, 1, 'C', true);
        
        // Headers de tabla
        $this->SetFillColor(44, 62, 80);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        
        $this->Cell(95, 8, limpiarTexto('Método de Pago'), 1, 0, 'C', true);
        $this->Cell(95, 8, limpiarTexto('Total Ventas'), 1, 1, 'C', true);
        
        // Datos
        $this->SetTextColor(44, 62, 80);
        $this->SetFont('Arial', '', 10);
        
        // Efectivo
        $this->SetFillColor(240, 248, 255);
        $this->Cell(95, 7, 'Efectivo', 1, 0, 'L', true);
        $this->Cell(95, 7, '$' . number_format($efectivo, 2), 1, 1, 'R', true);
        
        // Débito
        $this->SetFillColor(255, 255, 255);
        $this->Cell(95, 7, limpiarTexto('Débito'), 1, 0, 'L', true);
        $this->Cell(95, 7, '$' . number_format($debito, 2), 1, 1, 'R', true);
        
        // Crédito
        $this->SetFillColor(240, 248, 255);
        $this->Cell(95, 7, limpiarTexto('Crédito'), 1, 0, 'L', true);
        $this->Cell(95, 7, '$' . number_format($credito, 2), 1, 1, 'R', true);
        
        // Transferencia
        $this->SetFillColor(255, 255, 255);
        $this->Cell(95, 7, 'Transferencia', 1, 0, 'L', true);
        $this->Cell(95, 7, '$' . number_format($transferencia, 2), 1, 1, 'R', true);
        
        // Tarjeta (si existe)
        if ($tarjeta > 0) {
            $this->SetFillColor(240, 248, 255);
            $this->Cell(95, 7, 'Tarjeta', 1, 0, 'L', true);
            $this->Cell(95, 7, '$' . number_format($tarjeta, 2), 1, 1, 'R', true);
        }
        
        // Total General
        $total = $efectivo + $debito + $credito + $transferencia + $tarjeta;
        $this->SetFillColor(46, 204, 113);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        
        $this->Cell(95, 9, limpiarTexto('TOTAL GENERAL'), 1, 0, 'L', true);
        $this->Cell(95, 9, '$' . number_format($total, 2), 1, 1, 'R', true);
    }
}

// Obtener datos de productos vendidos hoy
try {
    // Obtener datos de corte de caja
    $efectivo = isset($_GET['efectivo']) ? floatval($_GET['efectivo']) : 0;
    $debito = isset($_GET['debito']) ? floatval($_GET['debito']) : 0;
    $credito = isset($_GET['credito']) ? floatval($_GET['credito']) : 0;
    $transferencia = isset($_GET['transferencia']) ? floatval($_GET['transferencia']) : 0;
    $tarjeta = isset($_GET['tarjeta']) ? floatval($_GET['tarjeta']) : 0;
    
    $query = "
        SELECT 
            p.nombre,
            p.precio,
            t.nombre as tipo,
            SUM(op.preparado) as total_cantidad,
            SUM(op.preparado * p.precio) as total_subtotal
        FROM orden_productos op
        INNER JOIN productos p ON op.producto_id = p.id
        INNER JOIN type t ON p.type = t.id
        INNER JOIN ordenes o ON op.orden_id = o.id
        WHERE o.estado = 'cerrada'
        AND op.preparado > 0
        AND op.cancelado = 0
        $condicionFecha
        GROUP BY p.id, p.nombre, p.precio, t.nombre
        ORDER BY total_cantidad DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear el PDF
    $pdf = new ReporteProductosVendidos($textoPeriodo);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Verificar si hay datos
    if (empty($productos)) {
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(231, 76, 60);
        $texto_sin_datos = $fechaDesde && $fechaHasta ? 
            'No se encontraron productos vendidos para el período seleccionado.' : 
            'No se encontraron productos vendidos para el día de hoy.';
        $pdf->Cell(0, 20, $texto_sin_datos, 0, 1, 'C');
    } else {
        // Encabezado de tabla
        $pdf->TablaHeader();
        
        $total_productos = 0;
        $total_ventas = 0;
        $contador = 1;
        
        // Datos de la tabla
        foreach ($productos as $index => $producto) {
            $isEven = ($index % 2 == 0);
            
            $pdf->TablaFila(
                $contador,
                $producto['nombre'],
                $producto['total_cantidad'],
                $producto['precio'],
                $producto['total_subtotal'],
                $producto['tipo'],
                $isEven
            );
            
            $total_productos += $producto['total_cantidad'];
            $total_ventas += $producto['total_subtotal'];
            $contador++;
            
            // Verificar si necesitamos una nueva página
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                $pdf->TablaHeader();
            }
        }
        
        // Resumen final
        $pdf->ResumenFinal($total_productos, $total_ventas, $textoPeriodo);
        
        // Corte de Caja (solo si hay datos de métodos de pago)
        if ($efectivo > 0 || $debito > 0 || $credito > 0 || $transferencia > 0 || $tarjeta > 0) {
            $pdf->CorteDeCaja($efectivo, $debito, $credito, $transferencia, $tarjeta);
        }
    }
    
    // === CONFIGURAR HEADERS PARA DESCARGA FORZADA ===
    // Limpiar buffer de salida
    ob_end_clean();
    
    // Headers para forzar descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Reporte_Productos_Vendidos_' . date('Y-m-d_His') . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Generar PDF y forzar descarga (D = Download)
    $pdf->Output('D', 'Reporte_Productos_Vendidos_' . date('Y-m-d_His') . '.pdf');
    exit;
    
} catch (Exception $e) {
    // Limpiar buffer en caso de error
    if (ob_get_length()) ob_end_clean();
    
    // Mostrar error amigable
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error en Reporte</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            text-align: center;
        }
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #dc2626;
            margin: 0 0 20px 0;
            font-size: 28px;
        }
        .error-message {
            color: #374151;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>Error al Generar Reporte</h1>
        <div class="error-message">
            <p><strong>Detalles del error:</strong></p>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <p style="margin-top: 20px; font-size: 14px; color: #6b7280;">
                Sugerencia: Intenta reducir el rango de fechas o contacta al administrador del sistema.
            </p>
        </div>
        <a href="javascript:history.back()" class="btn">← Volver a Reportes</a>
    </div>
</body>
</html>';
    exit;
}
?>
