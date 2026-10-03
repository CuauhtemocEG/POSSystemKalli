<?php
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 30);

while (ob_get_level()) {
    ob_end_clean();
}

require_once '../conexion.php';
require_once '../fpdf/fpdf.php';
require_once '../includes/OrdenDetalle.php';

function t($s): string
{
    return mb_convert_encoding((string) $s, 'ISO-8859-1', 'UTF-8');
}

class OrdenPDF extends FPDF
{
    public $codigo = '';

    function Header()
    {
        $logo = __DIR__ . '/../assets/img/logo_pdf.jpg';
        if (file_exists($logo)) {
            $this->Image($logo, 15, 10, 52);
        }

        $this->SetXY(100, 11);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(33, 37, 41);
        $this->Cell(95, 8, 'DETALLE DE ORDEN', 0, 1, 'R');
        $this->SetX(100);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(95, 5, t($this->codigo), 0, 1, 'R');

        $this->SetDrawColor(253, 185, 49);
        $this->SetLineWidth(0.9);
        $this->Line(15, 29, 195, 29);
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(200, 200, 200);
        $this->SetY(34);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetDrawColor(253, 185, 49);
        $this->SetLineWidth(0.5);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(200, 200, 200);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(120, 6, t('Kalli Jaguar - Generado el ') . date('d/m/Y H:i'), 0, 0, 'L');
        $this->Cell(60, 6, t('Página ') . $this->PageNo() . ' de {nb}', 0, 0, 'R');
    }

    function titulo(string $texto)
    {
        if ($this->GetY() > 255) {
            $this->AddPage();
        }
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(33, 37, 41);
        $this->SetFillColor(253, 185, 49);
        $this->Cell(2, 7, '', 0, 0, 'L', true);
        $this->Cell(0, 7, '  ' . t($texto), 0, 1, 'L');
        $this->Ln(1);
    }

    function encabezadoTabla(array $cols, array $anchos, array $aligns)
    {
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetFillColor(33, 37, 41);
        $this->SetTextColor(255, 255, 255);
        foreach ($cols as $i => $c) {
            $this->Cell($anchos[$i], 7, t($c), 1, 0, $aligns[$i], true);
        }
        $this->Ln();
        $this->SetTextColor(52, 58, 64);
    }

    // Recorta el texto (ya en UTF-8) para que quepa; la fuente debe estar seleccionada
    function recortar(string $texto, float $ancho): string
    {
        $texto = t($texto);
        while ($texto !== '' && $this->GetStringWidth($texto) > $ancho - 2) {
            $texto = substr($texto, 0, -1);
        }
        return $texto;
    }

    function limite(): float
    {
        return $this->PageBreakTrigger;
    }
}

try {
    $pdo = conexion();

    $orden_id = intval($_GET['id'] ?? 0);
    if ($orden_id <= 0) {
        throw new Exception('ID de orden inválido');
    }

    $d = obtenerDetalleOrden($pdo, $orden_id);
    if (!$d) {
        throw new Exception('Orden no encontrada');
    }
    $orden = $d['orden'];

    $pdf = new OrdenPDF('P', 'mm', 'A4');
    $pdf->codigo = $orden['codigo'];
    $pdf->AliasNbPages();
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // --- Información de la orden ---
    $pdf->titulo('Información de la orden');

    $cerrada = $orden['estado'] !== 'abierta';
    $info = [
        ['Código', $orden['codigo']],
        ['Mesa', $orden['mesa_nombre']],
        ['Mesero', $orden['mesero_nombre'] ?: 'Sin asignar'],
        ['Estado', ucfirst($orden['estado'])],
        ['Creada', date('d/m/Y H:i', strtotime($orden['creada_en']))],
        ['Cerrada', !empty($orden['cerrada_en']) ? date('d/m/Y H:i', strtotime($orden['cerrada_en'])) : '-'],
    ];
    if ($cerrada) {
        $info[] = ['Cobrada por', $orden['mesero_nombre'] ?: '-'];
        $info[] = ['Método de pago', empty($d['pagos']) ? textoMetodoPago($orden['metodo_pago']) : 'Pago dividido (' . count($d['pagos']) . ')'];
    }

    $pdf->SetFillColor(248, 249, 250);
    $mitad = (int) ceil(count($info) / 2);
    for ($i = 0; $i < $mitad; $i++) {
        foreach ([$i, $i + $mitad] as $k => $idx) {
            $ultimo = $k === 1;
            if (isset($info[$idx])) {
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetTextColor(108, 117, 125);
                $pdf->Cell(30, 7, t($info[$idx][0]), 0, 0, 'L', true);
                $pdf->SetFont('Arial', '', 9.5);
                $pdf->SetTextColor(33, 37, 41);
                $pdf->Cell(58, 7, $pdf->recortar((string) $info[$idx][1], 58), 0, $ultimo ? 1 : 0, 'L', true);
            } else {
                $pdf->Cell(88, 7, '', 0, $ultimo ? 1 : 0);
            }
            if (!$ultimo) {
                $pdf->Cell(4, 7, '', 0, 0);
            }
        }
        $pdf->Ln(0.8);
    }
    $pdf->Ln(4);

    // --- Productos ---
    $pdf->titulo('Productos');
    $anchos = [86, 14, 18, 16, 22, 24];
    $aligns = ['L', 'C', 'C', 'C', 'R', 'R'];
    $cols = ['Producto', 'Cant.', 'Prep.', 'Canc.', 'P. Unit.', 'Subtotal'];
    $pdf->encabezadoTabla($cols, $anchos, $aligns);

    $lh = 4.2;
    foreach ($d['productos'] as $prod) {
        $lineas = 1 + count($prod['variedades']);
        if ($prod['nota_adicional'] !== '') {
            $lineas++;
        }
        if (intval($prod['pendiente_cancelacion']) > 0) {
            $lineas++;
        }
        $alto = max(8, $lineas * $lh + 3);

        if ($pdf->GetY() + $alto > $pdf->limite()) {
            $pdf->AddPage();
            $pdf->encabezadoTabla($cols, $anchos, $aligns);
        }

        $x0 = $pdf->GetX();
        $y0 = $pdf->GetY();
        $cantidad = intval($prod['cantidad']);
        $cancelado = intval($prod['cancelado']);
        $activa = $prod['cantidad_activa'];
        $preparado = intval($prod['preparado']);

        $x = $x0;
        foreach ($anchos as $a) {
            $pdf->Rect($x, $y0, $a, $alto);
            $x += $a;
        }

        // Producto + detalle
        $y = $y0 + 1.5;
        $pdf->SetXY($x0 + 1, $y);
        $pdf->SetFont('Arial', 'B', 9);
        if ($activa > 0) {
            $pdf->SetTextColor(33, 37, 41);
        } else {
            $pdf->SetTextColor(160, 40, 50);
        }
        $pdf->Cell($anchos[0] - 2, $lh, $pdf->recortar($prod['nombre'], $anchos[0]), 0, 0, 'L');
        $y += $lh;

        $pdf->SetFont('Arial', '', 8);
        foreach ($prod['variedades'] as $v) {
            $txt = '> ' . $v['grupo_nombre'] . ': ' . $v['opcion_nombre'];
            if (floatval($v['precio_adicional']) > 0) {
                $txt .= ' (+$' . number_format($v['precio_adicional'], 2) . ')';
            }
            $pdf->SetXY($x0 + 3, $y);
            $pdf->SetTextColor(176, 98, 0);
            $pdf->Cell($anchos[0] - 4, $lh, $pdf->recortar($txt, $anchos[0] - 2), 0, 0, 'L');
            $y += $lh;
        }
        if ($prod['nota_adicional'] !== '') {
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetXY($x0 + 3, $y);
            $pdf->SetTextColor(108, 117, 125);
            $pdf->Cell($anchos[0] - 4, $lh, $pdf->recortar('Nota: ' . $prod['nota_adicional'], $anchos[0] - 2), 0, 0, 'L');
            $y += $lh;
        }
        if (intval($prod['pendiente_cancelacion']) > 0) {
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetXY($x0 + 3, $y);
            $pdf->SetTextColor(220, 53, 69);
            $pdf->Cell($anchos[0] - 4, $lh, t('Pendiente de cancelación: ' . intval($prod['pendiente_cancelacion'])), 0, 0, 'L');
        }

        // Columnas numéricas centradas verticalmente
        $yc = $y0 + ($alto - 5) / 2;
        $x = $x0 + $anchos[0];
        $pdf->SetFont('Arial', '', 9);

        $pdf->SetTextColor(52, 58, 64);
        $pdf->SetXY($x, $yc);
        $pdf->Cell($anchos[1], 5, $cantidad, 0, 0, 'C');
        $x += $anchos[1];

        if ($activa > 0 && $preparado >= $activa) {
            $pdf->SetTextColor(40, 167, 69);
        } elseif ($preparado > 0) {
            $pdf->SetTextColor(230, 140, 0);
        } else {
            $pdf->SetTextColor(220, 53, 69);
        }
        $pdf->SetXY($x, $yc);
        $pdf->Cell($anchos[2], 5, $preparado . '/' . $activa, 0, 0, 'C');
        $x += $anchos[2];

        if ($cancelado > 0) {
            $pdf->SetTextColor(220, 53, 69);
        } else {
            $pdf->SetTextColor(108, 117, 125);
        }
        $pdf->SetXY($x, $yc);
        $pdf->Cell($anchos[3], 5, $cancelado, 0, 0, 'C');
        $x += $anchos[3];

        $pdf->SetTextColor(52, 58, 64);
        $pdf->SetXY($x, $yc);
        $pdf->Cell($anchos[4] - 1, 5, '$' . number_format($prod['precio_unitario'], 2), 0, 0, 'R');
        $x += $anchos[4];

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetXY($x, $yc);
        $pdf->Cell($anchos[5] - 1, 5, '$' . number_format($prod['subtotal_linea'], 2), 0, 0, 'R');

        $pdf->SetXY($x0, $y0 + $alto);
    }
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(108, 117, 125);
    $pdf->Cell(0, 5, t('Prep. = piezas preparadas / piezas activas. El subtotal no incluye piezas canceladas.'), 0, 1, 'L');
    $pdf->Ln(3);

    // --- Promociones ---
    if (!empty($d['promociones']) || $d['descuento_pct'] > 0) {
        $pdf->titulo('Promociones y descuentos aplicados');
        $a = [70, 80, 30];
        $c = ['Promoción', 'Tipo', 'Descuento'];
        $pdf->encabezadoTabla($c, $a, ['L', 'L', 'R']);
        $pdf->SetFillColor(255, 248, 225);
        foreach ($d['promociones'] as $promo) {
            if ($pdf->GetY() + 8 > $pdf->limite()) {
                $pdf->AddPage();
                $pdf->encabezadoTabla($c, $a, ['L', 'L', 'R']);
            }
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(33, 37, 41);
            $pdf->Cell($a[0], 8, ' ' . $pdf->recortar($promo['nombre_promocion'], $a[0] - 2), 1, 0, 'L', true);
            $pdf->SetTextColor(73, 80, 87);
            $pdf->Cell($a[1], 8, ' ' . $pdf->recortar(textoTipoPromocion($promo), $a[1] - 2), 1, 0, 'L', true);
            $pdf->SetTextColor(33, 130, 60);
            $pdf->Cell($a[2], 8, '-$' . number_format($promo['descuento_aplicado'], 2) . ' ', 1, 1, 'R', true);
        }
        if ($d['descuento_pct'] > 0) {
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(33, 37, 41);
            $pdf->Cell($a[0], 8, ' Descuento manual', 1, 0, 'L', true);
            $pdf->SetTextColor(73, 80, 87);
            $pdf->Cell($a[1], 8, ' ' . $d['descuento_pct_valor'] . '% sobre el subtotal', 1, 0, 'L', true);
            $pdf->SetTextColor(33, 130, 60);
            $pdf->Cell($a[2], 8, '-$' . number_format($d['descuento_pct'], 2) . ' ', 1, 1, 'R', true);
        }
        $pdf->Ln(5);
    }

    // --- Pagos ---
    if (!empty($d['pagos'])) {
        $pdf->titulo('Pagos de la cuenta dividida');
        $a = [14, 38, 30, 32, 28, 38];
        $c = ['No.', 'Método', 'Monto', 'Recibido', 'Cambio', 'Hora'];
        $al = ['C', 'L', 'R', 'R', 'R', 'C'];
        $pdf->encabezadoTabla($c, $a, $al);
        foreach ($d['pagos'] as $p) {
            if ($pdf->GetY() + 7 > $pdf->limite()) {
                $pdf->AddPage();
                $pdf->encabezadoTabla($c, $a, $al);
            }
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(52, 58, 64);
            $esEfectivo = $p['metodo_pago'] === 'efectivo' && $p['dinero_recibido'] !== null;
            $pdf->Cell($a[0], 7, $p['numero_pago'], 1, 0, 'C');
            $pdf->Cell($a[1], 7, ' ' . t(textoMetodoPago($p['metodo_pago'])), 1, 0, 'L');
            $pdf->Cell($a[2], 7, '$' . number_format($p['monto'], 2) . ' ', 1, 0, 'R');
            $pdf->Cell($a[3], 7, $esEfectivo ? '$' . number_format($p['dinero_recibido'], 2) . ' ' : '-', 1, 0, 'R');
            $pdf->Cell($a[4], 7, $esEfectivo ? '$' . number_format($p['cambio'], 2) . ' ' : '-', 1, 0, 'R');
            $pdf->Cell($a[5], 7, date('d/m/Y H:i', strtotime($p['pagado_en'])), 1, 1, 'C');
        }
        $pdf->Ln(5);
    }

    // --- Totales ---
    if ($pdf->GetY() > 215) {
        $pdf->AddPage();
    }
    $pdf->titulo('Resumen');
    $lw = 140;
    $vw = 40;
    $fila = function ($etiqueta, $valor, $color = [52, 58, 64]) use ($pdf, $lw, $vw) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetFillColor(248, 249, 250);
        $pdf->Cell($lw, 7, t($etiqueta) . ' ', 1, 0, 'R', true);
        $pdf->Cell($vw, 7, $valor . ' ', 1, 1, 'R', true);
    };

    $fila('Subtotal (productos activos)', '$' . number_format($d['subtotal'], 2));
    if ($d['descuento_promos'] > 0) {
        $fila('Descuento por promociones', '-$' . number_format($d['descuento_promos'], 2), [33, 130, 60]);
    }
    if ($d['descuento_pct'] > 0) {
        $fila('Descuento manual ' . $d['descuento_pct_valor'] . '%', '-$' . number_format($d['descuento_pct'], 2), [33, 130, 60]);
    }
    if ($d['ajuste'] > 0) {
        $fila('Otros descuentos / ajustes', '-$' . number_format($d['ajuste'], 2), [33, 130, 60]);
    }
    if ($d['total_cancelado'] > 0) {
        $fila('Productos cancelados (no cobrados): ' . $d['productos_cancelados'], '$' . number_format($d['total_cancelado'], 2), [220, 53, 69]);
    }

    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetFillColor(253, 185, 49);
    $pdf->SetTextColor(33, 37, 41);
    $pdf->Cell($lw, 11, 'TOTAL ', 1, 0, 'R', true);
    $pdf->Cell($vw, 11, '$' . number_format($d['total'], 2) . ' ', 1, 1, 'R', true);

    if ($cerrada && empty($d['pagos']) && $orden['metodo_pago'] === 'efectivo' && $orden['dinero_recibido'] !== null) {
        $fila('Efectivo recibido', '$' . number_format($orden['dinero_recibido'], 2));
        $fila('Cambio entregado', '$' . number_format($orden['cambio'], 2));
    }

    $pdf->Output('I', $orden['codigo'] . '.pdf');
} catch (Exception $e) {
    error_log('exportar_order_pdf: ' . $e->getMessage());
    http_response_code(500);
    die('No se pudo generar el PDF: ' . htmlspecialchars($e->getMessage()));
}
exit();
