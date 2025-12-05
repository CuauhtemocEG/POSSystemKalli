<?php
require_once '../conexion.php';
require_once 'imprimir_termica.php';

$pdo = conexion();

/**
 * Integración de impresión térmica con el sistema POS
 */

// Función para imprimir ticket de orden automáticamente
function imprimirTicketOrden($orden_id) {
    try {
        // Obtener configuración de impresión térmica
        $stmt = $GLOBALS['pdo']->prepare("SELECT valor FROM configuracion_sistema WHERE clave = ?");
        
        $stmt->execute(['impresion_automatica']);
        $automatica = $stmt->fetchColumn();
        
        if (!$automatica || $automatica !== 'true') {
            return ['success' => false, 'message' => 'Impresión automática deshabilitada'];
        }
        
        $stmt->execute(['nombre_impresora']);
        $nombreImpresora = $stmt->fetchColumn();
        
        if (!$nombreImpresora) {
            return ['success' => false, 'message' => 'Impresora no configurada'];
        }
        
        // Crear impresora térmica
        $impresora = new ImpresorTermica();
        
        // Obtener datos de la orden
        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM ordenes WHERE id = ?");
        $stmt->execute([$orden_id]);
        $orden = $stmt->fetch();
        
        if (!$orden) {
            return ['success' => false, 'message' => 'Orden no encontrada'];
        }
        
        // Obtener productos de la orden
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT op.cantidad, p.precio as precio_unitario, (op.cantidad * p.precio) as subtotal, p.nombre 
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            WHERE op.orden_id = ?
        ");
        $stmt->execute([$orden_id]);
        $productos = $stmt->fetchAll();
        
        // Generar ticket ESC/POS
        $impresora->texto('TICKET DE VENTA', 'center', true, 'large');
        $impresora->saltoLinea();
        $impresora->texto('Mesa: ' . $orden['mesa_numero'], 'left');
        $impresora->texto('Orden: #' . $orden['id'], 'left');
        $impresora->texto('Fecha: ' . date('d/m/Y H:i:s', strtotime($orden['fecha_creacion'])), 'left');
        $impresora->saltoLinea();
        $impresora->linea('=', 32);
        $impresora->saltoLinea();
        
        // Tabla de productos
        $impresora->texto("PRODUCTO         CANT  PRECIO", 'left', true);
        $impresora->linea('-', 32);
        
        foreach ($productos as $producto) {
            $nombre = substr($producto['nombre'], 0, 16);
            $cantidad = str_pad($producto['cantidad'], 4, ' ', STR_PAD_LEFT);
            $precio = str_pad('$' . number_format($producto['precio_unitario'], 2), 8, ' ', STR_PAD_LEFT);
            
            $linea = str_pad($nombre, 16) . $cantidad . $precio;
            $impresora->texto($linea, 'left');
        }
        
        $impresora->linea('-', 32);
        $impresora->saltoLinea();
        
        // Total
        $impresora->texto('TOTAL: $' . number_format($orden['total'], 2), 'right', true, 'large');
        $impresora->saltoLinea();
        $impresora->linea('=', 32);
        $impresora->texto('Gracias por su compra!', 'center');
        $impresora->cortar();
        
        // Enviar a impresora - MULTIPLATAFORMA
        $resultadoImpresion = $impresora->imprimir($nombreImpresora);
        
        return [
            'success' => $resultadoImpresion['success'], 
            'message' => $resultadoImpresion['mensaje'],
            'sistema' => $resultadoImpresion['sistema'],
            'salida' => $resultadoImpresion['salida']
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => 'Error al imprimir: ' . $e->getMessage()
        ];
    }
}

// Si se llama como endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['orden_id'])) {
        $resultado = imprimirTicketOrden($input['orden_id']);
        echo json_encode($resultado);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ID de orden requerido'
        ]);
    }
}
?>
