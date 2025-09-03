<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "pos_restaurante";

// Conexión PDO
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Error de conexión PDO: " . $e->getMessage());
}

// Conexión MySQLi para compatibilidad
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Error de conexión MySQLi: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>