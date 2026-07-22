<?php
require_once '../helpers/Session.php';
Session::start();

// Destruir la sesión
Session::destroy();

// Redirigir al usuario a la página de login
header("Location: login.php");
exit();
?>
