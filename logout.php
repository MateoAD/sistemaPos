<?php
session_start();

// Eliminar la cookie de recordar sesión si existe
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/'); // Establecer tiempo en el pasado para eliminar la cookie
}

// Destruir la sesión
session_destroy();

// Redirigir al index con ruta absoluta
header("Location: /sistemaPos/index.php");
exit();
?>