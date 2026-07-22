<?php

require_once __DIR__ . '/../bootstrap.php';


require_once '../helpers/Session.php';
require_once '../helpers/Token.php'; // Asegurar que Token.php se carga
Session::start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../controllers/AuthController.php';
    $auth = new AuthController();

    if ($auth->login($_POST['email'], $_POST['password'], $_POST['recaptcha_token'])) {
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
        <title>PideYAPP - Iniciar Sesión</title>
    
        <!-- Bootstrap 5 -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <!-- Estilos personalizados -->
        <link rel="stylesheet" href="../public/css/style.css?cache=efh">
    
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
        
        <!-- reCAPTCHA -->
        <script src="https://www.google.com/recaptcha/api.js?render=6Ldij9AqAAAAAKtJOEIbFQ-d1KquTGaucM1iFLYJ"></script>
    
        
    </head>
    <body class="cuerpo" >
        <div class="login-container">
            <img src="../public/img/pideyapp.png" alt="PideYAPP Logo" class="brand-logo">
            <h3>PideYAPP</h3>
            <p class="text-center text-muted">Inicia sesión para continuar</p>
    
            <?php if (isset($login_error)): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
    
            <form method="post" action="" id="login-form">
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" id="email" class="form-control" value="pedidos@datarie.info" placeholder="Tu correo" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" name="password" id="password"  class="form-control" placeholder="Tu contraseña" required>
                </div>
    
                <input type="hidden" name="recaptcha_token" id="recaptcha_token">
    
                <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
            </form>
        </div>
    
        <script>
            // Ejecutar reCAPTCHA v3
            grecaptcha.ready(function() {
                grecaptcha.execute('6Ldij9AqAAAAAKtJOEIbFQ-d1KquTGaucM1iFLYJ', {action: 'login'}).then(function(token) {
                    document.getElementById('recaptcha_token').value = token;
                });
            });
        </script>
    </body>
    </html>
