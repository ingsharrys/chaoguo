<?php
require_once __DIR__ . '/../autoload.php';
require_once 'Session.php';

Session::start(); // Iniciar sesión con medidas de seguridad

// Si el usuario no está autenticado, redirigir al login
if (!Session::get('user_id')) {
    header("Location: ../views/login.php");
    exit();
}
?>
