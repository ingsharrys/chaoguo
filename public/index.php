<?php
require_once __DIR__ . '/../bootstrap.php';


// Incluir la validación de la sesión
require_once '../helpers/session_validation.php';

// Definir URL base
define('BASE_URL', 'https://admin.restaurantechaoguo.com/');

// Incluir el encabezado de la página
include '../views/inc/header.php';

// Mostrar el modal si el código no está validado en la sesión
if (!Session::get('cajero')) {
    echo '<div id="modalContainer"></div>'; // Este contenedor cargará el modal si es necesario
    include '../views/inc/modal.php';
}
?>

<div class="content">
    <div id="content-area">
        <?php
        function getPageToInclude($user_id, $allowed_pages) {
            if (isset($_GET['page']) && in_array($_GET['page'], $allowed_pages)) {
                return "../views/{$_GET['page']}";
            }

            // Lógica de inclusión basada en $user_id
            switch ($user_id) {
                case 4:
                    return "../views/whatsapp.php";
                case 3:
                    return "../views/llamadas.php";
                default:
                    return "../views/dashboard.php";
            }
        }

        $allowed_pages = [
            'dashboard.php', 'productos.php', 'reservas.php', 'reportes.php', 
            'domiciliarios.php', 'meseros.php', 'whatsapp.php', 'asig_domi.php', 
            'caja.php', 'caja_tm.php', 'domicilios.php', 'edit_pedido.php', 
            'repor_mese.php', 'llamadas.php', 'procesar_caja.php', 'consolidado.php', 
            'gastos.php', 'creditos.php', 'whatsapp_olds.php', 'estadistica.php', 'register.php' , 'inicios.php' ];

        // Obtener la página que se incluirá
        $page_to_include = getPageToInclude(Session::get('user_id'), $allowed_pages);
        include $page_to_include;
        ?>
    </div>
</div>

<?php include '../views/inc/footer.php'; ?>

<!-- Archivos de JavaScript -->

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/js/modal_script.js?cache=76gik"></script>
