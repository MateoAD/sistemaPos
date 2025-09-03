<?php
header('Content-Type: application/json');
require_once '../../controllers/db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listarProductos();
        break;
    case 'obtener':
        obtenerProducto();
        break;
    case 'guardar':
        guardarProducto();
        break;
    case 'cambiarEstado':
        cambiarEstado();
        break;
    case 'eliminar':
        eliminarProducto();
        break;
    case 'agregarCategoria':
        agregarCategoria();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

function listarProductos() {
    global $pdo;
    
    try {
        $sql = "SELECT m.*, 
                CASE 
                    WHEN m.estado = 'disponible' THEN 'Activo'
                    WHEN m.estado = 'no_disponible' THEN 'Inactivo'
                END as estado_texto 
                FROM menu m 
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($_GET['busqueda'])) {
            $sql .= " AND (m.nombre LIKE ? OR m.descripcion LIKE ?)";
            $busqueda = '%' . $_GET['busqueda'] . '%';
            $params = [$busqueda, $busqueda];
        }
        
        if (!empty($_GET['categoria'])) {
            $sql .= " AND m.categoria = ?";
            $params[] = $_GET['categoria'];
        }
        
        if (!empty($_GET['estado'])) {
            $sql .= " AND m.estado = ?";
            $params[] = $_GET['estado'];
        }
        
        $sql .= " ORDER BY m.categoria, m.nombre";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $productos]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function obtenerProducto() {
    global $pdo;
    $id = $_GET['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
        $stmt->execute([$id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $producto]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function guardarProducto() {
    global $pdo;
    
    try {
        $id = $_POST['id'] ?? null;
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio = $_POST['precio'];
        $categoria = $_POST['categoria'];
        $estado = $_POST['estado'];
        
        // Manejo de archivo
        $foto = null;
        if (!empty($_FILES['foto']['name'])) {
            $uploadDir = '../../uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto = uniqid() . '.' . $extension;
            move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $foto);
        }
        
        if ($id) {
            // Actualizar
            if ($foto) {
                $stmt = $pdo->prepare("UPDATE menu SET nombre = ?, descripcion = ?, precio = ?, categoria = ?, estado = ?, foto = ? WHERE id = ?");
                $stmt->execute([$nombre, $descripcion, $precio, $categoria, $estado, $foto, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE menu SET nombre = ?, descripcion = ?, precio = ?, categoria = ?, estado = ? WHERE id = ?");
                $stmt->execute([$nombre, $descripcion, $precio, $categoria, $estado, $id]);
            }
        } else {
            // Insertar
            $stmt = $pdo->prepare("INSERT INTO menu (nombre, descripcion, precio, categoria, estado, foto) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $precio, $categoria, $estado, $foto]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Producto guardado correctamente']);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function cambiarEstado() {
    global $pdo;
    $id = $_POST['id'] ?? 0;
    $nuevoEstado = $_POST['estado'] == 'disponible' ? 'no_disponible' : 'disponible';
    
    try {
        $stmt = $pdo->prepare("UPDATE menu SET estado = ? WHERE id = ?");
        $stmt->execute([$nuevoEstado, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function eliminarProducto() {
    global $pdo;
    $id = $_POST['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM menu WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Producto eliminado correctamente']);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function agregarCategoria() {
    global $pdo;
    $categoria = $_POST['categoria'];
    
    try {
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu WHERE categoria = ?");
        $stmt->execute([$categoria]);
        
        echo json_encode(['success' => true, 'message' => 'Categoría agregada correctamente']);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>