<?php
require_once __DIR__ . '/../../controllers/db.php';
require_once __DIR__ . '/../../controllers/admin/dashboard_controller.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - POS Restaurante</title>
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

        .sidebar {
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.7) 100%);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .sidebar-collapsed {
            width: 80px !important;
        }

        .nav-item {
            position: relative;
            transition: all 0.3s ease;
            border-radius: 12px;
            margin: 8px 16px;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-item:hover::before {
            left: 100%;
        }

        .nav-item:hover {
            transform: translateX(8px);
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-item.active {
            background: linear-gradient(135deg, #FF6B6B, #4ECDC4);
            transform: translateX(8px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
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

        .chart-container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .chart-container:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-5px);
        }

        .floating-widget {
            position: fixed;
            top: 50%;
            right: 30px;
            transform: translateY(-50%);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .floating-widget:hover {
            transform: translateY(-50%) scale(1.05);
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



        .notification {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            margin: 8px 0;
            opacity: 0;
            transform: translateX(100%);
            animation: slideIn 0.5s ease forwards;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            stroke-dasharray: 283;
            stroke-dashoffset: 283;
            transition: stroke-dashoffset 0.35s;
        }

        .glow-effect {
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>

<body>
    <!-- Mobile Menu Button -->
    <button id="mobileMenuBtn"
        class="lg:hidden fixed top-4 left-4 z-50 bg-black bg-opacity-50 text-white p-3 rounded-xl backdrop-blur-sm">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Navigation -->
    <div id="sidebar" class="sidebar fixed top-0 left-0 w-72 h-full z-40 transition-all duration-300">
        <div class="p-6 border-b border-white border-opacity-20">
            <div class="flex items-center">
                <div
                    class="w-12 h-12 bg-gradient-to-r from-red-500 to-pink-500 rounded-xl flex items-center justify-center mr-4">
                    <i class="fas fa-utensils text-white text-xl"></i>
                </div>
                <div class="flex items-center mb-4">
                    <div class="sidebar-text">
                        <p class="text-white font-semibold text-sm">Admin</p>
                        <p class="text-gray-300 text-xs">Administrador</p>
                    </div>
                </div>
            </div>
            <button id="sidebarToggle" class="absolute top-6 right-4 text-white hover:text-gray-300">
                <i class="fas fa-angle-left text-xl"></i>
            </button>
        </div>

        <nav class="mt-6 flex-1">
            <div class="nav-item active">
                <div class="px-6 py-4 flex items-center text-white">
                    <i class="fas fa-tachometer-alt text-xl mr-4"></i>
                    <span class="sidebar-text">Dashboard</span>
                </div>
            </div>

            <!-- Ventas -->
             <a href="ventas.php">
            <div class="nav-item">
                <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white cursor-pointer">
                    <i class="fas fa-cash-register text-xl mr-4"></i>
                    <span class="sidebar-text">Ventas</span>
                    <span
                        class="sidebar-text ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $total_ventas ?></span>
                </div>
            </div>
    </a>

            <!-- Empleados -->
              <a href="empleados.php">
            <div class="nav-item">
                <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white cursor-pointer">
                    <i class="fas fa-users text-xl mr-4"></i>
                    <span class="sidebar-text">Empleados</span>
                    <span
                        class="sidebar-text ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $total_empleados ?></span>
                </div>
            </div>
            </a>
            <!-- Menú -->
             <a href="menu.php">
            <div class="nav-item">
                <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white cursor-pointer">
                    <i class="fas fa-utensils text-xl mr-4"></i>
                    <span class="sidebar-text">Menú</span>
                    <span
                        class="sidebar-text ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $total_menu ?></span>
                </div>
            </div>
            </a>
            <!-- Mesas -->
            <div class="nav-item">
                <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white cursor-pointer">
                    <i class="fas fa-table text-xl mr-4"></i>
                    <span class="sidebar-text">Mesas</span>
                    <span
                        class="sidebar-text ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $total_mesas ?></span>
                </div>
            </div>

            <div class="nav-item">
                <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white cursor-pointer">
                    <i class="fas fa-chart-line text-xl mr-4"></i>
                    <span class="sidebar-text">Analytics</span>
                    <div class="sidebar-text ml-auto pulse-dot"></div>
                </div>
            </div>

        </nav>

        <div class="p-6 border-t border-white border-opacity-20">
            <button class="nav-item w-full">
                <div class="px-4 py-3 flex items-center text-gray-300 hover:text-white">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span class="sidebar-text">Cerrar Sesión</span>
                </div>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="ml-72 transition-all duration-300 p-8">
        <!-- Header -->
        <header class="glass-card rounded-2xl p-6 mb-8">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">Panel de Control</h2>
                    <div class="flex items-center text-gray-200">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <span id="currentDateTime"></span>
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
        </header>

        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Ventas del día -->
            <div class="metric-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 p-3 rounded-xl">
                        <i class="fas fa-dollar-sign text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-300">vs ayer</p>
                        <p
                            class="text-sm font-semibold <?php echo $porcentaje_cambio >= 0 ? 'text-green-400' : 'text-red-400'; ?>">
                            <?php echo ($porcentaje_cambio >= 0 ? '+' : '') . number_format($porcentaje_cambio, 1) . '%'; ?>
                        </p>
                    </div>
                </div>
                <h3 class="text-gray-300 text-sm mb-1">Ventas Hoy</h3>
                <p class="text-3xl font-bold text-white mb-2">$<?php echo number_format($ventas_dia, 2); ?></p>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-2 rounded-full" style="width: 65%"></div>
                </div>
            </div>

            <!-- Órdenes Activas -->
            <div class="metric-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 p-3 rounded-xl">
                        <i class="fas fa-receipt text-white text-xl"></i>
                    </div>
                    <p class="text-orange-400 text-sm"><?php echo $en_preparacion; ?> en preparación</p>
                </div>
                <h3 class="text-gray-300 text-sm mb-1">Órdenes Activas</h3>
                <p class="text-3xl font-bold text-white mb-2"><?php echo $mesas_ocupadas; ?></p>

            </div>

            <!-- Mesas Ocupadas -->
            <div class="metric-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 p-3 rounded-xl">
                        <i class="fas fa-chair text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-300">Disponibles</p>
                        <p class="text-emerald-400 text-sm font-semibold"><?php echo $mesas_disponibles; ?> mesas</p>
                    </div>
                </div>
                <h3 class="text-gray-300 text-sm mb-1">Ocupación</h3>
                <p class="text-3xl font-bold text-white mb-2"><?php echo $mesas_ocupadas; ?>/<?php echo $total_mesas; ?>
                </p>
                <div class="flex space-x-1">
                    <?php
                    $barras_verdes = floor($porcentaje_ocupacion / 25);
                    for ($i = 0; $i < 4; $i++) {
                        $clase = $i < $barras_verdes ? 'bg-emerald-500' : 'bg-gray-600';
                        echo "<div class='flex-1 h-2 $clase rounded'></div>";
                    }
                    ?>
                </div>
            </div>

            <!-- Ventas TOTALES -->
            <div class="metric-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 p-3 rounded-xl">
                        <i class="fas fa-dollar-sign text-white text-xl"></i>
                    </div>
                </div>
                <h3 class="text-gray-300 text-sm mb-1">Ventas Totales</h3>
                <p class="text-3xl font-bold text-white mb-2">$<?php echo number_format($ventas_totales, 2); ?></p>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-2 rounded-full"
                        style="width: <?php echo $porcentaje_ventas; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
            <!-- Ventas Chart -->
            <div class="chart-container p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-white">Tendencia de Ventas</h3>
                    <div class="flex space-x-2">
                        <select
                            class="bg-gray-800 text-white border border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="7">7 días</option>
                            <option value="15">15 días</option>
                            <option value="30">30 días</option>
                        </select>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="ventasChart"></canvas>
                </div>
            </div>

            <!-- Productos Populares -->
            <div class="chart-container p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-white">Top Productos</h3>
                    <button class="text-blue-400 hover:text-blue-300 text-sm">Ver todos</button>
                </div>
                <div class="space-y-4">
                    <?php foreach ($productos_mas_vendidos as $producto): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-800 bg-opacity-50 rounded-xl">
                            <div class="flex items-center">
                                <img src="https://via.placeholder.com/50/FF6B6B/FFFFFF?text=<?= substr($producto['nombre'], 0, 2) ?>"
                                    alt="<?= $producto['nombre'] ?>" class="w-12 h-12 rounded-lg mr-4">
                                <div>
                                    <p class="text-white font-semibold"><?= $producto['nombre'] ?></p>
                                    <p class="text-gray-400 text-sm"><?= $producto['cantidad'] ?> vendidas hoy</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-green-400 font-semibold">$<?= number_format($producto['total'], 2) ?></p>
                                <div class="w-20 bg-gray-700 rounded-full h-2 mt-1">
                                    <div class="bg-green-400 h-2 rounded-full"
                                        style="width: <?= min(100, ($producto['cantidad'] / 10) * 100) ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
            // Variables globales
            let sidebarCollapsed = false;

            // Inicialización
            document.addEventListener('DOMContentLoaded', function () {
                initializeCharts();
                updateDateTime();
                startRealTimeUpdates();
                initializeEventListeners();

                // Actualizar fecha y hora cada segundo
                setInterval(updateDateTime, 1000);
            });

            function initializeEventListeners() {
                // Toggle sidebar
                document.getElementById('sidebarToggle').addEventListener('click', toggleSidebar);
                document.getElementById('mobileMenuBtn').addEventListener('click', toggleMobileSidebar);

                // Nav items
                document.querySelectorAll('.nav-item').forEach(item => {
                    item.addEventListener('click', function () {
                        document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                        this.classList.add('active');
                    });
                });
            }

            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.getElementById('mainContent');
                const toggleBtn = document.getElementById('sidebarToggle').querySelector('i');

                sidebarCollapsed = !sidebarCollapsed;

                if (sidebarCollapsed) {
                    sidebar.classList.add('sidebar-collapsed');
                    mainContent.classList.remove('ml-72');
                    mainContent.classList.add('ml-20');
                    toggleBtn.classList.remove('fa-angle-left');
                    toggleBtn.classList.add('fa-angle-right');

                    // Ocultar texto del sidebar
                    document.querySelectorAll('.sidebar-text').forEach(el => {
                        el.style.display = 'none';
                    });
                } else {
                    sidebar.classList.remove('sidebar-collapsed');
                    mainContent.classList.remove('ml-20');
                    mainContent.classList.add('ml-72');
                    toggleBtn.classList.remove('fa-angle-right');
                    toggleBtn.classList.add('fa-angle-left');

                    // Mostrar texto del sidebar
                    document.querySelectorAll('.sidebar-text').forEach(el => {
                        el.style.display = 'block';
                    });
                }
            }

            function toggleMobileSidebar() {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('mobile-open');
            }

            function updateDateTime() {
                const now = new Date();
                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                };
                document.getElementById('currentDateTime').textContent = now.toLocaleDateString('es-ES', options);
            }

            function initializeCharts() {
                // Obtener datos reales mediante AJAX
                fetch('controllers/admin/dashboard_controller.php?action=get_ventas')
                    .then(response => response.json())
                    .then(data => {
                        // Gráfico de ventas
                        const ventasCtx = document.getElementById('ventasChart').getContext('2d');
                        new Chart(ventasCtx, {
                            type: 'line',
                            data: {
                                labels: data.fechas,
                                datasets: [{
                                    label: 'Ventas ($)',
                                    data: data.ventas,
                                    borderColor: 'rgb(75, 192, 192)',
                                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                    tension: 0.4,
                                    fill: true,
                                    pointBackgroundColor: '#4ECDC4',
                                    pointBorderColor: '#ffffff',
                                    pointBorderWidth: 2,
                                    pointRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        labels: {
                                            color: 'white'
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        ticks: {
                                            color: 'rgba(255, 255, 255, 0.7)'
                                        },
                                        grid: {
                                            color: 'rgba(255, 255, 255, 0.1)'
                                        }
                                    },
                                    y: {
                                        ticks: {
                                            color: 'rgba(255, 255, 255, 0.7)',
                                            callback: function (value) {
                                                return '$' + value;
                                            }
                                        },
                                        grid: {
                                            color: 'rgba(255, 255, 255, 0.1)'
                                        }
                                    }
                                },
                                elements: {
                                    line: {
                                        borderWidth: 3
                                    }
                                }
                            }
                        });
                    })
                    .catch(error => console.error('Error:', error));
            }


            function addActivityToTimeline(activity) {
                const timeline = document.getElementById('activityTimeline');
                const now = new Date();
                const timeString = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

                const activityElement = document.createElement('div');
                activityElement.className = 'flex items-start mb-4';
                activityElement.innerHTML = `
        <div class="bg-blue-100 rounded-full p-2 mr-3">
            <i class="${activity.icon} text-blue-600"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-900">${activity.text}</p>
            <p class="text-xs text-gray-500">${timeString}</p>
        </div>
    `;

                timeline.insertBefore(activityElement, timeline.firstChild);

                // Limitar a 10 actividades
                if (timeline.children.length > 10) {
                    timeline.removeChild(timeline.lastChild);
                }
            }

            function getRealActivities() {
    return fetch('controllers/admin/dashboard_controller.php?action=get_activities')
        .then(response => response.json())
        .catch(error => console.error('Error:', error));
}

function updateRealActivities() {
    getRealActivities().then(activities => {
        const timeline = document.getElementById('activityTimeline');
        timeline.innerHTML = ''; // Limpiar timeline
        
        activities.forEach(activity => {
            addActivityToTimeline(activity);
        });
    });
}

function startRealTimeUpdates() {
    // Actualizar actividades cada 10 segundos
        // Actualizar actividades cada 10 segundos

    setInterval(updateRealActivities, 10000);
    
    // Actualizar gráficos cada 30 segundos
    setInterval(initializeCharts, 30000);
    
    // Cargar datos iniciales
    updateRealActivities();
    initializeCharts();
}
        </script>
</body>

</html>