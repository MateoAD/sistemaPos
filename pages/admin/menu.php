<?php
require_once '../../controllers/db.php';

// Obtener productos del menú
$stmt = $pdo->query("SELECT m.*, 
                     CASE 
                         WHEN m.estado = 'disponible' THEN 'Activo'
                         WHEN m.estado = 'no_disponible' THEN 'Inactivo'
                     END as estado_texto 
                     FROM menu m 
                     ORDER BY m.categoria, m.nombre");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías únicas
$stmt = $pdo->query("SELECT DISTINCT categoria FROM menu WHERE categoria IS NOT NULL");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stmt = $pdo->query("SELECT 
                     COUNT(*) as total_productos,
                     COUNT(CASE WHEN estado = 'disponible' THEN 1 END) as productos_activos,
                     AVG(precio) as precio_promedio
                     FROM menu");
$estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Menú - POS Restaurante</title>
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

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
        }

        .form-input {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .file-upload {
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .file-upload:hover {
            border-color: rgba(59, 130, 246, 0.5);
            background: rgba(255, 255, 255, 0.15);
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
                    <h2 class="text-3xl font-bold text-white mb-2">Gestión de Menú</h2>
                    <div class="flex items-center text-gray-200">
                        <i class="fas fa-utensils mr-2"></i>
                        <span>Control de productos y categorías</span>
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
            <!-- Total Productos -->
            <div class="metric-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-4 rounded-xl">
                        <i class="fas fa-utensils text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-300 text-sm">Total Productos</p>
                        <p class="text-purple-400 text-2xl font-bold"><?php echo number_format($estadisticas['total_productos']); ?></p>
                    </div>
                </div>
                <h3 class="text-white text-xl font-bold mb-1">Productos Registrados</h3>
                <p class="text-gray-300 text-sm">Todos los platos del menú</p>
            </div>

            <!-- Productos Activos -->
            <div class="metric-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 p-4 rounded-xl">
                        <i class="fas fa-check-circle text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-300 text-sm">Activos</p>
                        <p class="text-green-400 text-2xl font-bold"><?php echo number_format($estadisticas['productos_activos']); ?></p>
                    </div>
                </div>
                <h3 class="text-white text-xl font-bold mb-1">Disponibles</h3>
                <p class="text-gray-300 text-sm">Productos activos en menú</p>
            </div>

            <!-- Precio Promedio -->
            <div class="metric-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 p-4 rounded-xl">
                        <i class="fas fa-dollar-sign text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-300 text-sm">Precio Promedio</p>
                        <p class="text-blue-400 text-2xl font-bold">$<?php echo number_format($estadisticas['precio_promedio'], 2); ?></p>
                    </div>
                </div>
                <h3 class="text-white text-xl font-bold mb-1">Costo Medio</h3>
                <p class="text-gray-300 text-sm">Precio promedio por plato</p>
            </div>
        </div>

        <!-- Tabla de productos -->
        <div class="glass-card rounded-2xl p-6 ">
                    <!-- Filtros y búsqueda -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                <div>
                    <label class="block text-gray-300 text-sm mb-2">Buscar</label>
                    <input type="text" id="busqueda" placeholder="Buscar productos..." 
                           class="w-full px-4 py-2 rounded-xl search-input text-white placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-2">Categoría</label>
                    <select id="filtroCategoria" class="w-full px-4 py-2 rounded-xl filter-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['categoria']; ?>"><?php echo $cat['categoria']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-2">Estado</label>
                    <select id="filtroEstado" class="w-full px-4 py-2 rounded-xl filter-select">
                        <option value="">Todos los estados</option>
                        <option value="disponible">Disponible</option>
                        <option value="no_disponible">No Disponible</option>
                    </select>
                </div>
            </div>
           <div class="flex justify-between items-center mb-6">

    <h3 class="text-xl font-semibold text-white">Lista de Productos</h3>
    
    <!-- Contenedor para los botones -->
    <div class="flex space-x-4">
        <button onclick="abrirModalPlato()" 
            class="btn-glow bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition-all duration-300 flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i>Agregar Plato
        </button>

        <button onclick="abrirModalCategoria()" 
            class="btn-glow bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-xl transition-all duration-300 flex items-center justify-center">
            <i class="fas fa-tag mr-2"></i>Agregar Categoría
        </button>
    </div>
</div>            
            <div class="overflow-x-auto">
                <div class="table-glass rounded-xl">
                    <table class="w-full">
                        <thead class="bg-gray-800 bg-opacity-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Foto</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Producto</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Categoría</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Precio</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Estado</th>
                                <th class="px-6 py-4 text-left text-gray-300 font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyProductos" class="divide-y divide-gray-700">
                            <?php foreach ($productos as $producto): ?>
                            <tr class="table-row">
                                <td class="px-6 py-4">
                                    <?php if ($producto['foto']): ?>
                                        <img src="../../uploads/<?php echo $producto['foto']; ?>" alt="<?php echo $producto['nombre']; ?>" class="product-image">
                                    <?php else: ?>
                                        <div class="product-image bg-gray-600 flex items-center justify-center">
                                            <i class="fas fa-utensils text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white font-medium"><?php echo $producto['nombre']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-blue-500 bg-opacity-20 text-blue-300 rounded-full text-xs">
                                        <?php echo $producto['categoria']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-green-400 font-bold">$<?php echo number_format($producto['precio'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs <?php echo $producto['estado'] == 'disponible' ? 'bg-green-500 bg-opacity-20 text-green-300' : 'bg-red-500 bg-opacity-20 text-red-300'; ?>">
                                        <?php echo $producto['estado_texto']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button onclick="editarProducto(<?php echo $producto['id']; ?>)" 
                                                class="text-blue-400 hover:text-blue-300 transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="cambiarEstado(<?php echo $producto['id']; ?>, '<?php echo $producto['estado']; ?>')" 
                                                class="text-yellow-400 hover:text-yellow-300 transition-colors">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                        <button onclick="eliminarProducto(<?php echo $producto['id']; ?>)" 
                                                class="text-red-400 hover:text-red-300 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar/Editar Plato -->
    <div id="modalPlato" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="modal-backdrop absolute inset-0"></div>
        <div class="glass-card rounded-2xl p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto relative z-10">
            <div class="flex justify-between items-center mb-6">
                <h2 id="tituloModalPlato" class="text-2xl font-bold text-white">Agregar Plato</h2>
                <button onclick="cerrarModal('modalPlato')" class="text-gray-400 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="formPlato" enctype="multipart/form-data">
                <input type="hidden" id="platoId" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-300 text-sm mb-2">Nombre del Plato</label>
                        <input type="text" id="nombre" name="nombre" required 
                               class="w-full px-4 py-2 rounded-xl form-input" placeholder="Ej: Pizza Margherita">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm mb-2">Precio</label>
                        <input type="number" id="precio" name="precio" step="0.01" required 
                               class="w-full px-4 py-2 rounded-xl form-input" placeholder="0.00">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm mb-2">Categoría</label>
                        <select id="categoria" name="categoria" required 
                                class="w-full px-4 py-2 rounded-xl form-input">
                            <option value="">Seleccione categoría</option>
                            <option value="entrada">Entrada</option>
                            <option value="plato_fuerte">Plato Fuerte</option>
                            <option value="postre">Postre</option>
                            <option value="bebida">Bebida</option>
                            <option value="adicional">Adicional</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm mb-2">Estado</label>
                        <select id="estado" name="estado" required 
                                class="w-full px-4 py-2 rounded-xl form-input">
                            <option value="disponible">Disponible</option>
                            <option value="no_disponible">No Disponible</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-gray-300 text-sm mb-2">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" 
                              class="w-full px-4 py-2 rounded-xl form-input" 
                              placeholder="Descripción del plato..."></textarea>
                </div>
                
                <div class="mt-6">
                    <label class="block text-gray-300 text-sm mb-2">Foto del Plato</label>
                    <div class="file-upload rounded-xl p-6 text-center">
                        <input type="file" id="foto" name="foto" accept="image/*" 
                               class="hidden" onchange="mostrarPreview(this)">
                        <label for="foto" class="cursor-pointer">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-gray-300">Haz clic para subir una imagen</p>
                            <img id="previewFoto" class="mt-4 mx-auto max-w-xs rounded-lg hidden">
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-8">
                    <button type="button" onclick="cerrarModal('modalPlato')" 
                            class="px-6 py-3 text-gray-300 hover:text-white transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="btn-glow bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition-all duration-300">
                        <i class="fas fa-save mr-2"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Agregar Categoría -->
    <div id="modalCategoria" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="glass-card rounded-2xl p-8 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">Agregar Categoría</h2>
                <button onclick="cerrarModal('modalCategoria')" class="text-gray-400 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="formCategoria">
                <div>
                    <label class="block text-gray-300 text-sm mb-2">Nombre de la Categoría</label>
                    <input type="text" id="nuevaCategoria" name="categoria" required 
                           class="w-full px-4 py-2 rounded-xl form-input" placeholder="Ej: Bebidas Frías">
                </div>
                
                <div class="flex justify-end space-x-4 mt-8">
                    <button type="button" onclick="cerrarModal('modalCategoria')" 
                            class="px-6 py-3 text-gray-300 hover:text-white transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="btn-glow bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-xl transition-all duration-300">
                        <i class="fas fa-plus mr-2"></i>Agregar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Event listeners
    document.getElementById('busqueda').addEventListener('input', actualizarTabla);
    document.getElementById('filtroCategoria').addEventListener('change', actualizarTabla);
    document.getElementById('filtroEstado').addEventListener('change', actualizarTabla);
    
    document.getElementById('formPlato').addEventListener('submit', function(e) {
        e.preventDefault();
        guardarProducto();
    });
    
    document.getElementById('formCategoria').addEventListener('submit', function(e) {
        e.preventDefault();
        agregarCategoria();
    });
});

function actualizarTabla() {
    const busqueda = document.getElementById('busqueda').value;
    const categoria = document.getElementById('filtroCategoria').value;
    const estado = document.getElementById('filtroEstado').value;
    
    let url = `../../controllers/admin/menu_controller.php?action=listar`;
    if (busqueda) url += `&busqueda=${encodeURIComponent(busqueda)}`;
    if (categoria) url += `&categoria=${encodeURIComponent(categoria)}`;
    if (estado) url += `&estado=${encodeURIComponent(estado)}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                actualizarTablaProductos(data.data);
            }
        });
}

function actualizarTablaProductos(productos) {
    const tbody = document.getElementById('tbodyProductos');
    tbody.innerHTML = '';
    
    if (productos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-400">No hay productos encontrados</td></tr>';
        return;
    }
    
    productos.forEach(producto => {
        const tr = document.createElement('tr');
        tr.className = 'table-row';
        tr.innerHTML = `
            <td class="px-6 py-4">
                ${producto.foto ? 
                    `<img src="../../uploads/${producto.foto}" alt="${producto.nombre}" class="product-image">` : 
                    `<div class="product-image bg-gray-600 flex items-center justify-center">
                        <i class="fas fa-utensils text-gray-400"></i>
                    </div>`
                }
            </td>
            <td class="px-6 py-4">
                <div class="text-white font-medium">${producto.nombre}</div>
            </td>
            <td class="px-6 py-4 text-gray-300 text-sm max-w-xs truncate">${producto.descripcion || 'Sin descripción'}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 bg-blue-500 bg-opacity-20 text-blue-300 rounded-full text-xs">
                    ${producto.categoria}
                </span>
            </td>
            <td class="px-6 py-4 text-green-400 font-bold">$${parseFloat(producto.precio).toFixed(2)}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 rounded-full text-xs ${producto.estado === 'disponible' ? 'bg-green-500 bg-opacity-20 text-green-300' : 'bg-red-500 bg-opacity-20 text-red-300'}">
                    ${producto.estado_texto}
                </span>
            </td>
            <td class="px-6 py-4">
                <div class="flex space-x-2">
                    <button onclick="editarProducto(${producto.id})" 
                            class="text-blue-400 hover:text-blue-300 transition-colors">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="cambiarEstado(${producto.id}, '${producto.estado}')" 
                            class="text-yellow-400 hover:text-yellow-300 transition-colors">
                        <i class="fas fa-toggle-on"></i>
                    </button>
                    <button onclick="eliminarProducto(${producto.id})" 
                            class="text-red-400 hover:text-red-300 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function abrirModalPlato() {
    document.getElementById('tituloModalPlato').textContent = 'Agregar Plato';
    document.getElementById('formPlato').reset();
    document.getElementById('platoId').value = '';
    document.getElementById('previewFoto').classList.add('hidden');
    document.getElementById('modalPlato').classList.remove('hidden');
}

function abrirModalCategoria() {
    document.getElementById('formCategoria').reset();
    document.getElementById('modalCategoria').classList.remove('hidden');
}

function cerrarModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function editarProducto(id) {
    fetch(`../../controllers/admin/menu_controller.php?action=obtener&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const producto = data.data;
                document.getElementById('tituloModalPlato').textContent = 'Editar Plato';
                document.getElementById('platoId').value = producto.id;
                document.getElementById('nombre').value = producto.nombre;
                document.getElementById('descripcion').value = producto.descripcion || '';
                document.getElementById('precio').value = producto.precio;
                document.getElementById('categoria').value = producto.categoria;
                document.getElementById('estado').value = producto.estado;
                
                if (producto.foto) {
                    document.getElementById('previewFoto').src = `../../uploads/${producto.foto}`;
                    document.getElementById('previewFoto').classList.remove('hidden');
                }
                
                document.getElementById('modalPlato').classList.remove('hidden');
            }
        });
}

function guardarProducto() {
    const formData = new FormData(document.getElementById('formPlato'));
    
    fetch('../../controllers/admin/menu_controller.php?action=guardar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cerrarModal('modalPlato');
            actualizarTabla();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function cambiarEstado(id, estadoActual) {
    if (confirm('¿Estás seguro de cambiar el estado de este producto?')) {
        fetch('../../controllers/admin/menu_controller.php?action=cambiarEstado', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&estado=${estadoActual}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                actualizarTabla();
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
}

function eliminarProducto(id) {
    if (confirm('¿Estás seguro de eliminar este producto?')) {
        fetch('../../controllers/admin/menu_controller.php?action=eliminar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                actualizarTabla();
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
}

function agregarCategoria() {
    const categoria = document.getElementById('nuevaCategoria').value;
    
    fetch('../../controllers/admin/menu_controller.php?action=agregarCategoria', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `categoria=${encodeURIComponent(categoria)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Categoría agregada correctamente');
            cerrarModal('modalCategoria');
            location.reload(); // Recargar para actualizar el select
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function mostrarPreview(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewFoto').src = e.target.result;
            document.getElementById('previewFoto').classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Cerrar modales al hacer clic fuera
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.parentElement.classList.add('hidden');
    }
});
    </script>
</body>
</html>