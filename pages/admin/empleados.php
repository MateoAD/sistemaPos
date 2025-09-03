<?php
require_once '../../controllers/db.php';


// Obtener contadores por rol
$stmt = $pdo->prepare("SELECT 
    (SELECT COUNT(*) FROM usuarios WHERE rol_id = 2 AND estado = 'activo') as meseros,
    (SELECT COUNT(*) FROM usuarios WHERE rol_id = 4 AND estado = 'activo') as cocineros,
    (SELECT COUNT(*) FROM usuarios WHERE rol_id = 3 AND estado = 'activo') as cajeros");
$stmt->execute();
$contadores = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados - POS Restaurante</title>
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

        .table-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-glass {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
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

        .sidebar {
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.7) 100%);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .nav-item {
            position: relative;
            transition: all 0.3s ease;
            border-radius: 12px;
            margin: 8px 16px;
            overflow: hidden;
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
    </style>
</head>
<body>
    <?php include '../../components/navbar.php'; ?>
    
    <div class="main-content ml-72 transition-all duration-300 p-8">
        <!-- Header -->
        <header class="glass-card rounded-2xl p-6 mb-8">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">Gestión de Empleados</h2>
                    <div class="flex items-center text-gray-200">
                        <i class="fas fa-users mr-2"></i>
                        <span>Administrar personal del restaurante</span>
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

        <!-- Cards de estadísticas glamurosas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Meseros -->
            <div class="metric-card rounded-2xl p-6 cursor-pointer" data-rol="2">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 p-4 rounded-xl">
                        <i class="fas fa-user-tie text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-300 text-sm">Personal activo</p>
                        <p class="text-blue-400 text-lg font-bold"><?php echo $contadores['meseros']; ?></p>
                    </div>
                </div>
                <h3 class="text-white text-xl font-bold mb-1">Meseros</h3>
                <p class="text-gray-300 text-sm">Atención al cliente</p>
                <div class="w-full bg-gray-700 rounded-full h-2 mt-4">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-2 rounded-full" style="width: <?php echo ($contadores['meseros'] / max($contadores)) * 100; ?>%"></div>
                </div>
            </div>

            <!-- Cocineros -->
            <div class="metric-card rounded-2xl p-6 cursor-pointer" data-rol="4">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 p-4 rounded-xl">
                        <i class="fas fa-utensils text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-300 text-sm">Personal activo</p>
                        <p class="text-green-400 text-lg font-bold"><?php echo $contadores['cocineros']; ?></p>
                    </div>
                </div>
                <h3 class="text-white text-xl font-bold mb-1">Cocineros</h3>
                <p class="text-gray-300 text-sm">Preparación de alimentos</p>
                <div class="w-full bg-gray-700 rounded-full h-2 mt-4">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-2 rounded-full" style="width: <?php echo ($contadores['cocineros'] / max($contadores)) * 100; ?>%"></div>
                </div>
            </div>

            <!-- Cajeros -->
            <div class="metric-card rounded-2xl p-6 cursor-pointer" data-rol="3">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 p-4 rounded-xl">
                        <i class="fas fa-cash-register text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-300 text-sm">Personal activo</p>
                        <p class="text-yellow-400 text-lg font-bold"><?php echo $contadores['cajeros']; ?></p>
                    </div>
                </div>
                <h3 class="text-white text-xl font-bold mb-1">Cajeros</h3>
                <p class="text-gray-300 text-sm">Gestión de pagos</p>
                <div class="w-full bg-gray-700 rounded-full h-2 mt-4">
                    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 h-2 rounded-full" style="width: <?php echo ($contadores['cajeros'] / max($contadores)) * 100; ?>%"></div>
                </div>
            </div>
        </div>
                    <!-- Tabla de empleados -->
            <div class="chart-container p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-white">Lista de Empleados</h3>
                    <button class="btn-glow bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl transition-all duration-300" onclick="abrirModalEmpleado()">
                        <i class="fas fa-plus mr-2"></i>Agregar Empleado
                    </button>
                </div>
                <div id="tablaEmpleados" style="display: none;">
                    <div class="table-glass rounded-xl overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-800 bg-opacity-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-gray-300 font-semibold">ID</th>
                                    <th class="px-6 py-4 text-left text-gray-300 font-semibold">Nombre</th>
                                    <th class="px-6 py-4 text-left text-gray-300 font-semibold">Rol</th>
                                    <th class="px-6 py-4 text-left text-gray-300 font-semibold">Sucursal</th>
                                    <th class="px-6 py-4 text-left text-gray-300 font-semibold">Estado</th>
                                    <th class="px-6 py-4 text-left text-gray-300 font-semibold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyEmpleados" class="divide-y divide-gray-700">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="mensajeSeleccion" class="text-center py-12">
                    <i class="fas fa-users text-6xl text-gray-500 mb-4"></i>
                    <p class="text-gray-400 text-lg">Selecciona un rol para ver los empleados</p>
                </div>
            </div>

            <!-- Gráfico de estadísticas -->
            <div class="chart-container p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-white">Distribución por Roles</h3>
                    <div class="flex space-x-2">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <span class="text-gray-300 text-sm">Meseros</span>
                        <div class="w-3 h-3 bg-green-500 rounded-full ml-2"></div>
                        <span class="text-gray-300 text-sm">Cocineros</span>
                        <div class="w-3 h-3 bg-yellow-500 rounded-full ml-2"></div>
                        <span class="text-gray-300 text-sm">Cajeros</span>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="empleadosChart"></canvas>
                </div>
            </div>
    </div>

    <!-- Modal glamuroso -->
    <div class="modal fade fixed inset-0 bg-black bg-opacity-50 z-50 hidden" id="modalEmpleado">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gray-900 bg-opacity-90 backdrop-blur-xl rounded-2xl p-8 w-full max-w-md border border-gray-700">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-white" id="tituloModal">Agregar Empleado</h3>
                    <button type="button" class="text-gray-400 hover:text-white text-2xl" onclick="cerrarModal()">&times;</button>
                </div>
                <form id="formEmpleado" class="space-y-4">
                    <input type="hidden" id="empleadoId">
                    <div>
                        <label class="block text-gray-300 mb-2">Nombre</label>
                        <input type="text" id="nombre" required 
                               class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-xl text-white focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Contraseña</label>
                        <input type="password" id="contrasena" required 
                               class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-xl text-white focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Rol</label>
                        <select id="rolId" required 
                                class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-xl text-white focus:border-blue-500 focus:outline-none">
                            <option value="">Seleccione un rol</option>
                            <option value="2">Mesero</option>
                            <option value="4">Cocinero</option>
                            <option value="3">Cajero</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-2">Sucursal</label>
                        <select id="sucursalId" required 
                                class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-xl text-white focus:border-blue-500 focus:outline-none">
                            <option value="">Seleccione una sucursal</option>
                        </select>
                    </div>
                </form>
                <div class="flex space-x-4 mt-6">
                    <button type="button" onclick="cerrarModal()" 
                            class="flex-1 px-4 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-xl transition-colors duration-300">
                        Cancelar
                    </button>
                    <button type="button" onclick="guardarEmpleado()" 
                            class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl transition-all duration-300 btn-glow">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let rolActual = null;
        let empleadosChart = null;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            inicializarGrafico();
            cargarSucursales();
            
            // Event listeners para las cards
            document.querySelectorAll('.metric-card').forEach(card => {
                card.addEventListener('click', function() {
                    const rol = this.dataset.rol;
                    mostrarEmpleadosPorRol(rol);
                });
            });
        });

        function inicializarGrafico() {
            const ctx = document.getElementById('empleadosChart').getContext('2d');
            
            const valores = [
                parseInt(document.querySelector('.metric-card[data-rol="2"] .text-blue-400').textContent),
                parseInt(document.querySelector('.metric-card[data-rol="4"] .text-green-400').textContent),
                parseInt(document.querySelector('.metric-card[data-rol="3"] .text-yellow-400').textContent)
            ];

            empleadosChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Meseros', 'Cocineros', 'Cajeros'],
                    datasets: [{
                        data: valores,
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(245, 158, 11, 0.8)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(34, 197, 94, 1)',
                            'rgba(245, 158, 11, 1)'
                        ],
                        borderWidth: 3,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#e5e7eb',
                                font: {
                                    size: 14
                                },
                                padding: 20
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }

// Reemplazar la función mostrarEmpleadosPorRol completa:
function mostrarEmpleadosPorRol(rolId) {
    rolActual = rolId;
    const tabla = document.getElementById('tablaEmpleados');
    const mensaje = document.getElementById('mensajeSeleccion');
    
    // Mostrar tabla y ocultar mensaje
    tabla.style.display = 'block';
    mensaje.style.display = 'none';
    
    // Cargar empleados
    fetch(`../../controllers/admin/empleados_controller.php?action=getEmpleadosPorRol&rol_id=${rolId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                actualizarTabla(data.data);
            }
        })
        .catch(error => {
            console.error('Error al cargar empleados:', error);
            alert('Error al cargar los empleados');
        });
}

// Asegurar que la función actualizarTabla funcione correctamente:
function actualizarTabla(empleados) {
    const tbody = document.getElementById('tbodyEmpleados');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (empleados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-400">No hay empleados en este rol</td></tr>';
        return;
    }
    
    empleados.forEach(empleado => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-800 hover:bg-opacity-30 transition-colors duration-200';
        tr.innerHTML = `
            <td class="px-6 py-4 text-gray-300">${empleado.id}</td>
            <td class="px-6 py-4 text-white font-medium">${empleado.nombre}</td>
            <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${
                    empleado.rol === 'Mesero' ? 'bg-blue-500 text-blue-100' :
                    empleado.rol === 'Cocinero' ? 'bg-green-500 text-green-100' :
                    'bg-yellow-500 text-yellow-100'
                }">${empleado.rol}</span>
            </td>
            <td class="px-6 py-4 text-gray-300">${empleado.sucursal || 'Sin asignar'}</td>
            <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${
                    empleado.estado === 'activo' ? 'bg-green-500 text-green-100' : 'bg-red-500 text-red-100'
                }">${empleado.estado}</span>
            </td>
            <td class="px-6 py-4">
                <div class="flex space-x-2">
                    <button onclick="editarEmpleado(${empleado.id})" class="text-blue-400 hover:text-blue-300">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="toggleEstado(${empleado.id})" class="${
                        empleado.estado === 'activo' ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300'
                    }">
                        <i class="fas fa-${empleado.estado === 'activo' ? 'times' : 'check'}"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

        function cargarSucursales() {
            fetch('../../controllers/admin/empleados_controller.php?action=getSucursales')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('sucursalId');
                        data.data.forEach(sucursal => {
                            const option = document.createElement('option');
                            option.value = sucursal.id;
                            option.textContent = sucursal.nombre;
                            select.appendChild(option);
                        });
                    }
                });
        }

        function abrirModalEmpleado(id = null) {
            const modal = document.getElementById('modalEmpleado');
            const titulo = document.getElementById('tituloModal');
            
            if (id) {
                titulo.textContent = 'Editar Empleado';
                fetch(`../../controllers/admin/empleados_controller.php?action=getEmpleado&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('empleadoId').value = data.data.id;
                            document.getElementById('nombre').value = data.data.nombre;
                            document.getElementById('contrasena').value = data.data.contraseña;
                            document.getElementById('rolId').value = data.data.rol_id;
                            document.getElementById('sucursalId').value = data.data.sucursal_id;
                        }
                    });
            } else {
                titulo.textContent = 'Agregar Empleado';
                document.getElementById('formEmpleado').reset();
                document.getElementById('empleadoId').value = '';
            }
            
            modal.classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('modalEmpleado').classList.add('hidden');
        }

        function guardarEmpleado() {
            const formData = {
                id: document.getElementById('empleadoId').value || null,
                nombre: document.getElementById('nombre').value,
                contrasena: document.getElementById('contrasena').value,
                rolId: document.getElementById('rolId').value,
                sucursalId: document.getElementById('sucursalId').value
            };
            
            fetch('../../controllers/admin/empleados_controller.php?action=guardarEmpleado', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al guardar: ' + data.error);
                }
            });
        }

        function toggleEstado(id) {
            if (confirm('¿Está seguro de cambiar el estado del empleado?')) {
                const formData = new FormData();
                formData.append('id', id);
                
                fetch('../../controllers/admin/empleados_controller.php?action=toggleEstado', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al cambiar estado: ' + data.error);
                    }
                });
            }
        }

        function editarEmpleado(id) {
            abrirModalEmpleado(id);
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalEmpleado').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>