<?php
/*require_once '../helpers/Session.php';

Session::start();

//if (!isset($_SESSION['registro_acceso']) || $_SESSION['registro_acceso'] !== true) {
//    header("Location: login.php"); 
//    exit();
//}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../controllers/AuthController.php';
    $auth = new AuthController();
    if ($auth->register($_POST['username'], $_POST['email'], $_POST['password'])) {
        header("Location: login.php");
        exit();
    } else {
        echo "Registration failed!";
    }
}
*/
?>
<!--
<div class="form-container-register">
    <h2 class="txt_register">Formulario de Registro</h2>
    <form method="post" action="">
        <input class="input_register" type="text" name="username" placeholder="Nombre de usuario" required>
        <input class="input_register" type="email" name="email" placeholder="Correo electrónico" required>
        <input class="input_register" type="password" name="password" placeholder="Contraseña" required>
        <button class="button_register" type="submit">Registrarse</button>
    </form>
</div>
-->
