<?php
session_start();

// Verificar si existe una cookie de usuario guardado
if (isset($_COOKIE['remember_username'])) {
    $saved_username = $_COOKIE['remember_username'];
} else {
    $saved_username = '';
}

// Si ya está logueado, redirigir según su rol
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['rol']) {
        case 'admin':
            header("Location: pages/admin/dashboard.php");
            break;
        case 'mesero':
            header("Location: pages/mesero/dashboard.php");
            break;
        case 'cajero':
            header("Location: pages/cajero/dashboard.php");
            break;
        case 'cocinero':
            header("Location: pages/cocinero/dashboard.php");
            break;
        default:
            session_destroy();
            header("Location: index.php");
            break;
    }
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    require_once 'controllers/db.php';
    
    try {
        $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre 
                              FROM usuarios u 
                              JOIN roles r ON u.rol_id = r.id 
                              WHERE u.nombre = ? AND u.estado = 'activo'");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $password === $user['contraseña']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usuario'] = $user['nombre'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol_nombre'];
            $_SESSION['sucursal_id'] = $user['sucursal_id'];
            
            // Si el usuario marcó "recordar", guardar solo el nombre de usuario
            if ($remember) {
                setcookie('remember_username', $user['nombre'], time() + (30 * 24 * 60 * 60), '/'); // 30 días
            } else {
                // Si no quiere recordar, eliminar la cookie si existe
                if (isset($_COOKIE['remember_username'])) {
                    setcookie('remember_username', '', time() - 3600, '/');
                }
            }
            
            switch ($user['rol_nombre']) {
                case 'admin':
                    header("Location: pages/admin/dashboard.php");
                    break;
                case 'mesero':
                    header("Location: pages/mesero/dashboard.php");
                    break;
                case 'cajero':
                    header("Location: pages/cajero/dashboard.php");
                    break;
                case 'cocinero':
                    header("Location: pages/cocinero/dashboard.php");
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
            background: #0F0F0F;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #1E201F;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: #2C302F;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-form {
            padding: 40px;
            color: white;
        }
        .form-control {
            background: #2C302F;
            border: 1px solid #1E201F;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
            color: white;
        }
        .form-control:focus {
            background: #2C302F;
            border-color: #0F0F0F;
            box-shadow: 0 0 0 0.2rem rgba(15, 15, 15, 0.25);
            color: white;
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        .form-label {
            color: white;
        }
        .btn-login {
            background: #2C302F;
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: #0F0F0F;
            color: white;
            transform: translateY(-2px);
        }
        .error-message {
            color: #ff4d4d;
            font-size: 14px;
            text-align: center;
            margin-bottom: 15px;
            background: rgba(255, 77, 77, 0.1);
            padding: 10px;
            border-radius: 8px;
        }
        .form-check-label {
            color: white;
        }
        .form-check-input:checked {
            background-color: #2C302F;
            border-color: #0F0F0F;
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
                    <input type="text" class="form-control" id="usuario" name="usuario" 
                           value="<?php echo htmlspecialchars($saved_username); ?>" required 
                           placeholder="Ingresa tu usuario">
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required
                           placeholder="Ingresa tu contraseña">
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember"
                           <?php echo $saved_username ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="remember">
                        <i class="fas fa-clock"></i> Recordar mi usuario
                    </label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
            
            <div class="text-center mt-3">
                <small style="color: rgba(255, 255, 255, 0.6);">
                    <i class="fas fa-info-circle"></i> 
                    Ingresa con tus credenciales asignadas
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>