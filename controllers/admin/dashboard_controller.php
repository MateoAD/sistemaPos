<?php
// Obtener estadísticas del día
$ventas_dia = 0;
$mesas_ocupadas = 0;
$producto_mas_vendido = "N/A";

// Ventas del día
$hoy = date('Y-m-d');
$sql_ventas = "SELECT SUM(total) as total FROM ventas WHERE DATE(fecha) = '$hoy'";
$stmt = $pdo->query($sql_ventas);
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $ventas_dia = $row['total'] ? $row['total'] : 0;
}

// Mesas ocupadas
$sql_mesas = "SELECT COUNT(*) as ocupadas FROM mesas WHERE estado = 'ocupada' OR estado = 'en_preparacion'";
$stmt = $pdo->query($sql_mesas);
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $mesas_ocupadas = $row['ocupadas'];
}

// Producto más vendido del día
$sql_producto = "SELECT m.nombre, SUM(dp.cantidad) as total_vendido 
                FROM detalle_pedido dp 
                JOIN menu m ON dp.plato_id = m.id 
                JOIN pedidos p ON dp.pedido_id = p.id 
                WHERE DATE(p.hora_creacion) = '$hoy' 
                GROUP BY dp.plato_id 
                ORDER BY total_vendido DESC 
                LIMIT 1";
$stmt = $pdo->query($sql_producto);
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $producto_mas_vendido = $row['nombre'];
}

// Datos para gráfico de ventas por día (últimos 7 días)
$ventas_ultimos_7_dias = [];
$fechas_ultimos_7_dias = [];

for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $fechas_ultimos_7_dias[] = date('d/m', strtotime($fecha));
    
    $sql_venta_dia = "SELECT SUM(total) as total FROM ventas WHERE DATE(fecha) = '$fecha'";
    $stmt = $pdo->query($sql_venta_dia);
    $total_dia = 0;
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $total_dia = $row['total'] ? $row['total'] : 0;
    }
    $ventas_ultimos_7_dias[] = $total_dia;
}

// Datos para estadísticas de trabajadores
$roles = ['mesero', 'cajero', 'cocinero'];
$estadisticas_trabajadores = [];

foreach ($roles as $rol) {
    $sql_trabajadores = "SELECT u.nombre, COUNT(p.id) as pedidos_procesados, 
                        COALESCE(SUM(v.total), 0) as ventas_totales
                        FROM usuarios u 
                        LEFT JOIN pedidos p ON u.id = p.mesero_id AND u.rol_id = (SELECT id FROM roles WHERE nombre = '$rol')
                        LEFT JOIN ventas v ON u.id = v.cajero_id AND u.rol_id = (SELECT id FROM roles WHERE nombre = '$rol')
                        WHERE u.rol_id = (SELECT id FROM roles WHERE nombre = '$rol')
                        GROUP BY u.id";
    
    $stmt = $pdo->query($sql_trabajadores);
    $estadisticas_trabajadores[$rol] = [];
    
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch()) {
            $estadisticas_trabajadores[$rol][] = $row;
        }
    }
}

// Ventas del día
$hoy = date('Y-m-d');
$ayer = date('Y-m-d', strtotime('-1 day'));

$sql_ventas_hoy = "SELECT SUM(total) as total FROM ventas WHERE DATE(fecha) = '$hoy'";
$stmt = $pdo->query($sql_ventas_hoy);
$ventas_hoy = 0;
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $ventas_hoy = $row['total'] ? $row['total'] : 0;
}

$sql_ventas_ayer = "SELECT SUM(total) as total FROM ventas WHERE DATE(fecha) = '$ayer'";
$stmt = $pdo->query($sql_ventas_ayer);
$ventas_ayer = 0;
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $ventas_ayer = $row['total'] ? $row['total'] : 0;
}

$porcentaje_cambio = 0;
if ($ventas_ayer > 0) {
    $porcentaje_cambio = (($ventas_hoy - $ventas_ayer) / $ventas_ayer) * 100;
}

$ventas_dia = $ventas_hoy;

// Pedidos en preparación
$sql_preparacion = "SELECT COUNT(*) as en_preparacion FROM detalle_pedido WHERE estado = 'cocinando'";
$stmt = $pdo->query($sql_preparacion);
$en_preparacion = 0;
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $en_preparacion = $row['en_preparacion'];
}

// Mesas disponibles
$sql_mesas_disponibles = "SELECT COUNT(*) as disponibles FROM mesas WHERE estado = 'libre'";
$stmt = $pdo->query($sql_mesas_disponibles);
$mesas_disponibles = 0;
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $mesas_disponibles = $row['disponibles'];
}

// Total mesas (asumiendo 20 mesas en total)
$total_mesas = 3;
$porcentaje_ocupacion = ($mesas_ocupadas / $total_mesas) * 100;

// Ventas totales (todas las ventas registradas)
$sql_ventas_totales = "SELECT SUM(total) as total FROM ventas";
$stmt = $pdo->query($sql_ventas_totales);
$ventas_totales = 0;
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $ventas_totales = $row['total'] ? $row['total'] : 0;
}

// Porcentaje de progreso (puede ser basado en una meta o comparación con periodo anterior)
$meta_ventas = 5000; // Puedes ajustar este valor según necesites
$porcentaje_ventas = ($ventas_totales / $meta_ventas) * 100;
$porcentaje_ventas = min($porcentaje_ventas, 100); // No mostrar más del 100%

// Obtener datos para gráfico de ventas
function getVentasPorDias($pdo, $dias = 7) {
    $ventas = [];
    $fechas = [];
    
    for ($i = $dias-1; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        $fechas[] = date('d/m', strtotime($fecha));
        
        $sql = "SELECT SUM(total) as total FROM ventas WHERE DATE(fecha) = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fecha]);
        
        $total = 0;
        if ($row = $stmt->fetch()) {
            $total = $row['total'] ? $row['total'] : 0;
        }
        
        $ventas[] = $total;
    }
    
    return [
        'fechas' => $fechas,
        'ventas' => $ventas
    ];
}

// Obtener datos para 7 días por defecto
$datosVentas = getVentasPorDias($pdo, 7);

if (isset($_GET['action']) && $_GET['action'] === 'get_ventas') {
    $dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 7;
    $datosVentas = getVentasPorDias($pdo, $dias);
    
    header('Content-Type: application/json');
    echo json_encode($datosVentas);
    exit;
}

// Obtener productos más vendidos del día
function getProductosMasVendidos($pdo) {
    $hoy = date('Y-m-d');
    $sql = "SELECT m.nombre, SUM(dp.cantidad) as cantidad, SUM(dp.cantidad * m.precio) as total 
            FROM detalle_pedido dp 
            JOIN menu m ON dp.plato_id = m.id 
            JOIN pedidos p ON dp.pedido_id = p.id 
            WHERE DATE(p.hora_creacion) = ? 
            GROUP BY dp.plato_id 
            ORDER BY cantidad DESC 
            LIMIT 3";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hoy]);
    
    $productos = [];
    while ($row = $stmt->fetch()) {
        $productos[] = $row;
    }
    
    return $productos;
}

$productos_mas_vendidos = getProductosMasVendidos($pdo);



// Obtener total de ventas
$sql_total_ventas = "SELECT COUNT(*) as total FROM ventas";
$stmt = $pdo->query($sql_total_ventas);
$total_ventas = 0;
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $total_ventas = $row['total'];
}

// Obtener total de empleados
$sql_total_empleados = "SELECT COUNT(*) as total FROM usuarios WHERE rol_id != 1"; // Excluye admin
$stmt = $pdo->query($sql_total_empleados);
$total_empleados = 0;
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $total_empleados = $row['total'];
}

// Obtener total de items en el menú
$sql_total_menu = "SELECT COUNT(*) as total FROM menu";
$stmt = $pdo->query($sql_total_menu);
$total_menu = 0;
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $total_menu = $row['total'];
}

// Obtener total de mesas
$sql_total_mesas = "SELECT COUNT(*) as total FROM mesas";
$stmt = $pdo->query($sql_total_mesas);
$total_mesas = 0;
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch();
    $total_mesas = $row['total'];
}

// Función para obtener actividades recientes
function getRecentActivities($pdo) {
    $sql = "SELECT p.id, m.nombre as producto, dp.cantidad, dp.estado, p.hora_creacion 
            FROM detalle_pedido dp
            JOIN pedidos p ON dp.pedido_id = p.id
            JOIN menu m ON dp.plato_id = m.id
            ORDER BY p.hora_creacion DESC
            LIMIT 10";
    
    $stmt = $pdo->query($sql);
    $activities = [];
    
    while ($row = $stmt->fetch()) {
        $activities[] = [
            'text' => 'Pedido #' . $row['id'] . ': ' . $row['cantidad'] . 'x ' . $row['producto'] . ' (' . $row['estado'] . ')',
            'icon' => 'fas fa-utensils'
        ];
    }
    
    return $activities;
}

// Manejar solicitud AJAX para actividades
if (isset($_GET['action']) && $_GET['action'] === 'get_activities') {
    $activities = getRecentActivities($pdo);
    header('Content-Type: application/json');
    echo json_encode($activities);
    exit;
}

?>