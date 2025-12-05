<?php
require_once '../auth-check.php'; // Para obtener getUserInfo()
require_once '../conexion.php';

$pdo = conexion();

// Obtener información del usuario actual
$userInfo = getUserInfo();
$usuario_id = $userInfo['id'] ?? 1; // Usar ID 1 como fallback si no hay usuario

$op_id = $_POST['op_id'];
$marcar = isset($_POST['marcar']) ? intval($_POST['marcar']) : 1;

// Consulta la cantidad y preparado/cancelado actuales
$stmt = $pdo->prepare("SELECT cantidad, preparado, cancelado, pendiente_cancelacion FROM orden_productos WHERE id=?");
$stmt->execute([$op_id]);
$row = $stmt->fetch();

if ($row) {
    // ✅ CALCULAR UNIDADES DISPONIBLES
    $pendientes = $row['cantidad'] - $row['preparado'] - $row['cancelado'] - $row['pendiente_cancelacion'];
    
    // ✅ VERIFICAR SI HAY PRODUCTOS PENDIENTES DISPONIBLES PARA PREPARAR
    if ($pendientes <= 0) {
        // Crear mensaje más específico según la situación
        $mensaje = "No hay productos disponibles para preparar";
        
        if ($row['pendiente_cancelacion'] > 0 && $row['cancelado'] == 0) {
            $disponibles_reales = $row['cantidad'] - $row['preparado'] - $row['cancelado'];
            if ($disponibles_reales > 0) {
                $mensaje = "Todas las unidades disponibles ({$disponibles_reales}) están pendientes de cancelación";
            }
        } elseif ($row['preparado'] >= $row['cantidad'] - $row['cancelado']) {
            $mensaje = "Todos los productos ya están preparados";
        } elseif ($row['cancelado'] >= $row['cantidad']) {
            $mensaje = "Todos los productos están cancelados";
        }
        
        echo json_encode(["status"=>"error", "msg"=>$mensaje]);
        exit;
    }
    
    // ✅ NOTA: Ya no necesitamos verificar pendiente_cancelacion aquí
    // porque $pendientes ya descuenta las unidades pendientes de cancelación
    // Esto permite preparar las unidades que NO están pendientes de cancelación
    
    $a_preparar = min($pendientes, max(1, $marcar));
    $nuevo_preparado = $row['preparado'] + $a_preparar;
    
    // Actualizar con el usuario que marcó como preparado
    $pdo->prepare("UPDATE orden_productos SET preparado=?, preparado_por_usuario_id=? WHERE id=?")
        ->execute([$nuevo_preparado, $usuario_id, $op_id]);
        
    echo json_encode(["status"=>"ok", "msg"=>"Se marcaron $a_preparar como preparados"]);
} else {
    echo json_encode(["status"=>"error", "msg"=>"No se encontró el producto"]);
}
exit;
?>