<?php
header('Content-Type: application/json');
require_once '../../controllers/db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getEmpleadosPorRol':
        getEmpleadosPorRol();
        break;
    case 'getEmpleado':
        getEmpleado();
        break;
    case 'guardarEmpleado':
        guardarEmpleado();
        break;
    case 'toggleEstado':
        toggleEstado();
        break;
    case 'getSucursales':
        getSucursales();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

function getEmpleadosPorRol() {
    global $pdo;
    $rolId = $_GET['rol_id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("SELECT u.id, u.nombre, r.nombre as rol, s.nombre as sucursal, u.estado 
                              FROM usuarios u 
                              JOIN roles r ON u.rol_id = r.id 
                              LEFT JOIN sucursales s ON u.sucursal_id = s.id 
                              WHERE u.rol_id = ? AND u.estado IN ('activo', 'inactivo')
                              ORDER BY u.nombre");
        $stmt->execute([$rolId]);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $empleados]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getEmpleado() {
    global $pdo;
    $id = $_GET['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $empleado]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function guardarEmpleado() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? null;
    $nombre = $data['nombre'];
    $contrasena = $data['contrasena'];
    $rolId = $data['rolId'];
    $sucursalId = $data['sucursalId'] ?: null;
    
    try {
        if ($id) {
            // Actualizar
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, contraseña = ?, rol_id = ?, sucursal_id = ? WHERE id = ?");
            $stmt->execute([$nombre, $contrasena, $rolId, $sucursalId, $id]);
        } else {
            // Insertar
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, contraseña, rol_id, sucursal_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $contrasena, $rolId, $sucursalId]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function toggleEstado() {
    global $pdo;
    $id = $_POST['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET estado = CASE WHEN estado = 'activo' THEN 'inactivo' ELSE 'activo' END WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getSucursales() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT id, nombre FROM sucursales WHERE estado = 'activa'");
        $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $sucursales]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>