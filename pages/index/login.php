<?php
session_start();

// Si ya está logueado, redirigir según su rol
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['rol']) {
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        case 'mesero':
            header("Location: ../mesero/dashboard.php");
            break;
        case 'cajero':
            header("Location: ../cajero/dashboard.php");
            break;
        case 'cocinero':
            header("Location: ../cocinero/dashboard.php");
            break;
        default:
            session_destroy();
            header("Location: login.php");
            break;
    }
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    
    require_once '../../controllers/db.php';
    
    try {
        // Buscar usuario con credenciales válidas
        $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre 
                              FROM usuarios u 
                              JOIN roles r ON u.rol_id = r.id 
                              WHERE u.nombre = ? AND u.estado = 'activo'");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar contraseña (texto plano temporalmente)
        if ($user && $password === $user['contraseña']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usuario'] = $user['nombre'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol_nombre'];
            $_SESSION['sucursal_id'] = $user['sucursal_id'];
            
            // Redirigir según el rol
            switch ($user['rol_nombre']) {
                case 'admin':
                    header("Location: ../admin/dashboard.php");
                    break;
                case 'mesero':
                    header("Location: ../mesero/dashboard.php");
                    break;
                case 'cajero':
                    header("Location: ../cajero/dashboard.php");
                    break;
                case 'cocinero':
                    header("Location: ../cocinero/dashboard.php");
                    break;
                default:
                    $error = 'Rol no reconocido';
                    session_destroy();
                    break;
            }
            exit();
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-form {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px;
            width: 100%;
            font-weight: 600;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
        }
        .error-message {
            color: #dc3545;
            font-size: 14px;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h3><i class="fas fa-utensils"></i> Sistema POS</h3>
            <p class="mb-0">Inicio de Sesión</p>
        </div>
        <div class="login-form">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="usuario" class="form-label">
                        <i class="fas fa-user"></i> Usuario
                    </label>
                    <input type="text" class="form-control" id="usuario" name="usuario" required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Ingresa con tus credenciales asignadas
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>