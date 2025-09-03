<?php
require_once '../../controllers/db.php';

// Obtener estadísticas generales
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_ventas,
    SUM(total) as total_dinero,
    (SELECT COUNT(*) FROM ventas v2 
     WHERE DATE(v2.fecha) = CURDATE()) as ventas_hoy
FROM ventas");
$stmt->execute();
$estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener método de pago más utilizado (simulado con pedidos)
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    'Efectivo' as metodo_pago
FROM ventas 
GROUP BY metodo_pago 
ORDER BY total DESC 
LIMIT 1");
$stmt->execute();
$metodo_popular = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener ventas recientes (últimas 10)
$stmt = $pdo->prepare("SELECT 
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
ORDER BY v.fecha DESC
LIMIT 10");
$stmt->execute();
$ventas_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas - POS Restaurante</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B4513',
                        secondary: '#D97706',
                        accent: '#16A34A',
                        dark: '#1C1917',
                        light: '#FAF3E0'
                    }
                }
            }
        }
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }

        .metric-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FF6B6B, #4ECDC4, #45B7D1, #96CEB4);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .metric-card:hover::before {
            transform: scaleX(1);
        }

        .metric-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .table-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-glow {
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            transition: all 0.3s ease;
        }

        .btn-glow:hover {
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.6);
            transform: translateY(-2px);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #00ff88;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 255, 136, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(0, 255, 136, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(0, 255, 136, 0);
            }
        }

        .search-input {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .filter-select {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .filter-select option {
            background: #1f2937;
            color: white;
        }

        .table-row {
            transition: all 0.3s ease;
        }

        .table-row:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(5px);
        }
    </style>
</head>
<body>
    <?php include '../../components/navbar.php'; ?>
    
    <div class="main-content ml-72 transition-all duration-300 p-8">
        
        <!-- Header -->
        <header class="glass-card rounded-2xl p-6 mb-8">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">Gestión de Ventas</h2>
                    <div class="flex items-center text-gray-200">
                        <i class="fas fa-chart-line mr-2"></i>
                        <span>Control de ventas y estadísticas</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4 mt-4 lg:mt-0">
                    <div class="glass-card px-4 py-2 rounded-xl">
                        <div class="flex items-center text-white">
                            <div class="pulse-dot mr-2"></div>
                            <span class="text-sm">Sistema Activo</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- Modal de Detalles de Venta -->
<div id="modalDetalles" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="glass-card rounded-2xl p-8 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">Detalles de Venta</h2>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-white transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="contenidoModal" class="text-white">
            <!-- Contenido dinámico se cargará aquí -->
        </div>
        
        <div class="flex justify-end space-x-4 mt-8">
            <button onclick="imprimirVenta()" class="btn-glow bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition-all duration-300">
                <i class="fas fa-print mr-2"></i>Imprimir
            </button>
            <button onclick="descargarVenta()" class="btn-glow bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl transition-all duration-300">
                <i class="fas fa-download mr-2"></i>Descargar PDF
            </button>
        </div>
    </div>
</div>
        <!-- Cards de estadísticas glamurosas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Ventas -->
            <div class="metric-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-4 rounded-xl">
                        <i class="fas fa-shopping-cart text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-300 text-sm">Total Ventas</p>
                        <p class="text-purple-400 text-2xl font-bold"><?php echo number_format($estadisticas['total_ventas']); ?></p>
                    </div>
                </div>
                <h3 class="text-white text-xl font-bold mb-1">Ventas Realizadas</h3>
                <p class="text-gray-300 text-sm">Todas las ventas registradas</p>
                <div class="mt-4">
                    <span class="text-green-400 text-sm">+<?php echo $estadisticas['ventas_hoy']; ?> hoy</span>
                </div>
            </div>

            <!-- Método de Pago -->
            <div class="metric-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 p-4 rounded-xl">
                        <i class="fas fa-credit-card text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-300 text-sm">Método Popular</p>
                        <p class="text-blue-400 text-xl font-bold"><?php echo $metodo_popular['metodo_pago']; ?></p>
                    </div>
                </div>
                <h3 class="text-white text-xl font-bold mb-1">Forma de Pago</h3>
                <p class="text-gray-300 text-sm">Más utilizado por clientes</p>
                <div class="mt-4">
                    <span class="text-blue-400 text-sm"><?php echo $metodo_popular['total']; ?> usos</span>
                </div>
            </div>

            <!-- Total Dinero -->
            <div class="metric-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 p-4 rounded-xl">
                        <i class="fas fa-dollar-sign text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-300 text-sm">Total Recaudado</p>
                        <p class="text-green-400 text-2xl font-bold">$<?php echo number_format($estadisticas['total_dinero'], 2); ?></p>
                    </div>
                </div>
                <h3 class="text-white text-xl font-bold mb-1">Ingresos Totales</h3>
                <p class="text-gray-300 text-sm">Dinero ganado en ventas</p>
                <div class="mt-4">
                    <span class="text-green-400 text-sm">USD</span>
                </div>
            </div>
        </div>
             <!-- Tabla de Ventas -->
        <div class="glass-card rounded-2xl p-6">
                <!-- Filtros y Búsqueda -->
             <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-gray-300 text-sm mb-2">Buscar</label>
                    <input type="text" id="busqueda" placeholder="Buscar ventas..." 
                           class="w-full px-4 py-2 rounded-xl search-input text-white placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-2">Fecha</label>
                    <input type="date" id="filtroFecha" 
                           class="w-full px-4 py-2 rounded-xl filter-select">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-2">Cajero</label>
                    <select id="filtroCajero" class="w-full px-4 py-2 rounded-xl filter-select">
                        <option value="">Todos los cajeros</option>
                        <?php
                        $stmt = $pdo->query("SELECT DISTINCT u.id, u.nombre FROM usuarios u 
                                           JOIN ventas v ON u.id = v.cajero_id 
                                           WHERE u.rol_id = 3");
                        $cajeros = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($cajeros as $cajero) {
                            echo "<option value='{$cajero['id']}'>{$cajero['nombre']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-7">
                    <label class="block text-gray-300 text-sm mb-2">Sucursal</label>
                    <select id="filtroSucursal" class="w-full px-4 py-2 rounded-xl filter-select">
                        <option value="">Todas las sucursales</option>
                        <?php
                        $stmt = $pdo->query("SELECT id, nombre FROM sucursales WHERE estado = 'activa'");
                        $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($sucursales as $sucursal) {
                            echo "<option value='{$sucursal['id']}'>{$sucursal['nombre']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
                 <!-- Tabla -->
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-white">Últimas Ventas</h3>
                <button onclick="actualizarTabla()" class="btn-glow bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl transition-all duration-300">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <div class="table-glass rounded-xl">
                    <table class="w-full">
                        <thead class="bg-gray-800 bg-opacity-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">ID</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Fecha</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Mesa</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Cajero</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Sucursal</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Total</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyVentas" class="divide-y divide-gray-700">
                            <?php foreach ($ventas_recientes as $venta): ?>
                            <tr class="table-row">
                                <td class="px-6 py-4 text-gray-300">#<?php echo $venta['id']; ?></td>
                                <td class="px-6 py-4 text-white"><?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></td>
                                <td class="px-6 py-4 text-gray-300">Mesa <?php echo $venta['numero_mesa']; ?></td>
                                <td class="px-6 py-4 text-white font-medium"><?php echo $venta['cajero']; ?></td>
                                <td class="px-6 py-4 text-gray-300"><?php echo $venta['sucursal']; ?></td>
                                <td class="px-6 py-4 text-green-400 font-bold">$<?php echo number_format($venta['total'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <button onclick="verDetalles(<?php echo $venta['id']; ?>)" 
                                            class="text-blue-400 hover:text-blue-300 transition-colors">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <p class="text-gray-400 text-sm">Mostrando últimas 10 ventas. Desliza para ver más registros.</p>
            </div>
        </div>
    </div>

    <script>
        // Funciones de filtrado y búsqueda
        function actualizarTabla() {
            const busqueda = document.getElementById('busqueda').value;
            const fecha = document.getElementById('filtroFecha').value;
            const cajero = document.getElementById('filtroCajero').value;
            const sucursal = document.getElementById('filtroSucursal').value;
            
            // Construir URL con parámetros
            let url = `../../controllers/admin/ventas_controller.php?action=filtrarVentas`;
            if (busqueda) url += `&busqueda=${encodeURIComponent(busqueda)}`;
            if (fecha) url += `&fecha=${fecha}`;
            if (cajero) url += `&cajero=${cajero}`;
            if (sucursal) url += `&sucursal=${sucursal}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        actualizarTablaVentas(data.data);
                    }
                });
        }

        function actualizarTablaVentas(ventas) {
            const tbody = document.getElementById('tbodyVentas');
            tbody.innerHTML = '';
            
            if (ventas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-400">No hay ventas encontradas</td></tr>';
                return;
            }
            
            ventas.forEach(venta => {
                const tr = document.createElement('tr');
                tr.className = 'table-row';
                tr.innerHTML = `
                    <td class="px-6 py-4 text-gray-300">#${venta.id}</td>
                    <td class="px-6 py-4 text-white">${new Date(venta.fecha).toLocaleString('es-ES')}</td>
                    <td class="px-6 py-4 text-gray-300">Mesa ${venta.numero_mesa}</td>
                    <td class="px-6 py-4 text-white font-medium">${venta.cajero}</td>
                    <td class="px-6 py-4 text-gray-300">${venta.sucursal}</td>
                    <td class="px-6 py-4 text-green-400 font-bold">$${parseFloat(venta.total).toFixed(2)}</td>
                    <td class="px-6 py-4">
                        <button onclick="verDetalles(${venta.id})" 
                                class="text-blue-400 hover:text-blue-300 transition-colors">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

       function verDetalles(ventaId) {
    fetch(`../../controllers/admin/ventas_controller.php?action=getDetallesVenta&id=${ventaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const venta = data.data;
                const modal = document.getElementById('modalDetalles');
                const contenido = document.getElementById('contenidoModal');
                
                let productosHTML = '';
                venta.productos.forEach(producto => {
                    productosHTML += `
                        <tr class="border-b border-gray-600">
                            <td class="py-3">${producto.producto}</td>
                            <td class="py-3 text-center">${producto.cantidad}</td>
                            <td class="py-3 text-right">$${parseFloat(producto.precio_unitario).toFixed(2)}</td>
                            <td class="py-3 text-right font-bold">$${parseFloat(producto.subtotal).toFixed(2)}</td>
                        </tr>
                    `;
                });
                
                contenido.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="glass-card rounded-xl p-4">
                            <h3 class="text-lg font-semibold text-blue-400 mb-2">Información General</h3>
                            <p><strong>Venta ID:</strong> #${venta.id}</p>
                            <p><strong>Fecha:</strong> ${new Date(venta.fecha).toLocaleString('es-ES')}</p>
                            <p><strong>Mesa:</strong> ${venta.numero_mesa}</p>
                        </div>
                        <div class="glass-card rounded-xl p-4">
                            <h3 class="text-lg font-semibold text-green-400 mb-2">Ubicación y Personal</h3>
                            <p><strong>Sucursal:</strong> ${venta.sucursal}</p>
                            <p><strong>Cajero:</strong> ${venta.cajero}</p>
                        </div>
                    </div>
                    
                    <div class="glass-card rounded-xl p-4">
                        <h3 class="text-lg font-semibold text-purple-400 mb-4">Productos Vendidos</h3>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-600">
                                    <th class="text-left py-2">Producto</th>
                                    <th class="text-center py-2">Cantidad</th>
                                    <th class="text-right py-2">Precio Unit.</th>
                                    <th class="text-right py-2">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${productosHTML}
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-green-400">
                                    <td colspan="3" class="py-4 text-right font-bold text-xl">TOTAL:</td>
                                    <td class="py-4 text-right font-bold text-green-400 text-xl">$${parseFloat(venta.total).toFixed(2)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                `;
                
                modal.classList.remove('hidden');
            }
        });
}

function cerrarModal() {
    document.getElementById('modalDetalles').classList.add('hidden');
}

function imprimirVenta() {
    const contenido = document.getElementById('contenidoModal').innerHTML;
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
            <head>
                <title>Ticket de Venta</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .item { margin: 5px 0; }
                    .total { font-weight: bold; font-size: 1.2em; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>RESTAURANTE POS</h1>
                    <p>Ticket de Venta</p>
                </div>
                ${contenido}
            </body>
        </html>
    `);
    ventana.document.close();
    ventana.print();
}

function descargarVenta() {
    const contenido = document.getElementById('contenidoModal').innerHTML;
    const blob = new Blob([contenido], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `venta_${new Date().getTime()}.html`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalDetalles').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

        // Event listeners para filtros en tiempo real
        document.getElementById('busqueda').addEventListener('input', actualizarTabla);
        document.getElementById('filtroFecha').addEventListener('change', actualizarTabla);
        document.getElementById('filtroCajero').addEventListener('change', actualizarTabla);
        document.getElementById('filtroSucursal').addEventListener('change', actualizarTabla);

        // Actualizar cada 30 segundos
        setInterval(actualizarTabla, 30000);
    </script>
</body>
</html>