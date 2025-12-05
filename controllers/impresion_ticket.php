<?php
ini_set('memory_limit', '256M');
ob_start();
require_once '../conexion.php';
require_once '../fpdf/fpdf.php';
require_once '../vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;

$pdo = conexion();

$orden_id = $_GET['orden_id'] ?? 0;

// Datos de la orden con información completa incluyendo estado y método de pago
$stmt = $pdo->prepare(
    "SELECT o.*, m.nombre AS mesa 
     FROM ordenes o JOIN mesas m ON o.mesa_id = m.id WHERE o.id=?"
);
$stmt->execute([$orden_id]);
$orden = $stmt->fetch();

// Productos (solo productos con cantidades no canceladas - IGUAL que ticket térmico)
$detalles = $pdo->prepare(
    "SELECT 
        p.nombre, 
        p.precio,
        (op.cantidad - COALESCE(op.cancelado, 0)) as cantidad
     FROM orden_productos op 
     JOIN productos p ON op.producto_id = p.id 
     WHERE op.orden_id=? 
       AND (op.cantidad - COALESCE(op.cancelado, 0)) > 0"
);
$detalles->execute([$orden_id]);
$productos = $detalles->fetchAll();

// Obtener productos cancelados si existen
$stmt_cancelados = $pdo->prepare("
    SELECT p.nombre, p.precio, SUM(op.cancelado) as cantidad
    FROM orden_productos op 
    JOIN productos p ON op.producto_id = p.id 
    WHERE op.orden_id = ? AND op.cancelado > 0
    GROUP BY op.producto_id, p.nombre, p.precio
");
$stmt_cancelados->execute([$orden_id]);
$productosCancelados = $stmt_cancelados->fetchAll();

// Obtener configuraciones de la empresa
$stmt_config = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('empresa_nombre', 'empresa_direccion')");
$stmt_config->execute();
$configuraciones = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

$empresaNombre = $configuraciones['empresa_nombre'] ?? 'Kalli Jaguar';
$empresaDireccion = $configuraciones['empresa_direccion'] ?? '39 Oriente 1204-A, Colonia Anzurez, Puebla de Zaragoza';

// Función para truncar texto según ancho de celda
function fitCellText($pdf, $width, $text, $font='Arial', $style='', $size=10) {
    $pdf->SetFont($font, $style, $size);
    if($pdf->GetStringWidth($text) <= $width) return $text;
    while($pdf->GetStringWidth($text.'...') > $width && mb_strlen($text) > 0) {
        $text = mb_substr($text, 0, -1);
    }
    return $text.'...';
}

/**
 * Convertir número a texto en español para tickets
 */
function numeroATexto($numero) {
    $pesos = intval($numero);
    $centavos = intval(($numero - $pesos) * 100);
    
    $unidades = ["", "UNO", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE"];
    $decenas = ["", "", "VEINTE", "TREINTA", "CUARENTA", "CINCUENTA", "SESENTA", "SETENTA", "OCHENTA", "NOVENTA"];
    $centenas = ["", "CIENTO", "DOSCIENTOS", "TRESCIENTOS", "CUATROCIENTOS", "QUINIENTOS", 
                "SEISCIENTOS", "SETECIENTOS", "OCHOCIENTOS", "NOVECIENTOS"];
    
    // Función auxiliar para convertir números menores a 1000
    $convertir999 = function($num) use ($unidades, $decenas, $centenas) {
        if ($num == 0) return "";
        
        $resultado = "";
        
        // Centenas
        if ($num >= 100) {
            $c = intval($num / 100);
            if ($num == 100) {
                $resultado = "CIEN";
            } else {
                $resultado = $centenas[$c];
            }
            $num %= 100;
            if ($num > 0) $resultado .= " ";
        }
        
        // Decenas especiales (10-19, 20-29)
        if ($num >= 10 && $num <= 29) {
            $especiales = [
                10 => "DIEZ", 11 => "ONCE", 12 => "DOCE", 13 => "TRECE", 14 => "CATORCE",
                15 => "QUINCE", 16 => "DIECISÉIS", 17 => "DIECISIETE", 18 => "DIECIOCHO", 19 => "DIECINUEVE",
                20 => "VEINTE", 21 => "VEINTIUNO", 22 => "VEINTIDÓS", 23 => "VEINTITRÉS", 24 => "VEINTICUATRO",
                25 => "VEINTICINCO", 26 => "VEINTISÉIS", 27 => "VEINTISIETE", 28 => "VEINTIOCHO", 29 => "VEINTINUEVE"
            ];
            $resultado .= $especiales[$num];
        } elseif ($num >= 30) {
            // Decenas normales (30-99)
            $d = intval($num / 10);
            $u = $num % 10;
            $resultado .= $decenas[$d];
            if ($u > 0) {
                $resultado .= " Y " . $unidades[$u];
            }
        } elseif ($num > 0) {
            // Solo unidades (1-9)
            $resultado .= $unidades[$num];
        }
        
        return $resultado;
    };
    
    // Convertir pesos
    $textoPesos = "";
    
    if ($pesos == 0) {
        $textoPesos = "CERO";
    } elseif ($pesos < 1000) {
        $textoPesos = $convertir999($pesos);
    } elseif ($pesos < 1000000) {
        // Miles
        $miles = intval($pesos / 1000);
        $resto = $pesos % 1000;
        
        if ($miles == 1) {
            $textoPesos = "MIL";
        } else {
            $textoPesos = $convertir999($miles) . " MIL";
        }
        
        if ($resto > 0) {
            $textoPesos .= " " . $convertir999($resto);
        }
    }
    
    // Formatear resultado final
    if ($pesos == 1) {
        $resultado = "UN PESO";
    } else {
        $resultado = $textoPesos . " PESOS";
    }
    
    // Agregar centavos
    if ($centavos > 0) {
        if ($centavos == 1) {
            $resultado .= " CON UN CENTAVO";
        } else {
            $textoCentavos = $convertir999($centavos);
            $resultado .= " CON " . $textoCentavos . " CENTAVOS";
        }
    }
    
    return $resultado . " 00/100 M.N.";
}

/**
 * Formatear nombre del método de pago para impresión
 */
function formatearMetodoPago($metodo) {
    $metodos = [
        'efectivo' => 'EFECTIVO',
        'debito' => 'TARJETA DE DÉBITO',
        'credito' => 'TARJETA DE CRÉDITO',
        'transferencia' => 'TRANSFERENCIA BANCARIA'
    ];
    
    return $metodos[$metodo] ?? strtoupper($metodo);
}

// Calcular altura dinámica del ticket basándose en el contenido
$alturaLogo = file_exists('../assets/img/LogoBlack.png') ? 23 : 0; // Logo + espacio
$alturaEmpresa = count(explode(',', $empresaDireccion)) * 3 + 1; // Líneas de dirección
$alturaOrden = 17; // Info de orden (4 líneas × 4mm + separador)
$alturaHeaderTabla = 7; // Encabezado de tabla
$alturaPorProducto = 4; // Cada producto ocupa ~4mm
$alturaTotal = 7; // Línea de total
$alturaTextoTotal = 10; // Total en texto (puede ser 2-3 líneas)
$alturaPago = 0;

// Si hay información de pago
if ($orden['estado'] === 'cerrada') {
    $alturaPago = 14; // Método de pago + dinero recibido + cambio
}

// Productos cancelados
$alturaCancelados = 0;
if (!empty($productosCancelados)) {
    $alturaCancelados = 7 + (count($productosCancelados) * 4); // Header + productos
}

$alturaBarcode = !empty($orden['codigo']) ? 18 : 0; // Código de barras
$alturaMensaje = 6; // Mensaje de agradecimiento

$numProductos = count($productos);
$alturaCalculada = $alturaLogo + $alturaEmpresa + $alturaOrden + $alturaHeaderTabla + 
                   ($numProductos * $alturaPorProducto) + $alturaTotal + $alturaTextoTotal +
                   $alturaPago + $alturaCancelados + $alturaBarcode + $alturaMensaje + 10; // +10mm de margen

// Altura mínima y máxima para el ticket
$alturaMinima = 100;
$alturaMaxima = 500;
$alturaFinal = max($alturaMinima, min($alturaMaxima, $alturaCalculada));

// Ticket PDF con altura dinámica (80mm ancho x altura calculada)
$pdf = new FPDF('P','mm',[80, $alturaFinal]);
$pdf->SetAutoPageBreak(false); // Desactivar saltos de página automáticos
$pdf->SetMargins(3, 3, 3); // Márgenes mínimos (izq, top, der)
$pdf->AddPage();

// Logo de la empresa (si existe)
if (file_exists('../assets/img/LogoBlack.png')) {
    $pdf->Image('../assets/img/LogoBlack.png', 25, 5, 30);
}
$pdf->Ln(10);

// Dirección de la empresa (usando configuración)
$pdf->SetFont('Arial','',8);
// Dividir la dirección en líneas si es muy larga
$direccionLineas = explode(',', $empresaDireccion);
foreach ($direccionLineas as $linea) {
    $pdf->Cell(0,3, utf8_decode(trim($linea)),0,1,'C');
}

$pdf->Ln(1);

// Información de la orden
$pdf->SetFont('Arial','',9);
$pdf->Cell(0,4, utf8_decode("Sucursal: " . $empresaNombre),0,1,'L');
$pdf->Cell(0,4,"Mesa: ".$orden['mesa'],0,1,'L');
$pdf->Cell(0,4, utf8_decode("Orden: #".$orden['codigo']),0,1,'L');
$pdf->Cell(0,4,"Fecha: ".date('d/m/Y H:i:s', strtotime($orden['creada_en'])),0,1,'L');
$pdf->Ln(1);

$pdf->Cell(0,0,'','T');
$pdf->Ln(1);

// Encabezado de tabla - Nueva estructura: PRODUCTO | P. UNIT | CANT | PRECIO
$pdf->SetFont('Arial','B',9);
$pdf->Cell(33,6,'PRODUCTO',0);
$pdf->Cell(13,6,'P. UNIT',0,0,'C');
$pdf->Cell(10,6,'CANT',0,0,'C');
$pdf->Cell(15,6,'PRECIO',0,1,'C');

$pdf->SetFont('Arial','',9);
$total = 0;
foreach ($productos as $prod) {
    $subtotal = $prod['cantidad'] * $prod['precio'];
    $nombre_trunc = fitCellText($pdf, 30, utf8_decode($prod['nombre']), 'Arial', '', 9);
    $pdf->Cell(33,4,$nombre_trunc,0);
    $pdf->Cell(13,4,"$".number_format($prod['precio'],2),0,0,'C');
    $pdf->Cell(10,4,$prod['cantidad'],0,0,'C');
    $pdf->Cell(15,4,"$".number_format($subtotal,2),0,1,'C');
    $total += $subtotal;
}
$pdf->Ln(1);

// Total
$pdf->SetFont('Arial','B',11);
$pdf->Cell(40,6,'TOTAL',0);
$pdf->Cell(25,6,"$".number_format($total,2),0,1,'R');
$pdf->Ln(1);

// Total en texto
$pdf->SetFont('Arial','I',7);
$totalTexto = numeroATexto($total);
// Dividir el texto si es muy largo
$maxWidth = 65; // Ancho máximo para el texto
if ($pdf->GetStringWidth($totalTexto) > $maxWidth) {
    $palabras = explode(' ', $totalTexto);
    $linea = '';
    foreach ($palabras as $palabra) {
        $pruebaLinea = $linea . ($linea ? ' ' : '') . $palabra;
        if ($pdf->GetStringWidth($pruebaLinea) > $maxWidth) {
            $pdf->Cell(0, 3, utf8_decode($linea), 0, 1, 'C');
            $linea = $palabra;
        } else {
            $linea = $pruebaLinea;
        }
    }
    if ($linea) {
        $pdf->Cell(0, 3, utf8_decode($linea), 0, 1, 'C');
    }
} else {
    $pdf->Cell(0, 3, utf8_decode($totalTexto), 0, 1, 'C');
}
$pdf->Ln(1);

// Información del pago (si la orden está cerrada)
if ($orden['estado'] === 'cerrada') {
    $pdf->Cell(0,0,'','T');
    $pdf->Ln(1);
    
    $metodoPago = formatearMetodoPago($orden['metodo_pago'] ?? 'efectivo');
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(0,4, utf8_decode('MÉTODO DE PAGO: ' . $metodoPago),0,1,'L');
    
    if (($orden['metodo_pago'] === 'efectivo' || !isset($orden['metodo_pago'])) && isset($orden['dinero_recibido']) && $orden['dinero_recibido'] !== null) {
        $pdf->SetFont('Arial','',9);
        $pdf->Cell(0,4, utf8_decode('Dinero recibido: $' . number_format($orden['dinero_recibido'], 2)),0,1,'L');
        
        if (isset($orden['cambio']) && $orden['cambio'] !== null && $orden['cambio'] > 0) {
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(0,4,'Cambio: $' . number_format($orden['cambio'], 2),0,1,'L');
        } else {
            $pdf->SetFont('Arial','',9);
            $pdf->Cell(0,4,'Pago exacto',0,1,'L');
        }
    }
    $pdf->Ln(1);
}

// Mostrar productos cancelados si existen
if (!empty($productosCancelados)) {
    $pdf->Cell(0,0,'','T');
    $pdf->Ln(2);
    
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(0,5,'PRODUCTOS CANCELADOS:',0,1,'L');
    $pdf->Ln(1);
    
    $pdf->SetFont('Arial','',8);
    foreach ($productosCancelados as $prodCanc) {
        $nombre_trunc = fitCellText($pdf, 22, utf8_decode($prodCanc['nombre']), 'Arial', '', 8);
        $pdf->Cell(22,4,$nombre_trunc,0);
        $pdf->Cell(13,4,"$".number_format($prodCanc['precio'],2),0,0,'C');
        $pdf->Cell(10,4,$prodCanc['cantidad'],0,0,'C');
        $pdf->Cell(15,4,"$".number_format($prodCanc['precio'] * $prodCanc['cantidad'],2),0,1,'C');
    }
    $pdf->Ln(2);
}

// Código de barras de la orden
if (!empty($orden['codigo'])) {
    $pdf->Cell(0,0,'','T');
    $pdf->Ln(1);
    
    $barcodePath = sys_get_temp_dir() . "/barcode_" . $orden['codigo'] . ".png";
    $generator = new BarcodeGeneratorPNG();
    file_put_contents($barcodePath, $generator->getBarcode($orden['codigo'], $generator::TYPE_CODE_128));
    $pdf->SetFont('Arial','',8);
    $pdf->Cell(0, 3, utf8_decode('Código de Orden:'), 0, 1, 'C');
    $pdf->Image($barcodePath, 15, $pdf->GetY(), 50, 12);
    $pdf->Ln(13);
    @unlink($barcodePath);
}

// Mensaje de agradecimiento
$pdf->Cell(0,0,'','T');
$pdf->Ln(1);
$pdf->SetFont('Arial','B',9);
$pdf->Cell(0,4, utf8_decode('¡Gracias por su compra!'),0,1,'C');

// Limpiar buffer de salida
ob_clean();

// Configurar headers para descarga compatible con PWA
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="ticket_mesa_'.$orden['mesa'].'_'.date('YmdHis').'.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Generar y enviar el PDF
$pdf->Output('D', 'ticket_mesa_'.$orden['mesa'].'_'.date('YmdHis').'.pdf');
exit;
?>