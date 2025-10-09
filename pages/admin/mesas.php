<?php
require_once '../../controllers/db.php';

// Obtener sucursales activas
$stmt = $pdo->prepare("SELECT * FROM sucursales WHERE estado = 'activa'");
$stmt->execute();
$sucursales = $stmt->fetchAll();

// Selector de sucursal (por defecto la primera activa)
$sucursalId = isset($_GET['sucursal_id']) ? intval($_GET['sucursal_id']) : (isset($sucursales[0]['id']) ? $sucursales[0]['id'] : null);
$sucursalActual = null;
foreach ($sucursales as $suc) {
    if ($suc['id'] == $sucursalId) {
        $sucursalActual = $suc;
        break;
    }
}

// Obtener estadísticas de mesas de la sucursal seleccionada
$totalMesas = 0;
$mesasOcupadas = 0;
if ($sucursalId !== null) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM mesas WHERE sucursal_id = ?");
    $stmt->execute([$sucursalId]);
    $row = $stmt->fetch();
    $totalMesas = isset($row['total']) ? $row['total'] : 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as ocupadas FROM mesas WHERE estado = 'ocupada' AND sucursal_id = ?");
    $stmt->execute([$sucursalId]);
    $row = $stmt->fetch();
    $mesasOcupadas = isset($row['ocupadas']) ? $row['ocupadas'] : 0;

    // Obtener todas las mesas para el salón de la sucursal seleccionada
    $stmt = $pdo->prepare("SELECT m.*, s.nombre as sucursal_nombre FROM mesas m LEFT JOIN sucursales s ON m.sucursal_id = s.id WHERE m.sucursal_id = ? ORDER BY m.numero");
    $stmt->execute([$sucursalId]);
    $mesas = $stmt->fetchAll();

    // Obtener total de empleados de la sucursal seleccionada
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE sucursal_id = ? AND estado = 'activo'");
    $stmt->execute([$sucursalId]);
    $row = $stmt->fetch();
    $totalEmpleadosSucursal = isset($row['total']) ? $row['total'] : 0;
} else {
    $mesas = [];
    $totalEmpleadosSucursal = 0;
}

// Indicador de ocupación comparado con el promedio de todas las sucursales
$stmt = $pdo->prepare("SELECT sucursal_id, COUNT(*) as total, SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas FROM mesas GROUP BY sucursal_id");
$stmt->execute();
$statsSucursales = $stmt->fetchAll();
$promedioOcupacion = 0;
$ocupacionActual = 0;
$numSucursales = count($statsSucursales);
foreach ($statsSucursales as $stat) {
    $ocupacion = $stat['total'] > 0 ? ($stat['ocupadas'] / $stat['total']) * 100 : 0;
    $promedioOcupacion += $ocupacion;
    if ($stat['sucursal_id'] == $sucursalId) {
        $ocupacionActual = $ocupacion;
    }
}
$promedioOcupacion = $numSucursales > 0 ? $promedioOcupacion / $numSucursales : 0;
$indicadorSucursal = ($ocupacionActual > $promedioOcupacion)
    ? '+' . round($ocupacionActual - $promedioOcupacion, 1) . '%'
    : '-' . round($promedioOcupacion - $ocupacionActual, 1) . '%';

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
            background: #0F0F0F;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }

        .glass-card {
            background: #1E201F;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            border-radius: 20px;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #ffea00, #ff7b00);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .glass-card:hover::before {
            transform: scaleX(1);
        }

        .metric-card {
            background: #1E201F;
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
            height: 5px;
            background: linear-gradient(90deg, #ffea00, #ff7b00);
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

        .salon-container {
            background: #1E201F;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 40px;
            min-height: 600px;
            position: relative;
            overflow: hidden;
            /* 👈 evita que el gradiente se salga */
        }

        .salon-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #ffea00, #ff7b00);
            border-radius: 25px 25px 0 0;
            /* 👈 sigue el borde del contenedor */
            transform: scaleX(0);
            transform-origin: center;
            /* 👈 asegura que el gradiente crezca desde la izquierda */
            transition: transform 0.3s ease;
        }

        .salon-container:hover::before {
            transform: scaleX(1);
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
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .mesa-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
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
            background-color: rgba(0, 0, 0, 0.5);
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

        .glass-card-1 {
            background: #1E201F;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-card-2 {
            background: #0F0F0F;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body>

    <!-- Main Content -->
    <div class="main-content ml-72 transition-all duration-300">
        <div class="p-8">
            <!-- Header -->
            <header class="glass-card-1 rounded-2xl p-6 mb-8">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-white mb-2">Gestión de Mesas</h2>
                        <div class="flex items-center text-gray-200">
                            <i class="fas fa-users mr-2"></i>
                            <span>Administrar mesas del restaurante</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 mt-4 lg:mt-0">
                        <div class="glass-card-1 px-4 py-2 rounded-xl">
                            <div class="flex items-center text-white">
                                <div class="pulse-dot mr-2"></div>
                                <span class="text-sm">Sistema Activo</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <!-- Cards Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Mesas (metric-card estilo dashboard) -->
                <div class=" metric-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-gradient-to-r from-blue-500 to-cyan-500 p-3 rounded-xl">
                            <i class="fas fa-table text-white text-xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-300">Ocupación</p>
                            <p class="text-sm font-semibold text-green-400">
                                <?= $totalMesas > 0 ? round(($mesasOcupadas / $totalMesas) * 100, 1) : 0 ?>%</p>
                        </div>
                    </div>
                    <h3 class="text-gray-300 text-sm mb-1">Total Mesas</h3>
                    <p class="text-3xl font-bold text-white mb-2"><?= $totalMesas ?></p>
                    <div class="w-full bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-2 rounded-full"
                            style="width: <?= $totalMesas > 0 ? ($mesasOcupadas / $totalMesas) * 100 : 0 ?>%"></div>
                    </div>
                </div>

                <!-- Mesas Ocupadas (metric-card estilo dashboard) -->
                <div class="metric-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-gradient-to-r from-orange-500 to-red-500 p-3 rounded-xl">
                            <i class="fas fa-chair text-white text-xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-300">Libres</p>
                            <p class="text-orange-400 text-sm font-semibold"><?= $totalMesas - $mesasOcupadas ?> mesas
                            </p>
                        </div>
                    </div>
                    <h3 class="text-gray-300 text-sm mb-1">Mesas Ocupadas</h3>
                    <p class="text-3xl font-bold text-white mb-2"><?= $mesasOcupadas ?>/<?= $totalMesas ?></p>
                    <div class="flex space-x-1">
                        <?php
                        $barras_verdes = $totalMesas > 0 ? floor(($mesasOcupadas / $totalMesas) * 4) : 0;
                        for ($i = 0; $i < 4; $i++) {
                            $clase = $i < $barras_verdes ? 'bg-emerald-500' : 'bg-gray-600';
                            echo "<div class='flex-1 h-2 $clase rounded'></div>";
                        }
                        ?>
                    </div>
                </div>

                <!-- Card de Sucursal seleccionada -->
                <div class="metric-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-gradient-to-r from-green-500 to-teal-600 p-3 rounded-xl">
                            <i class="fas fa-store text-white text-xl"></i>
                        </div>
                        <form method="get" id="formSucursal">
                            <select name="sucursal_id"
                                class="bg-gray-800 text-white border border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                onchange="document.getElementById('formSucursal').submit()">
                                <?php foreach ($sucursales as $suc): ?>
                                    <option value="<?= $suc['id'] ?>" <?= isset($sucursalId) && $suc['id'] == $sucursalId ? 'selected' : '' ?>><?= $suc['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <h3 class="text-gray-300 text-sm mb-1">Sucursal Actual</h3>
                    <p class="text-xl font-bold text-white mb-2" id="nombreSucursal">
                        <?= isset($sucursalActual) && $sucursalActual ? $sucursalActual['nombre'] : 'Sin sucursal' ?>
                    </p>
                    <p class="text-sm text-gray-300 mb-2">Empleados: <span
                            id="totalEmpleados"><?= isset($totalEmpleadosSucursal) ? $totalEmpleadosSucursal : 0 ?></span>
                    </p>
                    <div class="flex items-center">
                        <span id="indicadorSucursal"
                            class="text-green-400 font-semibold mr-2"><?= isset($indicadorSucursal) ? $indicadorSucursal : '' ?></span>
                        <span class="text-xs text-gray-400">vs otras sucursales</span>
                    </div>
                </div>
            </div>

            <!-- Salon Container -->
            <div class="salon-container">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white glass-card-2 px-6 py-3 rounded-2xl shadow-lg">
                        Salón - <?= $sucursalActual ? $sucursalActual['nombre'] : 'Sin sucursal' ?>
                    </h2>
                    <button onclick="abrirModalAgregar()"
                        class="glass-card-2 flex items-center px-4 py-2 rounded-xl text-black font-semibold transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-yellow-500/25"
                        style="background: linear-gradient(45deg, #ffea00, #ff7b00);">
                        <i class="fas fa-plus mr-2"></i>Agregar Mesa
                    </button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8" id="salonMesas">
                    <?php foreach ($mesas as $mesa): ?>
                        <div class="text-center glass-card-2 p-4 rounded-2xl shadow-lg">
                            <div class="mesa-icon mesa-<?= $mesa['estado'] ?> mx-auto mb-2 flex items-center justify-center rounded-full transition-all duration-300"
                                onclick="abrirModalEditar(<?= $mesa['id'] ?>)" title="Mesa <?= $mesa['numero'] ?>"
                                style="width:64px;height:64px;font-size:2rem;
                  <?= $mesa['estado'] == 'libre' ? 'background:linear-gradient(45deg,#00ff99,#00cfff);color:#fff;' : '' ?>
                  <?= $mesa['estado'] == 'ocupada' ? 'background:linear-gradient(45deg,#ff7b00,#ffea00);color:#fff;' : '' ?>
                  <?= $mesa['estado'] == 'cerrada' ? 'background:linear-gradient(45deg,#6b7280,#4b5563);color:#fff;' : '' ?>
                  <?= $mesa['estado'] == 'en_preparacion' ? 'background:linear-gradient(45deg,#ff00cc,#3333ff);color:#fff;' : '' ?>">
                                <span class="mesa-numero font-bold"><?= $mesa['numero'] ?></span>
                                <i class="fas fa-table ml-2"></i>
                            </div>
                            <p class="text-white font-semibold">Mesa <?= $mesa['numero'] ?></p>
                            <p class="text-sm text-gray-300"><?= ucfirst(str_replace('_', ' ', $mesa['estado'])) ?></p>
                            <?php if ($mesa['sucursal_nombre']): ?>
                                <p class="text-xs text-gray-400"><?= $mesa['sucursal_nombre'] ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Agregar/Editar Mesa -->
    <div id="modalMesa"
        class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50 backdrop-blur-sm transition-all duration-300"
        style="display: none;">
        <div class="glass-card-2 rounded-2xl p-8 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white" id="modalTitulo">Agregar Mesa</h2>
                <button onclick="cerrarModal()" class="text-gray-400 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="formMesa" class="space-y-4 text-white">
                <input type="hidden" id="mesaId" name="id">
                <div>
                    <label class="block text-gray-300 mb-2">Número de Mesa:</label>
                    <input type="number" id="mesaNumero" name="numero" required
                        class="w-full px-4 py-3 bg-black border border-white rounded-xl text-white focus:border-yellow-500 focus:ring-yellow-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2">Sucursal:</label>
                    <select id="mesaSucursal" name="sucursal_id" required
                        class="w-full px-4 py-3 bg-black border border-white rounded-xl text-white focus:border-yellow-500 focus:ring-yellow-500 focus:outline-none">
                        <option value="">Seleccionar sucursal</option>
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?= $sucursal['id'] ?>"><?= $sucursal['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-300 mb-2">Estado:</label>
                    <select id="mesaEstado" name="estado" required
                        class="w-full px-4 py-3 bg-black border border-white rounded-xl text-white focus:border-yellow-500 focus:ring-yellow-500 focus:outline-none">
                        <option value="libre">Libre</option>
                        <option value="ocupada">Ocupada</option>
                        <option value="cerrada">Cerrada</option>
                        <option value="en_preparacion">En Preparación</option>
                    </select>
                </div>
            </form>
            <div class="flex justify-end space-x-4 mt-8">
                <button onclick="cerrarModal()"
                    class="text-black px-4 py-2 font-semibold rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-yellow-500/25"
                    style="background: linear-gradient(45deg, #ffea00, #ff7b00);">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
                <button onclick="guardarMesa()"
                    class="text-black px-4 py-2 font-semibold rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-yellow-500/25"
                    style="background: linear-gradient(45deg, #ffea00, #ff7b00);">
                    <i class="fas fa-save mr-2"></i>Guardar
                </button>
            </div>
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
            document.getElementById('modalMesa').style.display = 'flex';
        }

        function abrirModalEditar(id) {
            const mesa = mesasData.find(m => m.id == id);
            if (mesa) {
                document.getElementById('modalTitulo').textContent = 'Editar Mesa';
                document.getElementById('mesaId').value = mesa.id;
                document.getElementById('mesaNumero').value = mesa.numero;
                document.getElementById('mesaSucursal').value = mesa.sucursal_id;
                document.getElementById('mesaEstado').value = mesa.estado;
                document.getElementById('modalMesa').style.display = 'flex';
            }
        }

        function cerrarModal() {
            document.getElementById('modalMesa').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function (event) {
            const modal = document.getElementById('modalMesa');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Manejo del formulario
        document.getElementById('formMesa').addEventListener('submit', function (e) {
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
        if (document.getElementById('mobileMenuBtn')) {
            document.getElementById('mobileMenuBtn').addEventListener('click', function () {
                document.getElementById('sidebar').classList.toggle('mobile-open');
            });
        }

        // Selector de sucursal
        function abrirSelectorSucursal() {
            let selectorHtml = '<div class="modal-content"><span class="close" onclick="cerrarModalSelectorSucursal()">&times;</span>';
            selectorHtml += '<h2 class="text-2xl font-bold mb-6">Seleccionar Sucursal</h2>';
            selectorHtml += '<form id="formSelectorSucursal">';
            selectorHtml += '<select id="selectorSucursal" name="sucursal_id" class="select-input" required>';
            <?php foreach ($sucursales as $sucursal): ?>
                selectorHtml += '<option value="<?= $sucursal['id'] ?>" <?= ($sucursal['id'] == $sucursalId) ? "selected" : "" ?>><?= $sucursal['nombre'] ?></option>';
            <?php endforeach; ?>
            selectorHtml += '</select>';
            selectorHtml += '<div class="flex justify-end space-x-4 mt-6">';
            selectorHtml += '<button type="button" onclick="cerrarModalSelectorSucursal()" class="glow-button" style="background: linear-gradient(45deg, #6b7280, #4b5563);">Cancelar</button>';
            selectorHtml += '<button type="submit" class="glow-button">Seleccionar</button>';
            selectorHtml += '</div>';
            selectorHtml += '</form></div>';
            let modalDiv = document.createElement('div');
            modalDiv.id = 'modalSelectorSucursal';
            modalDiv.className = 'modal';
            modalDiv.innerHTML = selectorHtml;
            document.body.appendChild(modalDiv);
            modalDiv.style.display = 'block';
            document.getElementById('formSelectorSucursal').addEventListener('submit', function (e) {
                e.preventDefault();
                let sucursalId = document.getElementById('selectorSucursal').value;
                window.location.href = '?sucursal_id=' + sucursalId;
            });
        }
        function cerrarModalSelectorSucursal() {
            let modalDiv = document.getElementById('modalSelectorSucursal');
            if (modalDiv) {
                modalDiv.parentNode.removeChild(modalDiv);
            }
        }
    </script>
</body>

</html>