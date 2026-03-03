<?php
require_once __DIR__ . '/../../conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$orden_id = intval($_POST['orden_id'] ?? 0);

if ($orden_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de orden inválido'
    ]);
    exit;
}

$pdo = conexion();

try {
    $config_impresion = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('nombre_impresora')");
    $config_impresion->execute();

    $config_datos = [];
    while ($row = $config_impresion->fetch(PDO::FETCH_ASSOC)) {
        $config_datos[$row['clave']] = $row['valor'];
    }

    $nombre_impresora = trim($config_datos['nombre_impresora'] ?? '');
    if ($nombre_impresora === '') {
        throw new Exception('No hay una impresora configurada en el sistema.');
    }

    $originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    require_once __DIR__ . '/../imprimir_termica.php';
    $_SERVER['REQUEST_METHOD'] = $originalRequestMethod;

    $stmt = $pdo->prepare("SELECT o.*, o.estado AS orden_estado, m.nombre AS mesa_nombre, m.estado AS mesa_estado FROM ordenes o JOIN mesas m ON o.mesa_id = m.id WHERE o.id = ?");
    $stmt->execute([$orden_id]);
    $orden_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orden_data) {
        throw new Exception('Orden no encontrada.');
    }

    $estadoOrden = strtolower(trim($orden_data['orden_estado'] ?? ''));
    if (!in_array($estadoOrden, ['cerrada', 'pagada'], true)) {
        throw new Exception('Solo se permite imprimir órdenes cerradas o pagadas.');
    }

    $stmt = $pdo->prepare("SELECT COALESCE(es_personal, 0) as es_personal, COALESCE(aplicar_descuento_porcentaje, 0) as aplicar_descuento_porcentaje, COALESCE(descuento_porcentaje_valor, 0) as descuento_porcentaje_valor FROM ordenes WHERE id = ?");
    $stmt->execute([$orden_id]);
    $orden_flags = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'es_personal' => 0,
        'aplicar_descuento_porcentaje' => 0,
        'descuento_porcentaje_valor' => 0
    ];

    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.nombre, 
            p.precio,
            p.categoria,
            SUM(op.cantidad - COALESCE(op.cancelado, 0)) as cantidad
        FROM orden_productos op 
        JOIN productos p ON op.producto_id = p.id 
        WHERE op.orden_id = ?
          AND COALESCE(op.confirmado, 1) = 1
        GROUP BY p.id, p.nombre, p.precio, p.categoria
        HAVING SUM(op.cantidad - COALESCE(op.cancelado, 0)) > 0
        ORDER BY p.nombre
    ");
    $stmt->execute([$orden_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.nombre, 
            p.precio, 
            SUM(op.cancelado) as cantidad
        FROM orden_productos op 
        JOIN productos p ON op.producto_id = p.id 
        WHERE op.orden_id = ? 
          AND op.cancelado > 0
        GROUP BY p.id, p.nombre, p.precio
        HAVING SUM(op.cancelado) > 0
        ORDER BY p.nombre
    ");
    $stmt->execute([$orden_id]);
    $productosCancelados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $subtotal_query = $pdo->prepare("
        SELECT SUM((op.cantidad - COALESCE(op.cancelado, 0)) * p.precio) as subtotal
        FROM orden_productos op 
        JOIN productos p ON op.producto_id = p.id 
        WHERE op.orden_id = ? AND (op.cantidad - COALESCE(op.cancelado, 0)) > 0
    ");
    $subtotal_query->execute([$orden_id]);
    $subtotal = floatval($subtotal_query->fetchColumn() ?? 0);

    $stmtPromociones = $pdo->prepare("
        SELECT 
            pa.id,
            pa.promocion_id,
            p.nombre as nombre_promocion,
            p.tipo as tipo_descuento,
            p.valor as valor_descuento,
            pa.descuento_aplicado,
            pa.aplicado_at as aplicada_en
        FROM promociones_aplicadas pa
        JOIN promociones p ON p.id = pa.promocion_id
        WHERE pa.orden_id = ?
        ORDER BY pa.aplicado_at ASC
    ");
    $stmtPromociones->execute([$orden_id]);
    $promociones_aplicadas = $stmtPromociones->fetchAll(PDO::FETCH_ASSOC);

    $total_descuentos_promociones = 0;
    $promociones_a_guardar = [];
    foreach ($promociones_aplicadas as $promo) {
        $monto = floatval($promo['descuento_aplicado']);
        $total_descuentos_promociones += $monto;
        $promociones_a_guardar[] = [
            'promocion_id' => $promo['promocion_id'],
            'descuento' => round($monto, 2)
        ];
    }

    $descuento_porcentaje_aplicado = 0;
    if (
        intval($orden_flags['aplicar_descuento_porcentaje']) === 1 &&
        floatval($orden_flags['descuento_porcentaje_valor']) > 0
    ) {
        $porcentaje = floatval($orden_flags['descuento_porcentaje_valor']);
        $descuento_porcentaje_aplicado = ($subtotal * $porcentaje) / 100;
    }

    $total = $subtotal - $total_descuentos_promociones - $descuento_porcentaje_aplicado;

    $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('empresa_nombre', 'empresa_direccion')");
    $stmt->execute();
    $configuraciones = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $empresaNombre = $configuraciones['empresa_nombre'] ?? 'Kalli Jaguar';
    $empresaDireccion = $configuraciones['empresa_direccion'] ?? '';

    $impresora = new ImpresorTermica();

    $impresora->imagenConfigurada();

    if (!empty($empresaDireccion)) {
        $lineasDireccion = $impresora->dividirTextoParaTicket($empresaDireccion, 32);
        foreach ($lineasDireccion as $linea) {
            $impresora->texto($linea, 'center');
        }
    }
    $impresora->saltoLinea();

    $impresora->texto('Sucursal: ' . $empresaNombre, 'left');
    $impresora->texto('Mesa: ' . $orden_data['mesa_nombre'], 'left');
    $impresora->texto('Orden: #' . $orden_data['codigo'], 'left');
    $impresora->texto('Fecha: ' . date('d/m/Y H:i:s', strtotime($orden_data['creada_en'])), 'left');
    $impresora->saltoLinea();
    $impresora->linea('=', 45);
    $impresora->saltoLinea();

    if (!empty($productos)) {
        $impresora->tablaProductos($productos);
    }

    $impresora->saltoLinea();
    $impresora->texto('Subtotal: $' . number_format($subtotal, 2), 'right');

    $promociones_ticket = [];
    if (!empty($promociones_a_guardar)) {
        foreach ($promociones_a_guardar as $promo_guardada) {
            $stmtPromoNombre = $pdo->prepare("SELECT nombre, tipo FROM promociones WHERE id = ?");
            $stmtPromoNombre->execute([$promo_guardada['promocion_id']]);
            $promo_info = $stmtPromoNombre->fetch(PDO::FETCH_ASSOC);

            if ($promo_info) {
                $promociones_ticket[] = [
                    'nombre' => $promo_info['nombre'],
                    'tipo' => $promo_info['tipo'],
                    'monto' => $promo_guardada['descuento'],
                    'detalle' => ''
                ];
            }
        }
    }

    if (count($promociones_ticket) > 0) {
        $impresora->saltoLinea();
        $impresora->linea('-', 45);
        $impresora->texto('PROMOCIONES APLICADAS:', 'left', true);

        foreach ($promociones_ticket as $promo) {
            $nombrePromo = substr($promo['nombre'], 0, 25);
            $montoPromo = '-$' . number_format($promo['monto'], 2);
            $espacios = 45 - strlen($nombrePromo) - strlen($montoPromo);
            $linea = $nombrePromo . str_repeat('.', max(1, $espacios)) . $montoPromo;
            $impresora->texto($linea, 'left');
        }

        $impresora->saltoLinea();
        $impresora->texto('Total Descuentos: -$' . number_format($total_descuentos_promociones, 2), 'right', true);
    }

    if ($descuento_porcentaje_aplicado > 0) {
        $impresora->saltoLinea();
        $impresora->linea('-', 45);
        $impresora->texto('DESCUENTO MANUAL APLICADO:', 'left', true);

        $porcentaje_str = number_format($orden_flags['descuento_porcentaje_valor'], 1) . '%';
        $monto_desc_str = '-$' . number_format($descuento_porcentaje_aplicado, 2);

        $espacios = 45 - strlen('Descuento ' . $porcentaje_str) - strlen($monto_desc_str);
        $linea = 'Descuento ' . $porcentaje_str . str_repeat('.', max(1, $espacios)) . $monto_desc_str;
        $impresora->texto($linea, 'left');

        $impresora->saltoLinea();
        $impresora->texto('Descuento Manual: -$' . number_format($descuento_porcentaje_aplicado, 2), 'right', true);
    }

    $impresora->saltoLinea();
    $impresora->linea('=', 45);
    $impresora->texto('TOTAL: $' . number_format($total, 2), 'right', true, 'large');
    $impresora->saltoLinea();

    $numeroATexto = function($numero) {
        $pesos = intval($numero);
        $centavos = intval(($numero - $pesos) * 100);

        $unidades = ["", "UNO", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE"];
        $decenas = ["", "", "VEINTE", "TREINTA", "CUARENTA", "CINCUENTA", "SESENTA", "SETENTA", "OCHENTA", "NOVENTA"];
        $especiales = [
            10 => "DIEZ", 11 => "ONCE", 12 => "DOCE", 13 => "TRECE", 14 => "CATORCE", 15 => "QUINCE",
            16 => "DIECISEIS", 17 => "DIECISIETE", 18 => "DIECIOCHO", 19 => "DIECINUEVE",
            21 => "VEINTIUNO", 22 => "VEINTIDOS", 23 => "VEINTITRES", 24 => "VEINTICUATRO", 25 => "VEINTICINCO",
            26 => "VEINTISEIS", 27 => "VEINTISIETE", 28 => "VEINTIOCHO", 29 => "VEINTINUEVE"
        ];
        $centenas = ["", "CIENTO", "DOSCIENTOS", "TRESCIENTOS", "CUATROCIENTOS", "QUINIENTOS",
                    "SEISCIENTOS", "SETECIENTOS", "OCHOCIENTOS", "NOVECIENTOS"];

        $convertirNumero = function($num) use ($unidades, $decenas, $especiales, $centenas) {
            if ($num == 0) return "CERO";

            $texto = "";

            if ($num >= 1000) {
                $miles = intval($num / 1000);
                if ($miles == 1) {
                    $texto .= "MIL ";
                } else {
                    $textoMiles = "";
                    if ($miles < 30 && isset($especiales[$miles])) {
                        $textoMiles = $especiales[$miles];
                    } else {
                        if ($miles >= 100) {
                            $cenMiles = intval($miles / 100);
                            if ($miles == 100) {
                                $textoMiles .= "CIEN";
                            } else {
                                $textoMiles .= $centenas[$cenMiles];
                            }
                            $miles %= 100;
                            if ($miles > 0) $textoMiles .= " ";
                        }

                        if ($miles >= 30) {
                            $decMiles = intval($miles / 10);
                            $uniMiles = $miles % 10;
                            $textoMiles .= $decenas[$decMiles];
                            if ($uniMiles > 0) $textoMiles .= " Y " . $unidades[$uniMiles];
                        } elseif ($miles >= 10) {
                            $textoMiles .= $especiales[$miles] ?? ($decenas[intval($miles / 10)] . ($miles % 10 > 0 ? " Y " . $unidades[$miles % 10] : ""));
                        } elseif ($miles > 0) {
                            $textoMiles .= $unidades[$miles];
                        }
                    }
                    $texto .= $textoMiles . " MIL ";
                }
                $num %= 1000;
            }

            if ($num < 30 && isset($especiales[$num])) {
                $texto .= $especiales[$num];
            } else {
                if ($num >= 100) {
                    $cen = intval($num / 100);
                    if ($num == 100) {
                        $texto .= "CIEN";
                    } elseif ($cen <= 9) {
                        $texto .= $centenas[$cen];
                    }
                    $num %= 100;
                    if ($num > 0) $texto .= " ";
                }

                if ($num >= 30) {
                    $dec = intval($num / 10);
                    $uni = $num % 10;
                    $texto .= $decenas[$dec];
                    if ($uni > 0) $texto .= " Y " . $unidades[$uni];
                } elseif ($num >= 10) {
                    $texto .= $especiales[$num] ?? ($decenas[intval($num / 10)] . ($num % 10 > 0 ? " Y " . $unidades[$num % 10] : ""));
                } elseif ($num > 0) {
                    $texto .= $unidades[$num];
                }
            }

            return trim($texto);
        };

        $resultado = "";
        if ($pesos == 0) {
            $resultado = "CERO PESOS";
        } elseif ($pesos == 1) {
            $resultado = "UN PESO";
        } else {
            $resultado = $convertirNumero($pesos) . " PESOS";
        }

        if ($centavos > 0) {
            if ($centavos == 1) {
                $resultado .= " CON UN CENTAVO";
            } else {
                $resultado .= " CON " . $convertirNumero($centavos) . " CENTAVOS";
            }
        }

        return $resultado . " 00/100 M.N.";
    };

    $totalTexto = $numeroATexto($total);
    $impresora->texto($totalTexto, 'center', false, 'normal');
    $impresora->saltoLinea();

    $impresora->linea('-', 45);

    $metodos_formato = [
        'efectivo' => 'EFECTIVO',
        'debito' => 'TARJETA DE DÉBITO',
        'credito' => 'TARJETA DE CRÉDITO',
        'transferencia' => 'TRANSFERENCIA BANCARIA'
    ];
    $metodoPago = strtolower(trim($orden_data['metodo_pago'] ?? ''));
    $metodo_formateado = $metodos_formato[$metodoPago] ?? 'NO ESPECIFICADO';

    $impresora->texto('METODO DE PAGO: ' . $metodo_formateado, 'left', true);

    if ($metodoPago === 'efectivo' && $orden_data['dinero_recibido'] !== null) {
        $impresora->texto('Dinero recibido: $' . number_format($orden_data['dinero_recibido'], 2), 'left');
        if ($orden_data['cambio'] !== null && $orden_data['cambio'] > 0) {
            $impresora->texto('Cambio: $' . number_format($orden_data['cambio'], 2), 'left', true);
        } else {
            $impresora->texto('Pago exacto', 'left');
        }
    }
    $impresora->saltoLinea();

    if (!empty($productosCancelados)) {
        $impresora->linea('-', 45);
        $impresora->texto('PRODUCTOS CANCELADOS:', 'left', true);
        $impresora->saltoLinea();

        foreach ($productosCancelados as $producto) {
            $nombre = substr($producto['nombre'], 0, 20);
            $precioUnitario = str_pad('$' . number_format($producto['precio'], 2), 7, ' ', STR_PAD_LEFT);
            $cantidad = str_pad($producto['cantidad'], 4, ' ', STR_PAD_LEFT);
            $precioTotal = str_pad('$' . number_format($producto['precio'] * $producto['cantidad'], 2), 10, ' ', STR_PAD_LEFT);

            $linea = str_pad($nombre, 22) . $precioUnitario . ' ' . $cantidad . ' ' . $precioTotal;
            $impresora->texto($linea, 'left');
        }
        $impresora->saltoLinea();
    }

    $impresora->linea('=', 45);
    $impresora->texto('Gracias por su compra!', 'center');
    $impresora->saltoLinea();
    $impresora->cortar();

    $resultado = $impresora->imprimir($nombre_impresora);

    echo json_encode([
        'success' => (bool)($resultado['success'] ?? false),
        'message' => $resultado['mensaje'] ?? 'Impresión procesada',
        'printer' => $nombre_impresora,
        'sistema' => $resultado['sistema'] ?? null
    ]);
} catch (Exception $e) {
    error_log('Error al imprimir ticket de orden desde listado: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
