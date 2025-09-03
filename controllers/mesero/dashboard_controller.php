<?php
require_once '../controllers/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'mesero') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

try {
    switch($action) {
        case 'crear':
            crearPedido();
            break;
        case 'obtener':
            obtenerPedidos();
            break;
        case 'actualizar':
            actualizarPedido();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function crearPedido() {
    global $pdo;
    
    $mesa_id = $_POST['mesa_id'] ?? '';
    $mesero_id = $_POST['mesero_id'] ?? '';
    $sucursal_id = $_POST['sucursal_id'] ?? '';
    $productos = json_decode($_POST['productos'] ?? '{}', true);
    
    if (empty($mesa_id) || empty($mesero_id) || empty($sucursal_id) || empty($productos)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    // Verificar que la mesa esté libre
    $stmt = $pdo->prepare("SELECT estado FROM mesas WHERE id = ?");
    $stmt->execute([$mesa_id]);
    $mesa = $stmt->fetch();
    
    if (!$mesa) {
        echo json_encode(['success' => false, 'message' => 'Mesa no encontrada']);
        return;
    }
    
    if ($mesa['estado'] != 'libre') {
        echo json_encode(['success' => false, 'message' => 'La mesa no está disponible']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Crear el pedido
        $stmt = $pdo->prepare("INSERT INTO pedidos (mesa_id, mesero_id, sucursal_id, estado) VALUES (?, ?, ?, 'pendiente')");
        $stmt->execute([$mesa_id, $mesero_id, $sucursal_id]);
        $pedido_id = $pdo->lastInsertId();
        
        // Agregar detalles del pedido
        $stmt = $pdo->prepare("INSERT INTO detalle_pedido (pedido_id, plato_id, cantidad) VALUES (?, ?, ?)");
        
        foreach ($productos as $plato_id => $cantidad) {
            if ($cantidad > 0) {
                $stmt->execute([$pedido_id, $plato_id, $cantidad]);
            }
        }
        
        // Actualizar estado de la mesa
        $stmt = $pdo->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id = ?");
        $stmt->execute([$mesa_id]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Pedido creado exitosamente', 'pedido_id' => $pedido_id]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al crear el pedido: ' . $e->getMessage()]);
    }
}

function obtenerPedidos() {
    global $pdo;
    
    $mesero_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT p.*, m.numero as mesa_numero FROM pedidos p 
                          JOIN mesas m ON p.mesa_id = m.id 
                          WHERE p.mesero_id = ? AND p.estado != 'cerrado' 
                          ORDER BY p.hora_creacion DESC");
    $stmt->execute([$mesero_id]);
    $pedidos = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $pedidos]);
}

function actualizarPedido() {
    global $pdo;
    
    $pedido_id = $_POST['pedido_id'] ?? '';
    $estado = $_POST['estado'] ?? '';
    
    if (empty($pedido_id) || empty($estado)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    
    if ($stmt->execute([$estado, $pedido_id])) {
        echo json_encode(['success' => true, 'message' => 'Pedido actualizado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el pedido']);
    }
}
?>

