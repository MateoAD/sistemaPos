<?php
header('Content-Type: application/json');
require_once '../../controllers/db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'filtrarVentas':
        filtrarVentas();
        break;
    case 'getDetallesVenta':
        getDetallesVenta();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

function filtrarVentas() {
    global $pdo;
    
    $busqueda = $_GET['busqueda'] ?? '';
    $fecha = $_GET['fecha'] ?? '';
    $cajero = $_GET['cajero'] ?? '';
    $sucursal = $_GET['sucursal'] ?? '';
    
    try {
        $sql = "SELECT 
                v.id,
                v.total,
                v.fecha,
                u.nombre as cajero,
                s.nombre as sucursal,
                p.mesa_id,
                mes.numero as numero_mesa
                FROM ventas v
                JOIN usuarios u ON v.cajero_id = u.id
                JOIN sucursales s ON v.sucursal_id = s.id
                JOIN pedidos p ON v.pedido_id = p.id
                JOIN mesas mes ON p.mesa_id = mes.id
                WHERE 1=1";
        
        $params = [];
        
        if ($busqueda) {
            $sql .= " AND (u.nombre LIKE ? OR s.nombre LIKE ? OR v.id LIKE ?)";
            $searchTerm = "%$busqueda%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($fecha) {
            $sql .= " AND DATE(v.fecha) = ?";
            $params[] = $fecha;
        }
        
        if ($cajero) {
            $sql .= " AND v.cajero_id = ?";
            $params[] = $cajero;
        }
        
        if ($sucursal) {
            $sql .= " AND v.sucursal_id = ?";
            $params[] = $sucursal;
        }
        
        $sql .= " ORDER BY v.fecha DESC LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $ventas]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getDetallesVenta() {
    global $pdo;
    $id = $_GET['id'] ?? 0;
    
    try {
        // Información principal de la venta
        $stmt = $pdo->prepare("SELECT 
                              v.*,
                              u.nombre as cajero,
                              s.nombre as sucursal,
                              p.mesa_id,
                              mes.numero as numero_mesa
                              FROM ventas v
                              JOIN usuarios u ON v.cajero_id = u.id
                              JOIN sucursales s ON v.sucursal_id = s.id
                              JOIN pedidos p ON v.pedido_id = p.id
                              JOIN mesas mes ON p.mesa_id = mes.id
                              WHERE v.id = ?");
        $stmt->execute([$id]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$venta) {
            echo json_encode(['success' => false, 'error' => 'Venta no encontrada']);
            return;
        }
        
        // Productos de la venta
        $stmt2 = $pdo->prepare("SELECT 
                              dp.cantidad,
                              m.precio as precio_unitario,
                              (dp.cantidad * m.precio) as subtotal,
                              m.nombre as producto
                              FROM detalle_pedido dp
                              JOIN menu m ON dp.plato_id = m.id
                              WHERE dp.pedido_id = ?");
        $stmt2->execute([$venta['pedido_id']]);
        $productos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // Si no hay productos, inicializar array vacío
        if (!$productos) {
            $productos = [];
        }
        
        $venta['productos'] = $productos;
        
        // Debug: Verificar datos
        error_log("Venta ID: " . $venta['id'] . ", Pedido ID: " . $venta['pedido_id'] . ", Productos: " . count($productos));
        
        // Asegurar que todos los campos necesarios existen
        $venta['id'] = $venta['id'] ?? '';
        $venta['fecha'] = $venta['fecha'] ?? '';
        $venta['total'] = $venta['total'] ?? '0.00';
        $venta['cajero'] = $venta['cajero'] ?? 'Desconocido';
        $venta['sucursal'] = $venta['sucursal'] ?? 'Desconocida';
        $venta['numero_mesa'] = $venta['numero_mesa'] ?? 'N/A';
        
        echo json_encode(['success' => true, 'data' => $venta]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>