<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'redirect' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $usuario = $data['usuario'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $response['message'] = 'Por favor complete todos los campos';
        echo json_encode($response);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre 
                              FROM usuarios u 
                              JOIN roles r ON u.rol_id = r.id 
                              WHERE u.usuario = ? AND u.estado = 1");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol_nombre'];
            $_SESSION['sucursal_id'] = $user['sucursal_id'];
            
            $response['success'] = true;
            
            // Determinar redirección según el rol
            switch ($user['rol_nombre']) {
                case 'admin':
                    $response['redirect'] = '../admin/dashboard.php';
                    break;
                case 'mesero':
                    $response['redirect'] = '../mesero/dashboard.php';
                    break;
                case 'cajero':
                    $response['redirect'] = '../cajero/dashboard.php';
                    break;
                case 'cocinero':
                    $response['redirect'] = '../cocinero/dashboard.php';
                    break;
                default:
                    $response['message'] = 'Rol no reconocido';
                    $response['success'] = false;
                    break;
            }
        } else {
            $response['message'] = 'Usuario o contraseña incorrectos';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error al procesar la solicitud';
    }
}

echo json_encode($response);
?>