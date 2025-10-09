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

        .sidebar {
            background: #0F0F0F;
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .sidebar-collapsed {
            width: 80px !important;
        }

        .sidebar-collapsed .nav-item {
            margin: 2px 8px !important;
            padding: 8px 8px !important;
            justify-content: center !important;
        }

        .sidebar-collapsed .nav-item i {
            margin: 0 !important;
            font-size: 1rem !important;
        }

        .sidebar-collapsed .nav-item .sidebar-text {
            display: none !important;
        }

        .sidebar-collapsed .nav-item > div {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }

        /* Ajuste automático de elementos cuando sidebar cambia */
        .main-content {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            width: calc(100% - 288px) !important; /* 72 * 4 = 288px cuando sidebar expandido */
        }

        .sidebar-collapsed ~ .main-content {
            width: calc(100% - 80px) !important; /* 80px cuando sidebar colapsado */
        }

        /* Estilo WhatsApp para contadores en sidebar colapsado */
       .sidebar-collapsed .counter-badge {
    z-index: 9999 !important;
    background: linear-gradient(135deg, #ff6b35, #f7931e) !important;
    box-shadow: 0 2px 8px rgba(255, 107, 53, 0.4) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

.sidebar-collapsed .pulse-dot {
    z-index: 9998 !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.sidebar-collapsed .nav-item > div {
    position: relative !important;
}

        .sidebar-collapsed .nav-item {
            position: relative !important;
        }

        .sidebar-collapsed .pulse-dot {
            position: absolute !important;
            top: -5px !important;
            right: -5px !important;
            width: 8px !important;
            height: 8px !important;
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
            background: #1E201F;
        }

        .nav-item.active {
            background: #2C302F;
            transform: translateX(8px);
            box-shadow: 0 4px 15px rgba(44, 48, 47, 0.5);
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
                background: #1E201F;
                color: white;
                padding: 0.75rem;
                border-radius: 0.5rem;
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
            <a href="dashboard.php">
            <div class="nav-item active">
                <div class="px-6 py-4 flex items-center text-white">
                    <i class="fas fa-tachometer-alt text-xl mr-4"></i>
                    <span class="sidebar-text">Dashboard</span>
                </div>
            </div>
            </a>
            <!-- Ventas -->
             <a href="ventas.php">
            <div class="nav-item">
                <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white cursor-pointer">
                    <i class="fas fa-cash-register text-xl mr-4"></i>
                    <span class="sidebar-text">Ventas</span>
                    <span
                        class="sidebar-text ml-auto text-black font-semibold text-xs px-2 py-1 rounded-full counter-badge" style="background: linear-gradient(45deg, #ffea00, #ff7b00);"><?= $total_ventas ?></span>
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
                        class="sidebar-text ml-auto text-black font-semibold text-xs px-2 py-1 rounded-full counter-badge" style="background: linear-gradient(45deg, #ffea00, #ff7b00);"><?= $total_empleados ?></span>
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
                        class="sidebar-text ml-auto text-black font-semibold text-xs px-2 py-1 rounded-full counter-badge" style="background: linear-gradient(45deg, #ffea00, #ff7b00);"><?= $total_menu ?></span>
                </div>
            </div>
            </a>
            <!-- Mesas -->
             <a href="mesas.php">
            <div class="nav-item">
                <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white cursor-pointer">
                    <i class="fas fa-table text-xl mr-4"></i>
                    <span class="sidebar-text">Mesas</span>
                   <span
                        class="sidebar-text ml-auto text-black font-semibold text-xs px-2 py-1 rounded-full counter-badge" style="background: linear-gradient(45deg, #ffea00, #ff7b00);"><?= $total_mesas ?></span>
                </div>
            </div>
             </a>
            <!-- Analytics -->
             <a href="analytics.php">
            <div class="nav-item">
                <div class="px-6 py-4 flex items-center text-gray-300 hover:text-white cursor-pointer">
                    <i class="fas fa-chart-line text-xl mr-4"></i>
                    <span class="sidebar-text">Analytics</span>
                    <div class="sidebar-text ml-auto pulse-dot"></div>
                </div>
            </div>

        </nav>

        <div class="p-6 border-t border-white border-opacity-20 flex justify-center">
            <a href="../../logout.php"  class="nav-item w-full block">
                <div class="bg-red-500 hover:bg-red-600 px-4 py-3 flex items-center text-gray-300 hover:text-white rounded-lg transition-colors duration-200">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span class="sidebar-text">Cerrar Sesión</span>
                </div>
            </a>
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
            // Restaurar estado del sidebar desde localStorage
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                sidebarCollapsed = true;
                const sidebar = document.getElementById('sidebar');
                const toggleBtn = document.getElementById('sidebarToggle');
                
                if (sidebar && toggleBtn) {
                    sidebar.classList.add('sidebar-collapsed');
                    document.querySelectorAll('.main-content').forEach(content => {
                        content.classList.remove('ml-72');
                        content.classList.add('ml-20');
                    });
                    toggleBtn.innerHTML = '<i class="fas fa-angle-right text-xl"></i>';
                    document.querySelectorAll('.sidebar-text').forEach(el => {
                        el.style.display = 'none';
                    });
                }
            }

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

            if (!sidebar || !toggleBtn) return;

            sidebarCollapsed = !sidebarCollapsed;

            if (sidebarCollapsed) {
                // Colapsar sidebar
                sidebar.classList.add('sidebar-collapsed');
                
                // Ajustar todos los elementos main-content para usar el espacio disponible
                document.querySelectorAll('.main-content').forEach(content => {
                    content.classList.remove('ml-72');
                    content.classList.add('ml-20');
                    // Forzar reflow para animación suave
                    content.style.width = 'calc(100% - 80px)';
                });
                
                toggleBtn.innerHTML = '<i class="fas fa-angle-right text-xl"></i>';
                
                // Ocultar texto del sidebar
                document.querySelectorAll('.sidebar-text').forEach(el => {
                    el.style.display = 'none';
                });
                
                // Mostrar contadores estilo WhatsApp
                setTimeout(() => {
                    document.querySelectorAll('.counter-badge').forEach(counter => {
                        if (counter.closest('.sidebar-collapsed')) {
                            counter.style.display = 'flex';
                            counter.style.position = 'absolute';
                            counter.style.top = '2px';
                            counter.style.right = '2px';
                        }
                    });
                    document.querySelectorAll('.pulse-dot').forEach(dot => {
                        if (dot.closest('.sidebar-collapsed')) {
                            dot.style.display = 'block';
                        }
                    });
                }, 100);
                
            } else {
                // Expandir sidebar
                sidebar.classList.remove('sidebar-collapsed');
                
                // Ajustar todos los elementos main-content para el nuevo tamaño
                document.querySelectorAll('.main-content').forEach(content => {
                    content.classList.remove('ml-20');
                    content.classList.add('ml-72');
                    // Forzar reflow para animación suave
                    content.style.width = 'calc(100% - 288px)';
                });
                
                toggleBtn.innerHTML = '<i class="fas fa-angle-left text-xl"></i>';
                
                // Mostrar texto del sidebar
                document.querySelectorAll('.sidebar-text').forEach(el => {
                    el.style.display = 'block';
                });
                
                // Ocultar contadores estilo WhatsApp
                document.querySelectorAll('.counter-badge').forEach(counter => {
                    counter.style.display = '';
                    counter.style.position = '';
                    counter.style.top = '';
                    counter.style.right = '';
                });
                
                document.querySelectorAll('.pulse-dot').forEach(dot => {
                    dot.style.display = '';
                });
            }
            
            // Forzar re-layout para que los elementos se ajusten al nuevo espacio
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 50);
            
            // Guardar estado en localStorage
            localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
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