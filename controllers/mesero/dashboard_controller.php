<?php
require_once '../db.php';

// Configuración de errores para producción
error_reporting(0);
ini_set('display_errors', 0);

// Asegurar que siempre enviamos JSON
header('Content-Type: application/json');

// Función para enviar respuesta JSON y terminar
function sendJsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Verificar el método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(false, 'Método HTTP no permitido');
}

// Para pruebas directas
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test'])) {
    sendJsonResponse(true, 'Controlador funcionando correctamente', [
        'post' => $_POST,
        'get' => $_GET
    ]);
}

// Verificar acción
if (!isset($_GET['action'])) {
    sendJsonResponse(false, 'Acción no especificada');
}

// Manejar acción crear
if ($_GET['action'] === 'crear') {
    try {
        // Log de entrada para debugging
        error_log("=== INICIO PETICIÓN CREAR PEDIDO ===");
        error_log("POST: " . json_encode($_POST));
        error_log("GET: " . json_encode($_GET));
        
        // Validar conexión
        if (!isset($pdo) || !$pdo) {
            throw new Exception("Error de conexión a la base de datos");
        }
        
        // Obtener y validar datos
        $mesa_id = $_POST['mesa_id'] ?? null;
        $mesero_id = $_POST['mesero_id'] ?? null;
        $sucursal_id = $_POST['sucursal_id'] ?? null;
        $productos_json = $_POST['productos'] ?? null;
        
        // Validación más estricta de datos
        if (!$mesa_id || !is_numeric($mesa_id)) {
            throw new Exception("Mesa ID inválido o faltante");
        }
        if (!$mesero_id || !is_numeric($mesero_id)) {
            throw new Exception("Mesero ID inválido o faltante");
        }
        if (!$sucursal_id || !is_numeric($sucursal_id)) {
            throw new Exception("Sucursal ID inválido o faltante");
        }
        if (!$productos_json || empty($productos_json)) {
            throw new Exception("Productos faltantes o vacíos");
        }
        
        // Log para debug
        error_log("Datos recibidos - Mesa: $mesa_id, Mesero: $mesero_id, Sucursal: $sucursal_id, Productos: $productos_json");
        
        $productos = json_decode($productos_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error al decodificar productos: " . json_last_error_msg());
        }
        if (!is_array($productos) || empty($productos)) {
            throw new Exception("Productos inválidos o vacíos");
        }
        
        // Validar que todos los productos tengan cantidad válida
        foreach ($productos as $producto_id => $cantidad) {
            if (!is_numeric($producto_id) || !is_numeric($cantidad) || $cantidad <= 0) {
                throw new Exception("Producto o cantidad inválida: Producto ID $producto_id, Cantidad $cantidad");
            }
        }
        
        $pdo->beginTransaction();
        
        // Verificar mesa
        $stmt = $pdo->prepare("SELECT id, estado FROM mesas WHERE id = ?");
        $stmt->execute([$mesa_id]);
        $mesa = $stmt->fetch();
        if (!$mesa) {
            throw new Exception("Mesa no encontrada");
        }
        
        // Crear pedido
        $stmt = $pdo->prepare("INSERT INTO pedidos (mesa_id, mesero_id, sucursal_id, estado, hora_creacion) VALUES (?, ?, ?, 'pendiente', NOW())");
        if (!$stmt->execute([$mesa_id, $mesero_id, $sucursal_id])) {
            throw new Exception("Error al crear pedido");
        }
        $pedido_id = $pdo->lastInsertId();
        
        // Insertar productos
        $stmt = $pdo->prepare("INSERT INTO detalle_pedido (pedido_id, plato_id, cantidad, estado) VALUES (?, ?, ?, 'pendiente')");
        foreach ($productos as $producto_id => $cantidad) {
            if ($cantidad > 0) {
                if (!$stmt->execute([$pedido_id, $producto_id, $cantidad])) {
                    throw new Exception("Error al insertar producto ID: $producto_id");
                }
            }
        }
        
        // Actualizar mesa
        $stmt = $pdo->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id = ?");
        if (!$stmt->execute([$mesa_id])) {
            throw new Exception("Error al actualizar mesa");
        }
        
        $pdo->commit();
        
        sendJsonResponse(true, 'Pedido registrado exitosamente', [
            'pedido_id' => $pedido_id
        ]);
        
    } catch (Exception $e) {
        error_log("Error en crear pedido: " . $e->getMessage());
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        sendJsonResponse(false, 'Error: ' . $e->getMessage());
    }
}

// Acción para corregir el estado de una mesa
if ($_GET['action'] === 'corregir_estado_mesa') {
    try {
        if (!isset($_POST['mesa_id'])) {
            throw new Exception('ID de mesa no proporcionado');
        }
        
        $mesa_id = $_POST['mesa_id'];
        
        // Verificar si la mesa tiene pedidos activos
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM pedidos 
            WHERE mesa_id = ? 
            AND estado NOT IN ('cerrado', 'completado')
        ");
        $stmt->execute([$mesa_id]);
        $tiene_pedidos_activos = $stmt->fetchColumn() > 0;
        
        // Actualizar estado de la mesa según si tiene pedidos activos
        $nuevo_estado = $tiene_pedidos_activos ? 'ocupada' : 'libre';
        $stmt = $pdo->prepare("UPDATE mesas SET estado = ? WHERE id = ?");
        if (!$stmt->execute([$nuevo_estado, $mesa_id])) {
            throw new Exception("Error al actualizar estado de la mesa");
        }
        
        sendJsonResponse(true, 'Estado de mesa corregido exitosamente', [
            'nuevo_estado' => $nuevo_estado
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(false, 'Error al corregir estado de mesa: ' . $e->getMessage());
    }
}

// Acción para obtener el estado actual del dashboard
if ($_GET['action'] === 'obtener_estado') {
    try {
        // Obtener información de todas las mesas
        $stmt = $pdo->prepare("
            SELECT m.*, s.nombre as sucursal_nombre,
                   p.id as pedido_id, p.estado as pedido_estado,
                   u.nombre as mesero_nombre, u.id as mesero_id,
                   (SELECT SUM(dp.cantidad * mn.precio) 
                    FROM detalle_pedido dp 
                    JOIN menu mn ON dp.plato_id = mn.id 
                    WHERE dp.pedido_id = p.id) as total
            FROM mesas m 
            JOIN sucursales s ON m.sucursal_id = s.id 
            LEFT JOIN pedidos p ON m.id = p.mesa_id AND p.estado IN ('en_preparacion', 'ocupada')
            LEFT JOIN usuarios u ON p.mesero_id = u.id 
            ORDER BY m.numero
        ");
        $stmt->execute();
    } catch (Exception $e) {
        sendJsonResponse(false, 'Error al obtener estado: ' . $e->getMessage());
    }
}

// Acción para obtener detalles de un pedido
if ($_GET['action'] === 'obtener_pedido') {
    try {
        if (!isset($_GET['pedido_id'])) {
            throw new Exception('ID de pedido no proporcionado');
        }
        
        // Obtener información del pedido con LEFT JOIN para evitar errores si falta alguna relación
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                m.numero as mesa_numero,
                COALESCE(u.nombre, 'Sin asignar') as mesero_nombre
            FROM pedidos p
            LEFT JOIN mesas m ON p.mesa_id = m.id
            LEFT JOIN usuarios u ON p.mesero_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$_GET['pedido_id']]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido) {
            sendJsonResponse(false, 'Pedido no encontrado');
            return;
        }
        
        // Obtener productos del pedido
        $stmt = $pdo->prepare("
            SELECT 
                dp.*,
                m.nombre as producto_nombre,
                m.precio,
                m.id as producto_id,
                m.categoria
            FROM detalle_pedido dp
            LEFT JOIN menu m ON dp.plato_id = m.id
            WHERE dp.pedido_id = ?
        ");
        $stmt->execute([$_GET['pedido_id']]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse(true, '', [
            'pedido' => $pedido,
            'productos' => $productos
        ]);
        return;
    } catch (Exception $e) {
        sendJsonResponse(false, 'Error al obtener pedido: ' . $e->getMessage());
        return;
    }
}
// Si llegamos aquí, la acción no es válida
sendJsonResponse(false, 'Acción no válida o no soportada');

// Manejador de errores global para asegurar JSON incluso en errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error fatal del servidor: ' . $error['message']
        ]);
    }
});
?>