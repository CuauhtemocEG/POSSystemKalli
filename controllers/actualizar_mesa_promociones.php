<?php
/**
 * Controlador para actualizar configuración de promociones por mesa
 * Permite activar/desactivar promociones para mesas específicas
 */

session_start();
require_once '../conexion.php';

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener conexión
    $pdo = conexion();
    
    // Validar autenticación
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('No autorizado');
    }
    
    // Obtener datos JSON del request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Datos inválidos');
    }
    
    // Validar parámetros requeridos
    $mesa_id = intval($data['mesa_id'] ?? 0);
    $aplicar_promociones = isset($data['aplicar_promociones']) ? intval($data['aplicar_promociones']) : null;
    
    if ($mesa_id <= 0) {
        throw new Exception('ID de mesa inválido');
    }
    
    if ($aplicar_promociones === null) {
        throw new Exception('Parámetro aplicar_promociones requerido');
    }
    
    // Verificar que la mesa existe
    $stmt = $pdo->prepare("SELECT id, nombre, es_para_llevar FROM mesas WHERE id = ?");
    $stmt->execute([$mesa_id]);
    $mesa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mesa) {
        throw new Exception('Mesa no encontrada');
    }
    
    // Si es para llevar, no permitir activar promociones
    if ($mesa['es_para_llevar'] && $aplicar_promociones) {
        echo json_encode([
            'success' => false,
            'message' => 'Las órdenes para llevar no pueden tener promociones activadas'
        ]);
        exit;
    }
    
    // Actualizar configuración de la mesa
    $stmt = $pdo->prepare("
        UPDATE mesas 
        SET aplicar_promociones = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$aplicar_promociones, $mesa_id]);
    
    if ($result) {
        // Log de auditoría (opcional)
        error_log(sprintf(
            "[PROMO] Usuario %d %s promociones para mesa %d (%s)",
            $_SESSION['usuario_id'],
            $aplicar_promociones ? 'activó' : 'desactivó',
            $mesa_id,
            $mesa['nombre']
        ));
        
        echo json_encode([
            'success' => true,
            'message' => $aplicar_promociones 
                ? 'Promociones activadas correctamente' 
                : 'Promociones desactivadas correctamente',
            'mesa_id' => $mesa_id,
            'aplicar_promociones' => $aplicar_promociones
        ]);
    } else {
        throw new Exception('Error al actualizar la base de datos');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
