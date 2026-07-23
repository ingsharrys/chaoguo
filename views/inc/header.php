<?php require_once '../controllers/header_controller.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chao Guo · Administración</title>
    <link rel="icon" type="image/jpeg" href="../public/img/logo-chaoguo.jpg">
    <link rel="stylesheet" href="../public/css/style.css?cache=<?php echo rand(10,100); ?>">
    <link  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://kit.fontawesome.com/744a196ea0.js" crossorigin="anonymous"></script>
    <script type="text/javascript" src="/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>
</head>
<body>
    
    <div class="sidebar">
        <h2><?php echo htmlspecialchars($cajero); ?></h2>
        <ul>
            <?php 
            $url_index = "../public/index.php?page=";
            $currentPage = $_GET['page'] ?? '';

            $menus = [
                'domi' => ['whatsapp.php' => 'Domicilios', 'llamadas.php' => 'Recoger/WP'],
                'turno' => ['dashboard.php' => 'Pedidos', 'whatsapp.php' => 'Domicilios', 'llamadas.php' => 'Recoger/WP'],
                'admin' => [
                    'dashboard.php' => 'Pedidos', 'llamadas.php' => 'Recoger/WP', 'whatsapp.php' => 'Domicilios', 
                    'productos.php' => 'Productos', 'estadistica.php' => 'Estadísticas', 
                    'domiciliarios.php' => 'Domiciliarios', 'meseros.php' => 'Colaboradores', 
                    'gastos.php' => 'Gastos', 'consolidado.php' => 'Consolidado', 
                    'register.php' => 'Registrar', 'reportes.php' => 'Reportes'
                ],
                'cajero' => ['dashboard.php' => 'Pedidos', 'llamadas.php' => 'Recoger/WP', 'gastos.php' => 'Gastos', 'whatsapp.php' => 'Domicilios', 'consolidado.php' => 'Consolidado',  'domiciliarios.php' => 'Domiciliarios'],
                'default' => ['dashboard.php' => 'Pedidos', 'llamadas.php' => 'Recoger/WP', 'whatsapp.php' => 'Domicilios']
            ];

            $menu_items = $menus[$cargo] ?? $menus['default'];

            foreach ($menu_items as $page => $title) {
                $active_class = ($currentPage === $page) ? 'active' : '';
                echo "<li class='$active_class'><a href='$url_index$page' class='menu-link'>$title</a></li>";
            }
            ?>
            <li><a href="../views/logout.php" class="menu-link">Cerrar sesión</a></li>
        </ul>

        <!-- Agregar botón para abrir modal de base -->
        
        <button class="btn btn-warning btn-block mt-3" onclick="abrirModalBase()">Base</button>

        <div class="info-box">
            <p><strong>Gastos:</strong> 
               <span style="color: #04f504">$<?php echo number_format($total_gastos, 0); ?></span>
            </p>
            <p><strong>Base:</strong> 
               <span style="color: #04f504" id="base_mostrada">$0</span>
               <span style="color: #04f504" id="cajero_base_mostrado"></span>
            </p>
            <p><strong>Efectivo + Base:</strong> 
               <span style="color: #04f504">$<?php echo number_format($total_efectivo, 0); ?></span>
               
            </p>
            <p><strong>Tarjeta:</strong> 
               <span style="color: #04f504">$<?php echo number_format($total_tarjeta, 0); ?></span>
            </p>
            <p><strong>Transferencia:</strong> 
               <span style="color: #04f504">$<?php echo number_format($total_transferencia, 0); ?></span>
            </p>
        </div>
    </div>


    <!-- Modal para ingresar base -->
        <div id="modalBase" class="modal-container" style="display: none;">
            <div class="modal-content">
                <span class="close-btn" onclick="cerrarModalBase()">&times;</span>
                <h3>Ingresar Base</h3>
                <input type="hidden" id="cajero" value="<?php echo htmlspecialchars($idsmese); ?>">
                <input type="number" id="valorBase" class="form-control" placeholder="Ingrese la base">
                <button class="btn btn-primary mt-2" onclick="guardarBase()">Guardar</button>
            </div>
        </div>


    <script>
       function abrirModalBase() {
    document.getElementById('modalBase').style.display = 'block';
}

function cerrarModalBase() {
    document.getElementById('modalBase').style.display = 'none';
}

function guardarBase() {
    let valorBase = document.getElementById('valorBase').value;
    let cajero = document.getElementById('cajero').value;
    

    if (!valorBase || valorBase <= 0) {
        alert("Ingrese un valor válido.");
        return;
    }

    $.post("../controllers/guardar_base.php", { base: valorBase, cajero: cajero }, function(response) {
        try {
            let data = JSON.parse(response);
            if (data.status === 'success') {
                alert("Base guardada correctamente.");
                cerrarModalBase();
                location.reload(); // Recargar la página para reflejar cambios
            } else {
                alert("Error al guardar la base: " + data.message);
            }
        } catch (e) {
            console.error("Error en la respuesta del servidor:", response);
            alert("Ocurrió un error inesperado.");
        }
    }).fail(function() {
        alert("Error en la conexión con el servidor.");
    });
}


        function cargarBase() {
            let cajeroActual = "<?php echo htmlspecialchars($idsmese); ?>"; // Cajero actual en PHP

    $.get("../controllers/obtener_base.php", function(cajeroActual) {
        console.log('Cajero actual', cajeroActual);
        console.log( 'cajero base', data.cajero_base);
        
     if (cajeroActual === data.cajero_base){
            document.getElementById("base_mostrada").innerText = `$${parseFloat(data.base).toLocaleString('es-CO')}`;
        document.getElementById('cajero_base_mostrado').innerText = `Cajero: ${data.cajero_base}`;
        document.getElementById("efectivo_total").innerText = `$${parseFloat(data.efectivo_total).toLocaleString('es-CO')}`;
   
    } else{
        baseElement.innerText = "0$";
    }
        
    }, "json");
}

$(document).ready(function() {
    cargarBase(); // Cargar la base al iniciar la página
});


        $(document).ready(function() {
            cargarBase(); // Cargar la base al cargar la página
        });
    </script>

</body>
</html>
