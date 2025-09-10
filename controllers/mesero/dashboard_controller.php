<?php
require_once '../db.php';

// Activar todos los errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'crear') {
    header('Content-Type: application/json');
    
    try {
        // Validar conexión
        if (!$pdo) {
            throw new Exception("Error de conexión a la base de datos");
        }
        
        // Obtener y validar datos
        $mesa_id = $_POST['mesa_id'] ?? null;
        $mesero_id = $_POST['mesero_id'] ?? null;
        $sucursal_id = $_POST['sucursal_id'] ?? null;
        $productos_json = $_POST['productos'] ?? null;
        
        if (!$mesa_id || !$mesero_id || !$sucursal_id || !$productos_json) {
            throw new Exception("Datos incompletos: " . json_encode($_POST));
        }
        
        $productos = json_decode($productos_json, true);
        if (!is_array($productos) || empty($productos)) {
            throw new Exception("Productos inválidos");
        }
        
        $pdo->beginTransaction();
        
        // Verificar mesa
        $stmt = $pdo->prepare("SELECT id FROM mesas WHERE id = ?");
        $stmt->execute([$mesa_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Mesa no encontrada");
        }
        
        // Crear pedido
        $stmt = $pdo->prepare("INSERT INTO pedidos (mesa_id, mesero_id, sucursal_id, estado, hora_creacion) VALUES (?, ?, ?, 'pendiente', NOW())");
        $stmt->execute([$mesa_id, $mesero_id, $sucursal_id]);
        $pedido_id = $pdo->lastInsertId();
        
        // Insertar productos
        $stmt = $pdo->prepare("INSERT INTO detalle_pedido (pedido_id, plato_id, cantidad, estado) VALUES (?, ?, ?, 'pendiente')");
        foreach ($productos as $producto_id => $cantidad) {
            if ($cantidad > 0) {
                $stmt->execute([$pedido_id, $producto_id, $cantidad]);
            }
        }
        
        // Actualizar mesa
        $pdo->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id = ?")
            ->execute([$mesa_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedido creado exitosamente (ID: ' . $pedido_id . ')',
            'pedido_id' => $pedido_id
        ]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Para pruebas directas
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test'])) {
    echo json_encode(['status' => 'controller working', 'post' => $_POST, 'get' => $_GET]);
}
?>