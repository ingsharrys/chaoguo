<?php

require_once __DIR__ . '/../bootstrap.php';


require_once '../helpers/Session.php';
require_once '../helpers/Token.php'; // Asegurar que Token.php se carga
Session::start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../controllers/AuthController.php';
    $auth = new AuthController();

    if ($auth->login($_POST['email'], $_POST['password'], $_POST['recaptcha_token'] ?? '')) {
        header("Location: ../public");
        exit();
    } else {
        $login_error = 'Credenciales incorrectas. Inténtalo de nuevo.';
    }
}



?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Chao Guo · Iniciar Sesión</title>
        <link rel="icon" type="image/jpeg" href="../public/img/logo-chaoguo.jpg">

        <!-- Bootstrap 5 -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <!-- Estilos personalizados -->
        <link rel="stylesheet" href="../public/css/style.css?cache=chaoguo1">

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    </head>
    <body class="cuerpo">
        <div class="login-container">
            <img src="../public/img/logo-chaoguo.jpg" alt="Restaurante Chao Guo" class="brand-logo">
            <h3>Restaurante Chao Guo</h3>
            <p class="login-subtitle">Panel de administración</p>

            <?php if (isset($login_error)): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="login-form">
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="tucorreo@ejemplo.com" autocomplete="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" autocomplete="current-password" required>
                </div>

                <input type="hidden" name="recaptcha_token" id="recaptcha_token">

                <button type="submit" class="btn btn-primary w-100 btn-login">Iniciar sesión</button>
            </form>

            <p class="login-footer">admin.restaurantechaoguo.com</p>
        </div>
    </body>
    </html>
