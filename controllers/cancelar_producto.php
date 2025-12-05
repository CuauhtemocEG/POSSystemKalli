<?php
require_once '../conexion.php';
$pdo = conexion();
$op_id = $_POST['op_id'];
$marcar = isset($_POST['marcar']) ? intval($_POST['marcar']) : 1;

// Consulta la cantidad, cancelado, preparado y pendiente_cancelacion actuales
$stmt = $pdo->prepare("SELECT cantidad, preparado, cancelado, pendiente_cancelacion FROM orden_productos WHERE id=?");
$stmt->execute([$op_id]);
$row = $stmt->fetch();

if ($row) {
    // Calcular cuántos productos están realmente disponibles para cancelar
    $disponibles = $row['cantidad'] - $row['preparado'] - $row['cancelado'] - $row['pendiente_cancelacion'];
    
    if ($disponibles <= 0) {
        echo json_encode(["status"=>"error", "msg"=>"No hay productos disponibles para cancelar"]);
        exit;
    }
    
    $a_cancelar = min($disponibles, max(1, $marcar));
    $nuevo_pendiente_cancelacion = $row['pendiente_cancelacion'] + $a_cancelar;
    
    // Actualizar el campo pendiente_cancelacion en lugar de cancelado directamente
    $pdo->prepare("UPDATE orden_productos SET pendiente_cancelacion=? WHERE id=?")
        ->execute([$nuevo_pendiente_cancelacion, $op_id]);
        
    echo json_encode([
        "status"=>"ok", 
        "msg"=>"Se enviaron $a_cancelar unidades a revisión de cancelación. Esperando aprobación del administrador."
    ]);
} else {
    echo json_encode(["status"=>"error", "msg"=>"No se encontró el producto"]);
}
exit;
?>