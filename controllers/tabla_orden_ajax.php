<?php
require_once '../conexion.php';
$pdo = conexion();

$mesa_id = intval($_GET['mesa_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM ordenes WHERE mesa_id=? AND estado='abierta'");
$stmt->execute([$mesa_id]);
$orden = $stmt->fetch();
if (!$orden) {
    echo '<div class="alert alert-info">No hay orden activa.</div>';
    exit;
}
$orden_id = $orden['id'];
$detalles = $pdo->query(
    "SELECT op.*, p.nombre, p.precio 
     FROM orden_productos op
     JOIN productos p ON p.id = op.producto_id
     WHERE op.orden_id=$orden_id
     ORDER BY p.nombre"
)->fetchAll();

$total = 0;
?>
<table class="table table-striped table-bordered align-middle">
  <thead>
    <tr>
      <th>Producto</th>
      <th>Cant.</th>
      <th>Preparado</th>
      <th>Cancelado</th>
      <th>Pendiente</th>
      <th>Precio</th>
      <th>Subtotal</th>
      <th>Acción</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($detalles as $item):
      $pendientes = $item['cantidad'] - $item['preparado'] - $item['cancelado'];
      $subtotal = $item['preparado'] * $item['precio'];
      $total += $subtotal;
      ?>
      <tr>
        <td><?= htmlspecialchars($item['nombre']) ?></td>
        <td><?= $item['cantidad'] ?></td>
        <td><?= $item['preparado'] ?></td>
        <td><?= $item['cancelado'] ?></td>
        <td><?= $pendientes ?></td>
        <td>$<?= number_format($item['precio'],2) ?></td>
        <td>$<?= number_format($subtotal,2) ?></td>
        <td>
          <?php if ($pendientes > 0): ?>
            <form class="marcar-preparado-form d-inline mb-1" data-op="<?= $item['id'] ?>">
              <input type="number" name="marcar" value="1" min="1" max="<?= $pendientes ?>" class="form-control form-control-sm d-inline" style="width:60px;">
              <button type="submit" class="btn btn-success btn-sm">Preparado</button>
            </form>
            <form class="cancelar-form d-inline" data-op="<?= $item['id'] ?>">
              <input type="number" name="marcar" value="1" min="1" max="<?= $pendientes ?>" class="form-control form-control-sm d-inline" style="width:60px;">
              <button type="submit" class="btn btn-danger btn-sm">Cancelar</button>
            </form>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr>
      <th colspan="6" style="text-align:right;">Total</th>
      <th colspan="2">$<?= number_format($total,2) ?></th>
    </tr>
  </tfoot>
</table>