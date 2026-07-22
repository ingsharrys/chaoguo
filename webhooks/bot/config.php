<?php
$host = 'localhost';  // O el IP del servidor de la base de datos
$dbname = 'datarie_ristaurant';
$user = 'datarie_nuev';
$password = 'v06r-206.9;V';

// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexion fallida: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>