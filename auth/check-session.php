<?php
session_start();

header('Content-Type: application/json');

try {
    $sessionData = [
        'session_id' => session_id(),
        'session_status' => session_status(),
        'authenticated' => isset($_SESSION['authenticated']) && $_SESSION['authenticated'],
        'user_data' => $_SESSION['user_data'] ?? null,
        'all_session_data' => $_SESSION
    ];
    
    echo json_encode([
        'success' => true,
        'session' => $sessionData
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error verificando sesión: ' . $e->getMessage()
    ]);
}
?>
