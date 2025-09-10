<?php
session_start();
require_once '../../controllers/db.php';

header('Content-Type: application/json');

// Verificar autenticación y rol
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cajero') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener datos JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['pedido_id']) || !isset($data['metodo_pago'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$pedido_id = $data['pedido_id'];
$metodo_pago = $data['metodo_pago'];
$cajero_id = $_SESSION['user_id'];

// Validar método de pago
$metodos_validos = ['efectivo', 'tarjeta', 'transferencia'];
if (!in_array($metodo_pago, $metodos_validos)) {
    echo json_encode(['success' => false, 'message' => 'Método de pago inválido']);
    exit();
}

try {
    // Verificar que el pedido existe y está por pagar
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND estado = 'por_pagar'");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado o ya pagado']);
        exit();
    }

    // Actualizar el pedido
    $stmt = $pdo->prepare("UPDATE pedidos SET 
                          estado = 'pagado', 
                          metodo_pago = ?, 
                          cajero_id = ?, 
                          fecha_pago = NOW() 
                          WHERE id = ?");
    $stmt->execute([$metodo_pago, $cajero_id, $pedido_id]);

    // Registrar en el log de ventas
    $stmt = $pdo->prepare("INSERT INTO ventas_log (pedido_id, cajero_id, metodo_pago, monto, fecha_venta) 
                          VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$pedido_id, $cajero_id, $metodo_pago, $pedido['total']]);

    // Liberar la mesa
    $stmt = $pdo->prepare("UPDATE mesas SET estado = 'libre' WHERE id = ?");
    $stmt->execute([$pedido['mesa_id']]);

    echo json_encode(['success' => true, 'message' => 'Pago procesado exitosamente']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al procesar el pago']);
}
?>