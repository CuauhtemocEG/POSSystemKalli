<?php
/**
 * Datos de detalle de una orden, compartidos por la vista y el PDF para que ambos coincidan.
 */
function obtenerDetalleOrden(PDO $pdo, int $orden_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT o.*, m.nombre AS mesa_nombre,
               u.nombre_completo AS mesero_nombre,
               uc.nombre_completo AS cajero_nombre
        FROM ordenes o
        JOIN mesas m ON m.id = o.mesa_id
        LEFT JOIN usuarios u ON u.id = o.usuario_id
        LEFT JOIN usuarios uc ON uc.id = o.cerrada_por_usuario_id
        WHERE o.id = ?
    ");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$orden) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT op.id, p.nombre, op.cantidad,
               COALESCE(op.preparado, 0) AS preparado,
               COALESCE(op.cancelado, 0) AS cancelado,
               COALESCE(op.pendiente_cancelacion, 0) AS pendiente_cancelacion,
               COALESCE(op.item_index, 1) AS item_index,
               COALESCE(op.nota_adicional, '') AS nota_adicional,
               op.producto_id, p.precio
        FROM orden_productos op
        JOIN productos p ON op.producto_id = p.id
        WHERE op.orden_id = ? AND op.estado != 'eliminado'
        ORDER BY p.nombre, op.item_index
    ");
    $stmt->execute([$orden_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtVar = $pdo->prepare("
        SELECT grupo_nombre, opcion_nombre, precio_adicional
        FROM orden_producto_variedades
        WHERE orden_id = ? AND producto_id = ? AND item_index = ?
        ORDER BY id
    ");

    $subtotal = 0.0;
    $total_cancelado = 0.0;
    $productos_activos = 0;
    $productos_cancelados = 0;

    foreach ($productos as &$prod) {
        $stmtVar->execute([$orden_id, $prod['producto_id'], $prod['item_index']]);
        $prod['variedades'] = $stmtVar->fetchAll(PDO::FETCH_ASSOC);

        $precio = floatval($prod['precio']);
        foreach ($prod['variedades'] as $v) {
            $precio += floatval($v['precio_adicional']);
        }

        $cantidad = intval($prod['cantidad']);
        $cancelado = intval($prod['cancelado']);
        $pendiente = intval($prod['pendiente_cancelacion']);
        $activa = max(0, $cantidad - $cancelado - $pendiente);

        $prod['precio_unitario'] = $precio;
        $prod['cantidad_activa'] = $activa;
        $prod['subtotal_linea'] = $precio * $activa;

        $subtotal += $precio * $activa;
        $total_cancelado += $precio * $cancelado;
        $productos_activos += $activa;
        $productos_cancelados += $cancelado;
    }
    unset($prod);

    try {
        $stmt = $pdo->prepare("
            SELECT pa.id, pa.promocion_id, p.nombre AS nombre_promocion, p.tipo AS tipo_descuento,
                   p.valor AS valor_descuento, pa.descuento_aplicado, pa.aplicado_at AS aplicada_en
            FROM promociones_aplicadas pa
            JOIN promociones p ON p.id = pa.promocion_id
            WHERE pa.orden_id = ?
            ORDER BY pa.aplicado_at ASC
        ");
        $stmt->execute([$orden_id]);
        $promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $promociones = [];
    }

    $descuento_promos = 0.0;
    foreach ($promociones as $promo) {
        $descuento_promos += floatval($promo['descuento_aplicado']);
    }

    $descuento_pct = 0.0;
    $pct_valor = floatval($orden['descuento_porcentaje_valor'] ?? 0);
    if (!empty($orden['aplicar_descuento_porcentaje']) && $pct_valor > 0) {
        $descuento_pct = $subtotal * $pct_valor / 100;
    }

    $total_calculado = max(0, $subtotal - $descuento_promos - $descuento_pct);

    // En órdenes cerradas el total cobrado es el guardado; la diferencia se muestra como ajuste
    $cerrada = ($orden['estado'] ?? '') !== 'abierta';
    $total = ($cerrada && floatval($orden['total']) > 0) ? floatval($orden['total']) : $total_calculado;
    $ajuste = round($total_calculado - $total, 2);
    if ($ajuste < 0.01) {
        $ajuste = 0.0;
    }

    $stmt = $pdo->prepare("
        SELECT pp.numero_pago, pp.monto, pp.metodo_pago, pp.dinero_recibido, pp.cambio, pp.pagado_en,
               u.nombre_completo AS usuario_nombre
        FROM pagos_parciales pp
        LEFT JOIN usuarios u ON u.id = pp.usuario_id
        WHERE pp.orden_id = ?
        ORDER BY pp.numero_pago ASC
    ");
    $stmt->execute([$orden_id]);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'orden' => $orden,
        'productos' => $productos,
        'promociones' => $promociones,
        'pagos' => $pagos,
        'subtotal' => $subtotal,
        'total_cancelado' => $total_cancelado,
        'productos_activos' => $productos_activos,
        'productos_cancelados' => $productos_cancelados,
        'descuento_promos' => $descuento_promos,
        'descuento_pct' => $descuento_pct,
        'descuento_pct_valor' => $pct_valor,
        'ajuste' => $ajuste,
        'total' => $total,
    ];
}

function textoTipoPromocion(array $promo): string
{
    return match ($promo['tipo_descuento']) {
        'descuento_porcentaje' => $promo['valor_descuento'] . '% de descuento',
        'descuento_fijo' => '$' . number_format($promo['valor_descuento'], 2) . ' de descuento',
        'descuento_personal' => $promo['valor_descuento'] . '% descuento personal',
        '2x1' => '2x1 en productos seleccionados',
        '3x2' => '3x2 en productos seleccionados',
        default => 'Descuento especial',
    };
}

function textoMetodoPago(?string $metodo): string
{
    return match ($metodo) {
        'efectivo' => 'Efectivo',
        'debito' => 'Débito',
        'credito' => 'Crédito',
        'transferencia' => 'Transferencia',
        default => $metodo ? ucfirst($metodo) : '-',
    };
}
