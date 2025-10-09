<?php
session_start();
require_once '../../controllers/db.php';

// Verificar que sea mesero
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'mesero') {
    header('Location: ../../index.php');
    exit();
}

$mesero_id = $_SESSION['user_id'];
// Obtener mesas con información de pedidos y meseros
$stmt = $pdo->prepare("
    SELECT m.*, s.nombre as sucursal_nombre,
           p.id as pedido_id, p.estado as pedido_estado,
           u.nombre as mesero_nombre, u.id as mesero_id,
           (SELECT SUM(dp.cantidad * mn.precio) FROM detalle_pedido dp 
            JOIN menu mn ON dp.plato_id = mn.id 
            WHERE dp.pedido_id = p.id) as total
    FROM mesas m 
    JOIN sucursales s ON m.sucursal_id = s.id 
    LEFT JOIN pedidos p ON m.id = p.mesa_id AND p.estado NOT IN ('cerrado', 'completado')
    LEFT JOIN usuarios u ON p.mesero_id = u.id 
    ORDER BY m.numero
");
$stmt->execute();
$mesas = $stmt->fetchAll();

// Obtener productos disponibles
$stmt = $pdo->prepare("SELECT * FROM menu WHERE estado = 'disponible' ORDER BY categoria, nombre");
$stmt->execute();
$productos = $stmt->fetchAll();

// Obtener sucursal del mesero
$stmt = $pdo->prepare("SELECT sucursal_id FROM usuarios WHERE id = ?");
$stmt->execute([$mesero_id]);
$sucursal_id = $stmt->fetchColumn();

// Obtener mesas atendidas del día por este mesero
$stmt = $pdo->prepare("SELECT COUNT(*) as atendidas FROM pedidos 
                       WHERE DATE(hora_creacion) = CURDATE() 
                       AND mesero_id = ? 
                       AND estado = 'completado'");
$stmt->execute([$mesero_id]);
$mesasAtendidas = $stmt->fetch()['atendidas'];
$metaDiaria = 30;
$porcentajeMeta = min(100, ($mesasAtendidas / $metaDiaria) * 100);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mesero - Sistema POS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/orbitalert@latest/dist/orbitalert.min.css">
    <script src="https://cdn.jsdelivr.net/npm/orbitalert@latest/dist/orbitalert.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0F0F0F;
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-header {
            background: #1E201F;
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header-left {
            flex: 1;
        }

        .dashboard-title {
            color: white;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .user-info {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2em;
        }

        .glass-card-2 {
            background: #1E201F;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
            }

            .logout-btn {
                margin-top: 15px;
            }
        }

        .mesas-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .mesa-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .mesa-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .mesa-icon {
            width: 80px;
            height: 80px;
            font-size: 1.5rem;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            align-content: center;


        }

        .mesa-libre {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .mesa-ocupada {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .mesa-cerrada {
            background: linear-gradient(135deg, #6b7280, #4b5563);
        }

        .mesa-en-preparacion {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }


        .mesa-numero {
            color: white;
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .mesa-estado {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1em;
            text-transform: capitalize;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            margin: 2% auto;
            padding: 0;
            border-radius: 20px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }



        .modal-title {
            font-size: 1.8em;
            font-weight: bold;
        }

        .close {
            color: white;
            font-size: 2em;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .close:hover {
            transform: scale(1.2);
        }

        .modal-body {
            display: flex;
            height: 70vh;
        }

        .products-section {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            border-right: 1px solid rgba(0, 0, 0, 0.1);
        }

        .order-section {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-3px);
        }

        /* Estilos para tarjetas con imagen completa */
        .product-card.image-cover {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }


        .product-full-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .product-full-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #2D3748, #4A5568);
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 0;
            left: 0;
        }

        .product-content-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
            padding: 8px;
            color: white;
        }

        .product-info-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .categoria-mini-badge {
            background: rgba(59, 130, 246, 0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: 600;
        }

        .product-price-mini {
            color: #FFD700;
            font-weight: bold;
            font-size: 10px;
        }

        .product-name-mini {
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .quantity-controls-mini {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .qty-btn-mini {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            color: #2D3748;
            font-size: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .qty-btn-mini:hover {
            background: white;
            transform: scale(1.1);
        }

        .qty-mini {
            font-size: 10px;
            font-weight: bold;
            min-width: 15px;
            text-align: center;
            color: white;
        }

        /* Estilos adicionales para nombre en parte superior */
        .product-name-top {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            background: transparent;
            padding: 0;
            z-index: 2;
        }

        .product-name-overlay {
            font-size: 12px;
            font-weight: 700;
            color: #000000FF;
            display: block;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            padding: 2px 8px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }


        /* Sin gradientes ni sombras en el overlay */
        .product-content-overlay.with-shadow {
            background: transparent;
        }

        /* Cambiar botones a color amarillo */
        .qty-btn-mini {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #2D3748;
            font-size: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .qty-btn-mini:hover {
            background: linear-gradient(45deg, #FFC400, #FF8C00);
            transform: scale(1.1);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
        }

        .product-info-bottom {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        /* Ajustar grid para 4 columnas */
        .product-grid.grid-cols-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        /* Estilos para precio en negro dentro de la imagen */
        .product-price-black {
            color: #000000 !important;
            font-weight: bold;
            font-size: 11px;
             background: linear-gradient(45deg, #FFD700, #FFA500);
            padding: 2px 6px;
            border-radius: 8px;
            display: inline-block;
        }

        /* Estilos para contenedor de producto */
        .product-item-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 120px;
        }

        /* Estilos para controles de cantidad debajo de la imagen */
        .quantity-controls-below {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 6px;
            padding: 4px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 120px;
            margin-left: auto;
            margin-right: auto;
        }

        .qty-btn-below {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #2D3748;
            font-size: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .qty-btn-below:hover {
            background: linear-gradient(45deg, #FFC400, #FF8C00);
            transform: scale(1.1);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
        }

        .qty-below {
            font-size: 12px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            color: white;
            background: rgba(0, 0, 0, 0.7);
            padding: 2px 6px;
            border-radius: 8px;
        }

        .product-image {
            width: 100%;
            height: 120px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3em;
            margin-bottom: 10px;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

    

        .glass-card-1 {
            background: #0F0F0F;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Estilos mejorados para el modal de pedidos */
/* Scrollbar personalizado */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 234, 0, 0.3);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 234, 0, 0.5);
}

/* Botones de categoría optimizados */
.categoria-btn {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.8);
    padding: 6px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
}

.categoria-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    transform: translateY(-1px);
}

.categoria-btn.active {
    background: linear-gradient(45deg, #ffea00, #ff7b00);
    color: #1a1a1a;
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(255, 234, 0, 0.3);
    font-weight: 600;
}

/* Badges de categoría más pequeños */
.categoria-badge {
    background: rgba(59, 130, 246, 0.15);
    color: rgb(147, 197, 253);
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 500;
    display: inline-block;
}





/* Controles de cantidad optimizados */
.quantity-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

.quantity-btn {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: white;
    border-radius: 6px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 10px;
}

.quantity-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.05);
}

.quantity-btn:active {
    transform: scale(0.95);
}

.quantity {
    color: white;
    font-weight: 600;
    font-size: 12px;
    min-width: 20px;
    text-align: center;
}

/* Items del pedido optimizados */
.order-items {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 12px;
    min-height: 200px;
    position: relative;
}

.order-item {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 8px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.order-item:hover {
    background: rgba(255, 255, 255, 0.08);
}

.order-item-image {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 6px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    flex-shrink: 0;
}

.order-item-details {
    flex: 1;
    min-width: 0;
}

.order-item-name {
    color: white;
    font-weight: 600;
    font-size: 12px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.order-item-price {
    color: rgba(255, 255, 255, 0.6);
    font-size: 11px;
}

.order-item-quantity {
    background: rgba(255, 234, 0, 0.15);
    color: #ffea00;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 11px;
    flex-shrink: 0;
}

/* Estado vacío optimizado */
.empty-order {
    color: rgba(255, 255, 255, 0.4);
    text-align: center;
    padding: 40px 16px;
}

.empty-order i {
    font-size: 32px;
    margin-bottom: 8px;
    opacity: 0.4;
    display: block;
}

.empty-order p {
    font-size: 13px;
}

/* Total y botón optimizados */
.order-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding: 12px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 8px;
}

.total-amount {
    color: #4ade80;
    font-weight: 700;
    font-size: 20px;
}

.register-btn {
    width: 100%;
    padding: 12px 16px;
    font-size: 14px;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: linear-gradient(45deg, #ffea00, #ff7b00);
    color: #1a1a1a;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.register-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 234, 0, 0.4);
}

.register-btn:active:not(:disabled) {
    transform: translateY(0);
}

.register-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Responsive mejorado */
@media (max-width: 1024px) {
    .product-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .glass-card-2 {
        padding: 16px;
    }
    
    .product-card {
        padding: 10px;
    }
}

/* Clases para mejorar el espaciado */
.min-h-0 {
    min-height: 0;
}

.order-summary-header {
    font-size: 14px;
}
    </style>
</head>

<body>
    <?php include '../../components/navbar_mesero.php'; ?>
    <div class="main-content ml-72 transition-all duration-300 p-8">
        <!-- Encabezado del Dashboard -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1 class="dashboard-title"><i class="fas fa-utensils"></i> Dashboard Mesero</h1>
                <div class="user-info">
                    Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Mesero') ?>
                </div>
            </div>
            <div class="flex items-center space-x-4 mt-4 lg:mt-0">
                    <div class="glass-card px-4 py-2 rounded-xl">
                        <div class="flex items-center text-white">
                            <div class="pulse-dot mr-2"></div>
                            <span class="text-sm">Sistema Online</span>
                        </div>
                    </div>
                </div>
        </div>

        <!-- Barra de Progreso - Meta Diaria -->
        <div class="glass-card-2 p-6 rounded-2xl shadow-lg mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-white">Progreso del Día</h2>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-clock text-gray-400"></i>
                    <span class="text-gray-300" id="horaActual"></span>
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-300">Meta diaria: <?= $metaDiaria ?> mesas</span>
                    <span class="text-green-400 font-bold"><?= $mesasAtendidas ?>/<?= $metaDiaria ?></span>
                </div>
                <div class="w-full h-3 bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-green-400 to-blue-500 rounded-full transition-all duration-500"
                        style="width: <?= $porcentajeMeta ?>%"></div>
                </div>
                <div class="text-sm text-gray-400">
                    <?php if ($mesasAtendidas < $metaDiaria): ?>
                        Faltan <?= $metaDiaria - $mesasAtendidas ?> mesas para completar la meta
                    <?php else: ?>
                        ¡Meta completada! Excelente trabajo
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Contenedor de Mesas -->
        <div class="glass-card-2 p-6 rounded-2xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white px-6 py-3 ">
                    Mesas Disponibles
                </h2>
            </div>
          <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8">
    <?php foreach ($mesas as $mesa): 
        $mesaData = json_encode([
            'pedido_id' => $mesa['pedido_id'],
            'mesero_nombre' => $mesa['mesero_nombre'],
            'total' => $mesa['total'],
            'estado' => $mesa['estado']
        ]);
    ?>
        <div class="text-center glass-card-1 p-4 rounded-2xl shadow-lg hover:scale-105 transition-transform duration-300 <?= $mesa['estado'] === 'cerrada' ? 'cursor-not-allowed opacity-75' : 'cursor-pointer' ?>"
            onclick="<?= $mesa['estado'] !== 'cerrada' ? "gestionarMesa(" . $mesa['id'] . ", '" . $mesa['numero'] . "', " . htmlspecialchars($mesaData) . ")" : '' ?>"
            data-mesa-id="<?= $mesa['id'] ?>">
            <div class="mesa-icon mesa-<?= $mesa['estado'] ?> mx-auto mb-2 flex items-center justify-center rounded-full"
                style="width:64px;height:64px;font-size:2rem;
                    <?= $mesa['estado'] == 'libre' ? 'background:linear-gradient(45deg,#00ff99,#00cfff);color:#fff;' : '' ?>
                    <?= $mesa['estado'] == 'ocupada' ? 'background:linear-gradient(45deg,#ff7b00,#ffea00);color:#fff;' : '' ?>
                    <?= $mesa['estado'] == 'cerrada' ? 'background:linear-gradient(45deg,#6b7280,#4b5563);color:#fff;' : '' ?>
                    <?= $mesa['estado'] == 'en_preparacion' ? 'background:linear-gradient(45deg,#ff00cc,#3333ff);color:#fff;' : '' ?>">
                <span class="mesa-numero font-bold"><?= $mesa['numero'] ?></span>
                <i class="fas fa-utensils ml-2"></i>
            </div>
            <p class="text-white font-semibold">Mesa <?= $mesa['numero'] ?></p>
            <p class="text-sm text-gray-300"><?= ucfirst(str_replace('_', ' ', $mesa['estado'])) ?></p>
            <?php if ($mesa['mesero_nombre']): ?>
            <p class="text-xs text-yellow-400 mt-1">Atendida por: <?= htmlspecialchars($mesa['mesero_nombre']) ?></p>
            <?php endif; ?>
            <?php if ($mesa['total']): ?>
            <p class="text-xs text-green-400 mt-1">Total: $<?= number_format($mesa['total'], 2) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
        </div>

       

        <!-- Modal de Pedidos -->
<div id="modalPedido"
    class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50 backdrop-blur-sm transition-all duration-300"
    style="display: none;">
    <div class="glass-card-2 rounded-2xl p-6 max-w-5xl w-full mx-4 max-h-[85vh] overflow-hidden shadow-2xl flex flex-col">
        <!-- Header más compacto -->
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-white/10">
            <div>
                <h2 class="text-xl font-bold text-white">Nuevo Pedido</h2>
                <p class="text-sm text-gray-400">Mesa <span id="numeroMesa" class="text-yellow-400 font-semibold"></span></p>
            </div>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-white transition-colors w-8 h-8 flex items-center justify-center hover:bg-white/10 rounded-lg">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <!-- Filtros de categoría más compactos -->
        <div class="mb-4">
            <div class="flex flex-wrap gap-2">
                <button class="categoria-btn active" data-categoria="">
                    <i class="fas fa-star mr-1.5"></i>Todas
                </button>
                <?php 
                $stmt = $pdo->query("SELECT DISTINCT categoria FROM menu WHERE categoria IS NOT NULL ORDER BY categoria");
                $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($categorias as $cat): ?>
                    <button class="categoria-btn" data-categoria="<?= $cat['categoria'] ?>">
                        <i class="fas fa-tag mr-1.5"></i><?= ucfirst($cat['categoria']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Contenido principal con altura controlada -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 flex-1 overflow-hidden min-h-0">
            <!-- Productos - 2 columnas -->
            <div class="lg:col-span-2 flex flex-col min-h-0">
                <h3 class="text-base font-semibold text-white mb-3 flex items-center">
                    <i class="fas fa-utensils mr-2 text-yellow-400"></i>
                    Productos Disponibles
                </h3>
                <div class="product-grid grid grid-cols-4 gap-2 overflow-y-auto pr-2 custom-scrollbar flex-1 justify-items-center">
                    <?php foreach ($productos as $producto): ?>
                        <div class="product-item-container" data-categoria="<?= $producto['categoria'] ?>">
                            <div class="product-card image-cover">
                                <div class="product-name-top">
                                    <span class="product-name-overlay"><?= htmlspecialchars($producto['nombre']) ?></span>
                                </div>
                                <?php if ($producto['foto']): ?>
                                    <img src="../../uploads/<?= $producto['foto'] ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" class="product-full-image">
                                <?php else: ?>
                                    <div class="product-full-placeholder">
                                        <i class="fas fa-utensils text-gray-400 text-2xl"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="product-content-overlay with-shadow">
                                    <div class="product-info-bottom">
                                        <div class="product-price-black">$<?= number_format($producto['precio'], 2) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="quantity-controls-below">
                                <button class="qty-btn-below" onclick="cambiarCantidad(<?= $producto['id'] ?>, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="qty-below" id="qty-<?= $producto['id'] ?>">0</span>
                                <button class="qty-btn-below" onclick="cambiarCantidad(<?= $producto['id'] ?>, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Resumen - 1 columna -->
            <div class="lg:col-span-1 flex flex-col min-h-0">
                <div class="order-summary-header text-white font-semibold text-sm mb-2 flex items-center">
                    <i class="fas fa-shopping-cart mr-2 text-yellow-400"></i>
                    Resumen del Pedido
                </div>
                <div class="order-items overflow-y-auto pr-2 custom-scrollbar flex-1 mb-3" id="orderItems">
                    <div class="empty-order">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Sin productos</p>
                    </div>
                </div>
                <div class="order-footer mt-auto pt-4 border-t border-white/10">
                    <div class="order-total">
                        <span class="text-gray-300 text-sm">Total:</span>
                        <span class="total-amount" id="orderTotal">$0.00</span>
                    </div>
                    <button class="register-btn" id="registerBtn" onclick="registrarPedido()" disabled>
                        <i class="fas fa-check-circle mr-2"></i>Registrar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>

    <script>
        let mesaIdActual = null;
        let productosData = <?= json_encode($productos) ?>;
        let pedidoActual = {};
        let categoriaActual = '';

        // Filtrar productos por categoría
        function filtrarPorCategoria(categoria) {
            categoriaActual = categoria;
            
            // Actualizar botones activos
            document.querySelectorAll('.categoria-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.categoria === categoria) {
                    btn.classList.add('active');
                }
            });
            
            // Mostrar/ocultar productos según categoría
            document.querySelectorAll('.product-item-container').forEach(container => {
                if (categoria === '' || container.dataset.categoria === categoria) {
                    container.style.display = 'flex';
                } else {
                    container.style.display = 'none';
                }
            });
        }

        // Inicializar filtros de categoría
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar event listeners a los botones de categoría
            document.querySelectorAll('.categoria-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    filtrarPorCategoria(this.dataset.categoria);
                });
            });
        });

        function abrirModalPedido(mesaId, numeroMesa) {
            mesaIdActual = mesaId;
            document.getElementById('numeroMesa').textContent = numeroMesa;
            document.getElementById('modalPedido').style.display = 'flex';

            // Resetear pedido
            pedidoActual = {};
            actualizarResumen();

            // Resetear cantidades
            document.querySelectorAll('.qty-below').forEach(el => el.textContent = '0');
        }

        function cerrarModal() {
            document.getElementById('modalPedido').style.display = 'none';
            mesaIdActual = null;
        }

        function cambiarCantidad(productoId, cambio) {
            const elemento = document.getElementById(`qty-${productoId}`);
            let cantidad = parseInt(elemento.textContent) + cambio;

            if (cantidad < 0) cantidad = 0;

            elemento.textContent = cantidad;

            if (cantidad > 0) {
                pedidoActual[productoId] = cantidad;
            } else {
                delete pedidoActual[productoId];
            }

            actualizarResumen();
        }

        function actualizarResumen() {
            const orderItems = document.getElementById('orderItems');
            const orderTotal = document.getElementById('orderTotal');
            const registerBtn = document.getElementById('registerBtn');

            if (Object.keys(pedidoActual).length === 0) {
                orderItems.innerHTML = '<div class="empty-order">No hay productos seleccionados</div>';
                orderTotal.textContent = '$0.00';
                registerBtn.disabled = true;
                return;
            }

            let total = 0;
            let html = '';

            Object.keys(pedidoActual).forEach(productoId => {
                const producto = productosData.find(p => p.id == productoId);
                const cantidad = pedidoActual[productoId];
                const subtotal = producto.precio * cantidad;
                total += subtotal;

                html += `
                    <div class="order-item">
                        <div class="order-item-image">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="order-item-details">
                            <div class="order-item-name">${producto.nombre}</div>
                            <div class="order-item-price">$${parseFloat(producto.precio).toFixed(2)} c/u</div>
                        </div>
                        <div class="order-item-quantity">x${cantidad}</div>
                    </div>
                `;
            });

            orderItems.innerHTML = html;
            orderTotal.textContent = `$${total.toFixed(2)}`;
            registerBtn.disabled = false;
        }

        function registrarPedido() {
    const productos = {};

    // Buscar las cantidades correctamente usando la clase .qty-below
    document.querySelectorAll('.qty-below').forEach(el => {
        const cantidad = parseInt(el.textContent);
        if (cantidad > 0) {
            const productoId = el.id.split('-')[1];
            productos[productoId] = cantidad;
        }
    });

    if (Object.keys(productos).length === 0) {
        alert('Debe seleccionar al menos un producto');
        return;
    }

    // Usar confirmación simple en lugar de Orbitalert
    const confirmar = confirm('¿Confirmar pedido con ' + Object.keys(productos).length + ' producto(s)?');
    if (!confirmar) {
        return;
    }

    // Usar URLSearchParams para envío más confiable
    const params = new URLSearchParams();
    params.append('mesa_id', mesaIdActual);
    params.append('mesero_id', <?= $mesero_id ?>);
    params.append('sucursal_id', <?= $sucursal_id ?>);
    params.append('productos', JSON.stringify(productos));

    // Enviar action en la URL en lugar del body
    fetch('../../controllers/mesero/dashboard_controller.php?action=crear', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Accept': 'application/json'
        },
        body: params
    })
    .then(response => {
        // Log de la respuesta para debugging
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        
        // Verificar si la respuesta es OK
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Error del servidor:', text);
                throw new Error(`Error HTTP ${response.status}: ${text}`);
            });
        }
        
        // Intentar parsear la respuesta como JSON
        return response.text().then(text => {
            console.log('Respuesta del servidor:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                console.error('Respuesta recibida:', text);
                throw new Error('Respuesta inválida del servidor');
            }
        });
    })
    .then(data => {
        console.log('Datos procesados:', data);
        
        if (data && data.success) {
            alert('Pedido registrado exitosamente');
            cerrarModal();
            location.reload();
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        alert('Error al registrar pedido: ' + error.message);
    });
}

        // Cerrar modal al hacer clic fuera
        window.onclick = function (event) {
            const modal = document.getElementById('modalPedido');
            if (event.target == modal) {
                cerrarModal();
            }
        }

        // Actualización automática cada 30 segundos
        setInterval(() => {
            location.reload();
        }, 3000000);

       // Función para gestionar una mesa
function gestionarMesa(mesaId, numeroMesa, mesaData) {
    if (mesaData.estado === 'cerrada') {
        alert('Esta mesa está cerrada y no puede ser atendida.');
        return;
    }
    
    mesaIdActual = mesaId;
    
    if (mesaData.estado === 'libre') {
        // Mesa libre - mostrar modal vacío
        document.getElementById('modalPedido').style.display = 'flex';
        document.querySelector('#modalPedido h2').textContent = `Mesa ${numeroMesa} - Nuevo Pedido`;
        pedidoActual = {};
        document.querySelectorAll('.qty-below').forEach(el => el.textContent = '0');
        
        // Habilitar todos los controles
        document.querySelectorAll('.qty-btn-below').forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
        const registerBtn = document.getElementById('registerBtn');
        registerBtn.disabled = false;
        registerBtn.textContent = 'Registrar Pedido';
    } else if (mesaData.estado === 'ocupada' || mesaData.estado === 'en_preparacion') {
        // Verificar si hay un pedido activo
        if (!mesaData.pedido_id) {
            // Corregir el estado de la mesa si no tiene pedido
            fetch('../../controllers/mesero/dashboard_controller.php?action=corregir_estado_mesa', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    mesa_id: mesaId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Recargar para mostrar el estado correcto
                } else {
                    console.error('Error al corregir estado de mesa:', data.message);
                    alert('Error: La mesa aparece como ocupada pero no tiene un pedido asociado. Se ha corregido el estado.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al corregir el estado de la mesa');
            });
            return;
        }
        
        // Cargar pedido existente
        cargarPedidoExistente(mesaData.pedido_id);
    }
}
// Función para cargar un pedido existente
function cargarPedidoExistente(pedidoId) {
    fetch(`../../controllers/mesero/dashboard_controller.php?action=obtener_pedido&pedido_id=${pedidoId}`)
        .then(response => {
            // Log de la respuesta para debugging
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('content-type'));
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Error del servidor:', text);
                    throw new Error(`Error HTTP ${response.status}: ${text}`);
                });
            }
            
            return response.text().then(text => {
                console.log('Respuesta del servidor:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error al parsear JSON:', e);
                    console.error('Respuesta recibida:', text);
                    throw new Error('Respuesta inválida del servidor');
                }
            });
        })
        .then(data => {
            console.log('Datos procesados:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar el pedido');
            }
            
            if (!data.data || !data.data.pedido || !data.data.productos) {
                throw new Error('Datos del pedido incompletos');
            }
            
            const pedido = data.data.pedido;
            const productos = data.data.productos;
            
            // Mostrar modal
            document.getElementById('modalPedido').style.display = 'flex';
            
            // Actualizar título del modal con información del pedido
            const modalTitle = document.querySelector('#modalPedido h2');
            modalTitle.textContent = `Mesa ${pedido.mesa_numero || 'N/A'} - Mesero: ${pedido.mesero_nombre || 'Sin asignar'}`;
            
            // Resetear cantidades actuales
            document.querySelectorAll('.qty-below').forEach(el => el.textContent = '0');
            
            // Inicializar pedido actual
            pedidoActual = {};
            
            // Cargar productos existentes
            if (Array.isArray(productos)) {
                productos.forEach(prod => {
                    if (prod && prod.producto_id && prod.cantidad) {
                        pedidoActual[prod.producto_id] = prod.cantidad;
                        const qtyElement = document.getElementById(`qty-${prod.producto_id}`);
                        if (qtyElement) {
                            qtyElement.textContent = prod.cantidad;
                        }
                    }
                });
            }
            
            // Actualizar resumen del pedido
            actualizarResumen();
            
            // Si el pedido está en preparación, deshabilitar edición
            const estaEnPreparacion = pedido.estado === 'en_preparacion';
            if (estaEnPreparacion) {
                document.querySelectorAll('.qty-btn-below').forEach(btn => {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                });
                const registerBtn = document.getElementById('registerBtn');
                registerBtn.disabled = true;
                registerBtn.textContent = 'Pedido en preparación';
            }
        })
        .catch(error => {
            console.error('Error al cargar el pedido:', error);
            alert('Error al cargar el pedido: ' + error.message);
            cerrarModal();
        });
}
// Función para actualizar la barra de progreso y las mesas
function actualizarDashboard() {
    fetch('../../controllers/mesero/dashboard_controller.php?action=obtener_estado')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar barra de progreso
                const porcentajeMeta = Math.min(100, (data.mesas_atendidas / data.meta_diaria) * 100);
                document.querySelector('.bg-gradient-to-r').style.width = `${porcentajeMeta}%`;
                
                // Actualizar texto de mesas atendidas
                const metaText = document.querySelector('.text-gray-300');
                metaText.textContent = `Meta diaria: ${data.meta_diaria} mesas`;
                
                const progressText = document.querySelector('.text-green-400');
                progressText.textContent = `${data.mesas_atendidas}/${data.meta_diaria}`;
                
                // Actualizar estado de las mesas
                data.mesas.forEach(mesa => {
                    const mesaElement = document.querySelector(`[data-mesa-id="${mesa.id}"]`);
                    if (mesaElement) {
                        // Actualizar estado visual de la mesa
                        mesaElement.className = `text-center glass-card-1 p-4 rounded-2xl shadow-lg hover:scale-105 transition-transform duration-300 ${mesa.estado === 'cerrada' ? 'cursor-not-allowed opacity-75' : 'cursor-pointer'}`;
                        
                        // Actualizar icono y color
                        const iconElement = mesaElement.querySelector('.mesa-icon');
                        iconElement.className = `mesa-icon mesa-${mesa.estado} mx-auto mb-2 flex items-center justify-center rounded-full`;
                        
                        // Actualizar información del mesero y total si existe
                        const infoHtml = mesa.mesero_nombre ? 
                            `<p class="text-xs text-yellow-400 mt-1">Atendida por: ${mesa.mesero_nombre}</p>` : '';
                        const totalHtml = mesa.total ? 
                            `<p class="text-xs text-green-400 mt-1">Total: $${parseFloat(mesa.total).toFixed(2)}</p>` : '';
                        
                        mesaElement.innerHTML = `
                            ${iconElement.outerHTML}
                            <p class="text-white font-semibold">Mesa ${mesa.numero}</p>
                            <p class="text-sm text-gray-300">${mesa.estado.replace('_', ' ')}</p>
                            ${infoHtml}
                            ${totalHtml}
                        `;
                    }
                });
            }
        })
        .catch(error => console.error('Error al actualizar dashboard:', error));
}

// Actualizar cada 30 segundos
setInterval(actualizarDashboard, 30000);

// Actualizar al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    actualizarDashboard();
    actualizarHora();
});

        // Función para probar el controlador
function testController() {
    console.log('Probando conexión con el controlador...');
    fetch('../../controllers/mesero/dashboard_controller.php?test=1')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Respuesta del controlador:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                throw new Error('Respuesta inválida del controlador');
            }
        })
        .then(data => {
            console.log('Test del controlador exitoso:', data);
        })
        .catch(error => {
            console.error('Test del controlador fallido:', error);
        });
}

// Ejecutar test al cargar la página
document.addEventListener('DOMContentLoaded', testController);

       
            // Actualizar hora en tiempo real
            function actualizarHora() {
                const ahora = new Date();
                const opciones = {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                };
                document.getElementById('horaActual').textContent = ahora.toLocaleTimeString('es-ES', opciones);
            }

            setInterval(actualizarHora, 1000);
            actualizarHora();
    </script>
</body>

</html>