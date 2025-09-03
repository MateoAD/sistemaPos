<?php
require_once '../../controllers/db.php';

// Obtener estadísticas de mesas usando PDO
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM mesas");
$stmt->execute();
$totalMesas = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as ocupadas FROM mesas WHERE estado = 'ocupada'");
$stmt->execute();
$mesasOcupadas = $stmt->fetch()['ocupadas'];

// Obtener todas las mesas para el salón
$stmt = $pdo->prepare("SELECT m.*, s.nombre as sucursal_nombre FROM mesas m LEFT JOIN sucursales s ON m.sucursal_id = s.id ORDER BY m.numero");
$stmt->execute();
$mesas = $stmt->fetchAll();

// Obtener sucursales para el formulario
$stmt = $pdo->prepare("SELECT * FROM sucursales WHERE estado = 'activa'");
$stmt->execute();
$sucursales = $stmt->fetchAll();

include '../../components/navbar.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Mesas - POS Restaurante</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.5);
        }

        .salon-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 40px;
            min-height: 600px;
            position: relative;
        }

        .mesa-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            font-size: 2.5rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .mesa-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
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
            position: absolute;
            top: -10px;
            right: -10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: bold;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            margin: 5% auto;
            padding: 40px;
            width: 90%;
            max-width: 500px;
            color: white;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: white;
        }

        .glow-button {
            background: linear-gradient(45deg, #FF6B6B, #4ECDC4);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .glow-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.5);
        }

        .form-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 12px;
            color: white;
            width: 100%;
            margin-bottom: 15px;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
        }

        .select-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 12px;
            color: white;
            width: 100%;
            margin-bottom: 15px;
        }

        .select-input option {
            background: #1f2937;
            color: white;
        }

        @media (max-width: 768px) {
            .salon-container {
                padding: 20px;
            }
            
            .mesa-icon {
                width: 80px;
                height: 80px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button id="mobileMenuBtn" class="mobile-menu-btn fixed top-4 left-4 z-50 bg-black bg-opacity-50 text-white p-3 rounded-lg lg:hidden">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content ml-72 transition-all duration-300">
        <div class="p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">Gestión de Mesas</h1>
                <p class="text-gray-300">Administra las mesas de tu restaurante</p>
            </div>

            <!-- Cards Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Mesas -->
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-300 text-sm">Total Mesas</p>
                            <p class="text-3xl font-bold text-white"><?= $totalMesas ?></p>
                        </div>
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-table text-white text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Mesas Ocupadas -->
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-300 text-sm">Mesas Ocupadas</p>
                            <p class="text-3xl font-bold text-white"><?= $mesasOcupadas ?></p>
                        </div>
                        <div class="w-16 h-16 bg-gradient-to-r from-red-500 to-pink-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Agregar Mesa -->
                <div class="glass-card p-6 cursor-pointer" onclick="abrirModalAgregar()">
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-plus text-white text-2xl"></i>
                            </div>
                            <p class="text-white font-semibold">Agregar Mesa</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Salon Container -->
            <div class="salon-container">
                <h2 class="text-2xl font-bold text-white mb-6">Salón del Restaurante</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8" id="salonMesas">
                    <?php foreach($mesas as $mesa): ?>
                        <div class="text-center">
                            <div class="mesa-icon mesa-<?= $mesa['estado'] ?>" 
                                 onclick="abrirModalEditar(<?= $mesa['id'] ?>)"
                                 title="Mesa <?= $mesa['numero'] ?>">
                                <div class="mesa-numero"><?= $mesa['numero'] ?></div>
                                <i class="fas fa-table"></i>
                            </div>
                            <p class="text-white mt-2 font-semibold">Mesa <?= $mesa['numero'] ?></p>
                            <p class="text-sm text-gray-300"><?= ucfirst(str_replace('_', ' ', $mesa['estado'])) ?></p>
                            <?php if($mesa['sucursal_nombre']): ?>
                                <p class="text-xs text-gray-400"><?= $mesa['sucursal_nombre'] ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Agregar/Editar Mesa -->
    <div id="modalMesa" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2 class="text-2xl font-bold mb-6" id="modalTitulo">Agregar Mesa</h2>
            
            <form id="formMesa">
                <input type="hidden" id="mesaId" name="id">
                
                <label class="block mb-2">Número de Mesa:</label>
                <input type="number" id="mesaNumero" name="numero" class="form-input" required>
                
                <label class="block mb-2">Sucursal:</label>
                <select id="mesaSucursal" name="sucursal_id" class="select-input" required>
                    <option value="">Seleccionar sucursal</option>
                    <?php foreach($sucursales as $sucursal): ?>
                        <option value="<?= $sucursal['id'] ?>"><?= $sucursal['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label class="block mb-2">Estado:</label>
                <select id="mesaEstado" name="estado" class="select-input" required>
                    <option value="libre">Libre</option>
                    <option value="ocupada">Ocupada</option>
                    <option value="cerrada">Cerrada</option>
                    <option value="en_preparacion">En Preparación</option>
                </select>
                
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="cerrarModal()" class="glow-button" style="background: linear-gradient(45deg, #6b7280, #4b5563);">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardar" class="glow-button">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Variables globales
        let mesasData = <?= json_encode($mesas) ?>;

        // Funciones del modal
        function abrirModalAgregar() {
            document.getElementById('modalTitulo').textContent = 'Agregar Mesa';
            document.getElementById('formMesa').reset();
            document.getElementById('mesaId').value = '';
            document.getElementById('modalMesa').style.display = 'block';
        }

        function abrirModalEditar(id) {
            const mesa = mesasData.find(m => m.id == id);
            if (mesa) {
                document.getElementById('modalTitulo').textContent = 'Editar Mesa';
                document.getElementById('mesaId').value = mesa.id;
                document.getElementById('mesaNumero').value = mesa.numero;
                document.getElementById('mesaSucursal').value = mesa.sucursal_id;
                document.getElementById('mesaEstado').value = mesa.estado;
                document.getElementById('modalMesa').style.display = 'block';
            }
        }

        function cerrarModal() {
            document.getElementById('modalMesa').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalMesa');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Manejo del formulario
        document.getElementById('formMesa').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const id = formData.get('id');
            const url = id ? '../../controllers/admin/mesas_controller.php?action=actualizar' : '../../controllers/admin/mesas_controller.php?action=guardar';
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar la mesa');
            });
        });

        // Actualización automática cada 30 segundos
        setInterval(() => {
            location.reload();
        }, 30000);

        // Manejo del sidebar móvil
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        });
    </script>
</body>
</html>