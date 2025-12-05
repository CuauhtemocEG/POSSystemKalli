<?php
require_once '../auth-check.php';

// Verificar permisos
if (!hasPermission('ordenes', 'editar')) {
    if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        echo json_encode(['status' => 'error', 'msg' => 'Sin permisos']);
    } else {
        header("Location: ../index.php?page=error&msg=Sin permisos");
    }
    exit;
}

$pdo = conexion();
$userInfo = getUserInfo();

$mesa_id = $_POST['mesa_id'];
$producto_id = $_POST['producto_id'];
$cantidad = intval($_POST['cantidad']);

// Busca la orden activa
$stmt = $pdo->prepare("SELECT id FROM ordenes WHERE mesa_id=? AND estado='abierta'");
$stmt->execute([$mesa_id]);
$orden = $stmt->fetch();
$orden_id = $orden['id'];

// Busca si ya existe ese producto en la orden
$stmt = $pdo->prepare("SELECT id, cantidad FROM orden_productos WHERE orden_id=? AND producto_id=?");
$stmt->execute([$orden_id, $producto_id]);
$item = $stmt->fetch();

if ($item) {
    // Sumar cantidad a la existente
    $nueva_cantidad = $item['cantidad'] + $cantidad;
    $pdo->prepare("UPDATE orden_productos SET cantidad=? WHERE id=?")
        ->execute([$nueva_cantidad, $item['id']]);
} else {
    // Crear fila nueva con auditoría
    $pdo->prepare("INSERT INTO orden_productos (orden_id, producto_id, cantidad, agregado_por_usuario_id) VALUES (?, ?, ?, ?)")
        ->execute([$orden_id, $producto_id, $cantidad, $userInfo['id']]);
}

if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    echo "ok";
} else {
    header("Location: ../index.php?page=mesa&id=$mesa_id");
}
exit;
?>