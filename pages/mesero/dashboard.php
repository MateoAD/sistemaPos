<?php
session_start();
require_once '../../controllers/db.php';

// Verificar que sea mesero
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'mesero') {
    header('Location: ../../login.php');
    exit();
}

$mesero_id = $_SESSION['user_id'];

// Obtener mesas
$stmt = $pdo->prepare("SELECT m.*, s.nombre as sucursal_nombre FROM mesas m 
                       JOIN sucursales s ON m.sucursal_id = s.id 
                       ORDER BY m.numero");
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mesero - Sistema POS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .dashboard-title {
            color: white;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-align: center;
        }

        .user-info {
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            font-size: 1.2em;
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
            font-size: 4em;
            margin-bottom: 15px;
        }

        .mesa-libre { color: #4CAF50; }
        .mesa-ocupada { color: #f44336; }
        .mesa-cerrada { color: #9e9e9e; }
        .mesa-preparacion { color: #ff9800; }

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

        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-3px);
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

        .product-price {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .quantity-btn {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 1.2em;
            transition: background 0.3s;
        }

        .quantity-btn:hover {
            background: #764ba2;
        }

        .quantity {
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }

        .order-items {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .order-item-image {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5em;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .order-item-price {
            color: #667eea;
        }

        .order-item-quantity {
            font-weight: bold;
            color: #333;
        }

        .order-total {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 20px;
        }

        .total-amount {
            font-size: 2em;
            font-weight: bold;
        }

        .register-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.2em;
            cursor: pointer;
            transition: transform 0.3s;
            width: 100%;
        }

        .register-btn:hover {
            transform: translateY(-2px);
        }

        .register-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
        }

        .empty-order {
            text-align: center;
            color: #666;
            font-style: italic;
            margin-top: 50px;
        }

        @media (max-width: 768px) {
            .modal-body {
                flex-direction: column;
                height: auto;
                max-height: 80vh;
            }
            
            .products-section, .order-section {
                border-right: none;
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1 class="dashboard-title">Dashboard Mesero</h1>
        <div class="user-info">
            Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Mesero') ?>
        </div>
    </div>

    <div class="mesas-container">
        <?php foreach ($mesas as $mesa): ?>
            <div class="mesa-card" onclick="abrirModalPedido(<?= $mesa['id'] ?>, '<?= $mesa['numero'] ?>')">
                <div class="mesa-icon mesa-<?= $mesa['estado'] ?>">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="mesa-numero">Mesa <?= $mesa['numero'] ?></div>
                <div class="mesa-estado"><?= ucfirst(str_replace('_', ' ', $mesa['estado'])) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal de Pedidos -->
    <div id="modalPedido" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Nuevo Pedido - Mesa <span id="numeroMesa"></span></h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="products-section">
                    <h3>Productos Disponibles</h3>
                    <div class="product-grid">
                        <?php foreach ($productos as $producto): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div class="product-name"><?= htmlspecialchars($producto['nombre']) ?></div>
                                <div class="product-price">$<?= number_format($producto['precio'], 2) ?></div>
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="cambiarCantidad(<?= $producto['id'] ?>, -1)">-</button>
                                    <span class="quantity" id="qty-<?= $producto['id'] ?>">0</span>
                                    <button class="quantity-btn" onclick="cambiarCantidad(<?= $producto['id'] ?>, 1)">+</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="order-section">
                    <h3>Resumen del Pedido</h3>
                    <div class="order-items" id="orderItems">
                        <div class="empty-order">No hay productos seleccionados</div>
                    </div>
                    <div class="order-total">
                        <div>Total:</div>
                        <div class="total-amount" id="orderTotal">$0.00</div>
                    </div>
                    <button class="register-btn" id="registerBtn" onclick="registrarPedido()" disabled>
                        Registrar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let mesaIdActual = null;
        let productosData = <?= json_encode($productos) ?>;
        let pedidoActual = {};

        function abrirModalPedido(mesaId, numeroMesa) {
            mesaIdActual = mesaId;
            document.getElementById('numeroMesa').textContent = numeroMesa;
            document.getElementById('modalPedido').style.display = 'block';
            
            // Resetear pedido
            pedidoActual = {};
            actualizarResumen();
            
            // Resetear cantidades
            document.querySelectorAll('.quantity').forEach(el => el.textContent = '0');
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
            if (!mesaIdActual || Object.keys(pedidoActual).length === 0) {
                alert('Por favor selecciona al menos un producto');
                return;
            }

            const formData = new FormData();
            formData.append('mesa_id', mesaIdActual);
            formData.append('mesero_id', <?= $mesero_id ?>);
            formData.append('sucursal_id', <?= $sucursal_id ?>);
            formData.append('productos', JSON.stringify(pedidoActual));

            fetch('../../controllers/dashboard_controller.php?action=crear', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Pedido registrado exitosamente');
                    cerrarModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al registrar el pedido');
            });
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalPedido');
            if (event.target == modal) {
                cerrarModal();
            }
        }

        // Actualización automática cada 30 segundos
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>