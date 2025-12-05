<?php
require_once '../conexion.php';
require_once '../auth-check.php';

$pdo = conexion();
$mesa_id = intval($_POST['mesa_id'] ?? 0);
$userInfo = getUserInfo();
$usuario_id = $userInfo['id'] ?? null;

if (!$mesa_id) {
    header("Location: ../index.php?page=mesas&error=mesa_no_seleccionada");
    exit;
}

try {
    // Verificar que la mesa exista
    $stmt = $pdo->prepare("SELECT * FROM mesas WHERE id = ?");
    $stmt->execute([$mesa_id]);
    $mesa = $stmt->fetch();
    
    if (!$mesa) {
        header("Location: ../index.php?page=mesas&error=mesa_no_encontrada");
        exit;
    }
    
    // Verificar que no haya una orden abierta para esta mesa
    $stmt = $pdo->prepare("SELECT id FROM ordenes WHERE mesa_id = ? AND estado = 'abierta'");
    $stmt->execute([$mesa_id]);
    $orden_existente = $stmt->fetch();
    
    if ($orden_existente) {
        header("Location: ../index.php?page=mesa&id=$mesa_id&info=orden_ya_existe");
        exit;
    }
    
    // Generar código único para la orden
    $codigo = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    
    // Crear nueva orden con campos básicos, incluyendo el usuario que la abre
    $stmt = $pdo->prepare("INSERT INTO ordenes (mesa_id, codigo, estado, total, usuario_id) VALUES (?, ?, 'abierta', 0.00, ?)");
    $stmt->execute([$mesa_id, $codigo, $usuario_id]);
    
    $orden_id = $pdo->lastInsertId();
    
    // Actualizar estado de la mesa (mantengo compatibilidad con estados originales)
    $stmt = $pdo->prepare("UPDATE mesas SET estado = 'cerrada' WHERE id = ?");
    $stmt->execute([$mesa_id]);
    
    header("Location: ../index.php?page=mesa&id=$mesa_id&success=orden_creada");
    exit;
    
} catch (Exception $e) {
    // Log del error
    error_log("Error creando orden: " . $e->getMessage() . " - Mesa ID: $mesa_id");
    
    // Redireccionar con información del error
    $error_msg = urlencode("Error al crear orden: " . $e->getMessage());
    header("Location: ../index.php?page=mesas&error=$error_msg");
    exit;
}
?>
?>