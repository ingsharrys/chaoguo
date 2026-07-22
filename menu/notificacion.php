<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación</title>
    <!-- Favicon -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <script src="https://kit.fontawesome.com/744a196ea0.js" crossorigin="anonymous"></script>
    <!-- SweetAlert -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.26/dist/sweetalert2.min.css">  
</head>
<body>

<!-- SweetAlerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.26/dist/sweetalert2.all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Obtener parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const success = urlParams.get('success');
    const numero = urlParams.get('orderNumber');

    // Mostrar alerta dependiendo del parámetro de error
    if (error) {
        let title = 'Oops...';
        let text = 'algo ha salido mal ';

        switch (error) {
            case 'incorrecta':
                text = 'su pedido no ha sido completado ';
                break;
            
            default:
                text = 'Ha ocurrido un error desconocido.';
        }

        Swal.fire({
            icon: 'error',
            title: title,
            text: text,
            allowOutsideClick: false, // Desactiva el cierre al hacer clic fuera del modal
            allowEscapeKey: false, // Desactiva el cierre con la tecla Esc
            allowEnterKey: false, // Desactiva el cierre con la tecla Enter
            showConfirmButton: false, // Oculta el botón de confirmación
        });
    }
    
    if (success) {
        let title = 'Excelente!';
        let text = '';

        switch (success) {
            case 'correcta':
                text = `Tu pedido está en preparación y en un promedio de 40 min a 1 hora estará llegando a tu casa, en un momento informaremos el costo del domicilio ${numero}`;
                break;
            
            default:
                text = 'Ha ocurrido un error desconocido.';
        }

        Swal.fire({
            icon: 'success',
            title: title,
            text: text,
            allowOutsideClick: false, // Desactiva el cierre al hacer clic fuera del modal
            allowEscapeKey: false, // Desactiva el cierre con la tecla Esc
            allowEnterKey: false, // Desactiva el cierre con la tecla Enter
            showConfirmButton: false, // Oculta el botón de confirmación
        });
    }
    
});
</script>

</body>
</html>
