<?php
require_once '../../controllers/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'guardar':
            guardarMesa();
            break;
        case 'actualizar':
            actualizarMesa();
            break;
        case 'eliminar':
            eliminarMesa();
            break;
        case 'obtener':
            obtenerMesa();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function guardarMesa() {
    global $pdo;
    
    $numero = $_POST['numero'] ?? '';
    $sucursal_id = $_POST['sucursal_id'] ?? '';
    $estado = $_POST['estado'] ?? 'libre';
    
    if (empty($numero) || empty($sucursal_id)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
        return;
    }
    
    // Verificar si el número de mesa ya existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mesas WHERE numero = ? AND sucursal_id = ?");
    $stmt->execute([$numero, $sucursal_id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'El número de mesa ya existe en esta sucursal']);
        return;
    }
    
    $stmt = $pdo->prepare("INSERT INTO mesas (numero, sucursal_id, estado) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$numero, $sucursal_id, $estado])) {
        echo json_encode(['success' => true, 'message' => 'Mesa creada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear la mesa']);
    }
}

function actualizarMesa() {
    global $pdo;
    
    $id = $_POST['id'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $sucursal_id = $_POST['sucursal_id'] ?? '';
    $estado = $_POST['estado'] ?? 'libre';
    
    if (empty($id) || empty($numero) || empty($sucursal_id)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
        return;
    }
    
    // Verificar si el número de mesa ya existe (excluyendo la mesa actual)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mesas WHERE numero = ? AND sucursal_id = ? AND id != ?");
    $stmt->execute([$numero, $sucursal_id, $id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'El número de mesa ya existe en esta sucursal']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE mesas SET numero = ?, sucursal_id = ?, estado = ? WHERE id = ?");
    
    if ($stmt->execute([$numero, $sucursal_id, $estado, $id])) {
        echo json_encode(['success' => true, 'message' => 'Mesa actualizada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la mesa']);
    }
}

function eliminarMesa() {
    global $pdo;
    
    $id = $_POST['id'] ?? '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        return;
    }
    
    // Verificar si la mesa tiene pedidos asociados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE mesa_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'No se puede eliminar la mesa porque tiene pedidos asociados']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM mesas WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Mesa eliminada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la mesa']);
    }
}

function obtenerMesa() {
    global $pdo;
    
    $id = $_GET['id'] ?? '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM mesas WHERE id = ?");
    $stmt->execute([$id]);
    $mesa = $stmt->fetch();
    
    if ($mesa) {
        echo json_encode(['success' => true, 'data' => $mesa]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Mesa no encontrada']);
    }
}
?>