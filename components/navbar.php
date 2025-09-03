<?php
require_once '../../controllers/db.php';
require_once '../../controllers/admin/dashboard_controller.php';
// Variables dinámicas para el navbar
$total_ventas = $total_ventas ?? 0;
$total_empleados = $total_empleados ?? 0;
$total_menu = $total_menu ?? 0;
$total_mesas = $total_mesas ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar - POS Restaurante</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

        .mobile-menu-btn {
            display: none;
        }

        @media (max-width: 1024px) {
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1000;
                background: rgba(0, 0, 0, 0.5);
                color: white;
                padding: 0.75rem;
                border-radius: 0.5rem;
                backdrop-filter: blur(10px);
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button id="mobileMenuBtn" class="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Navigation -->
    <div id="sidebar" class="sidebar fixed top-0 left-0 w-72 h-full z-40 transition-all duration-300">
        <div class="p-6 border-b border-white border-opacity-20">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-pink-500 rounded-xl flex items-center justify-center mr-4">
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
            <a href="dashboard.php" class="nav-link">
                <div class="nav-item active" data-page="dashboard">
                    <div class="px-6 py-4 flex items-center text-white">
                        <i class="fas fa-tachometer-alt text-xl mr-4"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </div>
                </div>
            </a>

            <a href="ventas.php" class="nav-link">
                <div class="nav-item" data-page="ventas">
                    <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white">
                        <i class="fas fa-cash-register text-xl mr-4"></i>
                        <span class="sidebar-text">Ventas</span>
                        <span class="sidebar-text ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $total_ventas ?></span>
                    </div>
                </div>
            </a>

            <a href="empleados.php" class="nav-link">
                <div class="nav-item" data-page="empleados">
                    <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white">
                        <i class="fas fa-users text-xl mr-4"></i>
                        <span class="sidebar-text">Empleados</span>
                        <span class="sidebar-text ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $total_empleados ?></span>
                    </div>
                </div>
            </a>

            <a href="menu.php" class="nav-link">
                <div class="nav-item" data-page="menu">
                    <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white">
                        <i class="fas fa-utensils text-xl mr-4"></i>
                        <span class="sidebar-text">Menú</span>
                        <span class="sidebar-text ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $total_menu ?></span>
                    </div>
                </div>
            </a>

            <a href="mesas.php" class="nav-link">
                <div class="nav-item" data-page="mesas">
                    <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white">
                        <i class="fas fa-table text-xl mr-4"></i>
                        <span class="sidebar-text">Mesas</span>
                        <span class="sidebar-text ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $total_mesas ?></span>
                    </div>
                </div>
            </a>

            <a href="analytics.php" class="nav-link">
                <div class="nav-item" data-page="analytics">
                    <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white">
                        <i class="fas fa-chart-line text-xl mr-4"></i>
                        <span class="sidebar-text">Analytics</span>
                        <div class="sidebar-text ml-auto pulse-dot"></div>
                    </div>
                </div>
            </a>
        </nav>

        <div class="p-6 border-t border-white border-opacity-20">
            <button class="nav-item w-full" onclick="logout()">
                <div class="px-4 py-3 flex items-center text-gray-300 hover:text-white">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span class="sidebar-text">Cerrar Sesión</span>
                </div>
            </button>
        </div>
    </div>

    <script>
        // Variables globales
        let sidebarCollapsed = false;

        // Inicialización cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            initializeNavbar();
            setActivePage();
        });

        function initializeNavbar() {
            // Toggle sidebar
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }

            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', toggleMobileSidebar);
            }

            // Manejar clicks en elementos del menú
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    // Si no es un enlace directo, manejar la navegación
                    const link = this.closest('.nav-link') || this.closest('a');
                    if (!link) {
                        e.preventDefault();
                        
                        // Remover clase active de todos los elementos
                        document.querySelectorAll('.nav-item').forEach(nav => {
                            nav.classList.remove('active');
                        });
                        
                        // Agregar clase active al elemento clickeado
                        this.classList.add('active');
                    }
                });
            });

            // Ajustar sidebar según el tamaño de pantalla
            window.addEventListener('resize', handleResize);
            handleResize();
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const mainContent = document.querySelector('.main-content');

            if (!sidebar || !toggleBtn) return;

            sidebarCollapsed = !sidebarCollapsed;

            if (sidebarCollapsed) {
                sidebar.classList.add('sidebar-collapsed');
                if (mainContent) {
                    mainContent.classList.remove('ml-72');
                    mainContent.classList.add('ml-20');
                }
                toggleBtn.innerHTML = '<i class="fas fa-angle-right text-xl"></i>';
                
                // Ocultar texto del sidebar
                document.querySelectorAll('.sidebar-text').forEach(el => {
                    el.style.display = 'none';
                });
            } else {
                sidebar.classList.remove('sidebar-collapsed');
                if (mainContent) {
                    mainContent.classList.remove('ml-20');
                    mainContent.classList.add('ml-72');
                }
                toggleBtn.innerHTML = '<i class="fas fa-angle-left text-xl"></i>';
                
                // Mostrar texto del sidebar
                document.querySelectorAll('.sidebar-text').forEach(el => {
                    el.style.display = 'block';
                });
            }
        }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('mobile-open');
            }
        }

        function handleResize() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (window.innerWidth <= 1024) {
                // Modo móvil
                if (mainContent) {
                    mainContent.style.marginLeft = '0';
                }
            } else {
                // Modo escritorio
                if (!sidebar.classList.contains('sidebar-collapsed')) {
                    if (mainContent) {
                        mainContent.style.marginLeft = '18rem'; // 72px * 4 = 288px
                    }
                } else {
                    if (mainContent) {
                        mainContent.style.marginLeft = '5rem'; // 80px
                    }
                }
            }
        }

        function setActivePage() {
            // Obtener la página actual desde la URL
            const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
            
            // Remover active de todos los elementos
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Agregar active a la página actual
            const activeElement = document.querySelector(`[data-page="${currentPage}"]`);
            if (activeElement) {
                activeElement.classList.add('active');
            }
        }

        function logout() {
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                // Aquí puedes agregar la lógica de logout
                window.location.href = '/sistemaPos/logout.php';
            }
        }

        // Función para actualizar contadores (opcional)
        function updateCounts(ventas, empleados, menu, mesas) {
            const ventasSpan = document.querySelector('[data-page="ventas"] .bg-red-500');
            const empleadosSpan = document.querySelector('[data-page="empleados"] .bg-red-500');
            const menuSpan = document.querySelector('[data-page="menu"] .bg-red-500');
            const mesasSpan = document.querySelector('[data-page="mesas"] .bg-red-500');

            if (ventasSpan) ventasSpan.textContent = ventas;
            if (empleadosSpan) empleadosSpan.textContent = empleados;
            if (menuSpan) menuSpan.textContent = menu;
            if (mesasSpan) mesasSpan.textContent = mesas;
        }

        // Función para cerrar sidebar en móvil al hacer clic fuera
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const mobileBtn = document.getElementById('mobileMenuBtn');
            
            if (window.innerWidth <= 1024 && 
                !sidebar.contains(e.target) && 
                !mobileBtn.contains(e.target) && 
                sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
            }
        });
    </script>
</body>
</html>