<?php
/**
 * @var int    $tipo_solicitud
 * @var string $celular
 * @var array  $pedidosPendientes
 * @var string $nombreCliente
 * @var string $direccionCliente
 * @var string $emailCliente
 * @var string $cedulaCliente
 * @var string $barrioCliente
 * @var array  $productosOrganizados
 * @var int    $dia_semana
 * @var string $pedido
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <!-- CSS de Bootstrap y FontAwesome -->
    <link rel="stylesheet" href="/menu/css/style_prueba.css?cache=bdfg">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name=viewport content="width=device-width, initial-scale=1">
</head>
<body>

<header class="d-flex justify-content-center py-3">
    <img src="https://admin.restaurantechaoguo.com/path/to/images/logo.jpg"
         style="width: 140px;height:56px"
         alt="Logotipo"
         class="header-logo">
</header>
<style>
    #scrollContainer {
        overflow-x: scroll !important;
        min-height: 60px;
    }
    
    #scrollContainer::-webkit-scrollbar {
        height: 16px !important;
        background: #e0e0e0;
    }
    
    #scrollContainer::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    #scrollContainer::-webkit-scrollbar-thumb:hover {
        background: #500;
    }
    
    #scrollContainer {
        scrollbar-color: #888 #e0e0e0;
        scrollbar-width: thick;
    }
    .justify-content-start{
            justify-content: flex-start !important;
    overflow-x: scroll;
    }
    .card-text {
    font-size: 0.9rem;
    color: #fff;
}
</style>
<div class="container mt-5">
    <h4 class="text-center mb-4">
        Pedidos Pendientes para Hoy de <?php echo htmlspecialchars($nombreCliente); ?>
    </h4>

    <?php if (count($pedidosPendientes) > 0): ?>
        <div class="card">
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($pedidosPendientes as $pedidoIndex => $pedidoData): ?>
                        <?php
                            switch ($pedidoData['tipo_solicitud']) {
                                case 50: $tipo_pedido = "Domicilio"; break;
                                case 51: $tipo_pedido = "Local"; break;
                                case 53: $tipo_pedido = "Llamada"; break;
                                default: $tipo_pedido = "Desconocido"; break;
                            }
    
                            // Mostrar encabezado si es el primer pedido o cambia el turno / tipo_solicitud
                            if (
                                $pedidoIndex === 0
                                || $pedidoData['turno'] !== $pedidosPendientes[$pedidoIndex - 1]['turno']
                                || $pedidoData['tipo_solicitud'] !== $pedidosPendientes[$pedidoIndex - 1]['tipo_solicitud']
                            ):
                        ?>
                            <strong>Pedido con N° de Turno <?php echo htmlspecialchars($pedidoData['turno']); ?>
                            para <?php echo htmlspecialchars($tipo_pedido); ?>
                            - (<?php echo htmlspecialchars($pedidoData['fecha']); ?>): </strong>
                            <ul class="list-group">
                        <?php endif; ?>
    
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <ul>
                                    <li>
                                        <?php echo htmlspecialchars($pedidoData['nombre']); ?>
                                        <span class="badge badge-pill badge-info">
                                            Cantidad: <?php echo htmlspecialchars($pedidoData['cantidad']); ?>
                                        </span>
    
                                        <?php if ($pedidoData['estado'] === 'entregado'): ?>
                                            <span class="badge badge-pill badge-success">Pedido entregado</span>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            </div>
                        </li>
    
                        <?php
                            // Cerrar la lista si es el último elemento o si el siguiente cambia
                            if (
                                $pedidoIndex === count($pedidosPendientes) - 1
                                || $pedidoData['turno'] !== $pedidosPendientes[$pedidoIndex + 1]['turno']
                                || $pedidoData['tipo_solicitud'] !== $pedidosPendientes[$pedidoIndex + 1]['tipo_solicitud']
                            ):
                        ?>
                            </ul>
                            <div class="d-flex align-items-center">
                                <?php if ($pedidoData['estado'] === 'nuevo'): ?>
                                    <span class="bombillo-rojo mr-3"><i class="fas fa-lightbulb"></i></span>
                                    <a href="https://wa.me/573174742056?text=Hola,%20me%20gustaría%20agregar%20o%20quitar%20algo%20del%20pedido%20con%20turno%20<?php echo $pedidoData['turno']; ?>"
                                       class="btn btn-outline-danger btn-sm">Modificar Pedido</a>
                                <?php elseif ($pedidoData['estado'] === 'en_cocina'): ?>
                                    <span class="bombillo-naranja mr-3"><i class="fas fa-lightbulb"></i></span>
                                    <a href="https://wa.me/573174742056?text=Hola,%20me%20gustaría%20agregar%20o%20quitar%20algo%20del%20pedido%20con%20turno%20<?php echo $pedidoData['turno']; ?>"
                                       class="btn btn-outline-warning btn-sm">Modificar Pedido</a>
                                <?php elseif ($pedidoData['estado'] === 'entregado'): ?>
                                    <span class="bombillo-verde mr-3"><i class="fas fa-lightbulb"></i></span>
                                <?php endif; ?>
                            </div>
                            <br>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">No tienes pedidos pendientes para hoy.</div>
    <?php endif; ?>
</div>

<div class="container mb-4">
    <h5 class="mr-3">Estado de su pedido</h5>
    <span class="bombillo-rojo mr-3"><i class="fas fa-lightbulb"></i> En Espera</span>
    <span class="bombillo-naranja mr-3"><i class="fas fa-lightbulb"></i> En Cocina</span>
    <span class="bombillo-verde mr-3"><i class="fas fa-lightbulb"></i> Entregado</span>
</div>

<div class="container mb-4">
    <input type="text" id="productSearch" class="form-control" placeholder="Buscar productos...">
    <!-- Carrusel / Nav -->
    <div id="filterCarousel" class="carousel slide" data-ride="carousel">
        <div class="carousel-inner d-flex flex-nowrap" id="scrollContainer">
            <?php include 'nav.php' ?>
        </div>
    </div>
</div>

<?php if ($pedido !== 'wp' && $pedido !== 'fb'): ?>
 <!--   <a href="https://wa.me/573174742056" class="whatsapp-button">
        <i class="fab fa-whatsapp"></i>
    </a> -->
<?php endif; ?>

<div class="container" style="z-index: 1; position: relative ">
    <div class="row" id="product-list" style="z-index: 1; position: relative ">
        <?php foreach ($productosOrganizados as $producto): ?>
            <?php 
            // No mostrar producto con id=51 salvo sábado o domingo
            if ($producto['id_pro'] == 51 && ($dia_semana != 6 && $dia_semana != 7)) {
                continue;
            }
            ?>
            <div class="col-sm-12 col-12 product-card" data-category="<?php echo $producto['cat']; ?>">
                <h5 class="card-title">
                    <label class="form-check-label"
                           style="color: #000;font-size: 12pt;margin-bottom: 3%;"
                           for="product-<?php echo htmlspecialchars($producto['id_pro']); ?>">
                        <?php echo htmlspecialchars($producto['nombre']); ?>
                    </label>
                </h5>
            </div>
            <div class="col-sm-12 col-md-12 mb-4 product-card"
                 data-category="<?php echo $producto['cat']; ?>"
                  data-tcomida="<?php echo $producto['tcomida']; ?>"
                 data-prefix="<?php echo $producto['prefijo']; ?>" style="z-index: 1; position: relative ">

                <div class="card-producto mb-3" style="max-width: 540px;">
                    <div class="row g-0">
                        <div class="col-sm-4 col-4">
                            <img src="<?php echo 'https://admin.restaurantechaoguo.com/path/to/productos/' . htmlspecialchars($producto['img']); ?>"
                                 class="card-img-top product-image lazyload"
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                 data-toggle="modal"
                                 data-target="#descriptionModal"
                                 data-image="<?php echo 'https://admin.restaurantechaoguo.com/path/to/images/' . htmlspecialchars($producto['img']); ?>"
                                 data-description="<?php echo htmlspecialchars($producto['descript']); ?>">
                        </div>
                        <div class="col-sm-8 col-8">
                            <div class="card-body-producto">
                                <h5 class="card-title">
                                    <label class="form-check-label" style="color: #000;font-size: 12pt;margin-bottom: 3%;"
                                           for="product-<?php echo htmlspecialchars($producto['id_pro']); ?>">
                                        <?php echo htmlspecialchars($producto['nombre']); ?>
                                    </label>
                                </h5>
                                <div class="row">
                                    <?php foreach ($producto['precios'] as $precio): ?>
                                        <?php if ($precio['tipo_prod'] && $precio['precio_tipo']): ?>
                                            <div class="col-12 mb-2">
                                                <div class="row" style="background: black;border-radius: 10px;">
                                                    <div class="col-5">
                                                        <p class="card-text mb-0">
                                                            <?php echo htmlspecialchars($precio['tipo_prod']); ?>
                                                        </p>
                                                        <p class="card-text mb-0">
                                                            $<?php echo number_format($precio['precio_tipo'], 0, '', ','); ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-7">
                                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                                            <button type="button" class="btn btn-secondary btn-minus">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                            <input type="number"
                                                                   style="color: #fff !important;background:#000 !important"
                                                                   class="form-control quantity-input"
                                                                   data-id="<?php echo $producto['id_pro']; ?>"
                                                                   data-product-name="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                                   data-prefix="<?php echo $producto['prefijo']; ?>"
                                                                   data-product-type="<?php echo htmlspecialchars($precio['tipo_prod']); ?>"
                                                                   data-price="<?php echo $precio['precio_tipo']; ?>"
                                                                   value="0" min="0" readonly>
                                                            <button type="button" class="btn btn-secondary btn-plus">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div><!-- row -->
                            </div><!-- card-body -->
                        </div><!-- col-8 -->
                    </div><!-- row g-0 -->
                </div><!-- card -->
            </div><!-- col -->
        <?php endforeach; ?>
    </div><!-- row -->
</div><!-- container -->

<!-- Modal Formulario -->
<?php include 'partials/orderFormModal.php'; ?>

<!-- Modal Pedido Enviado -->
<?php include 'partials/orderSentModal.php'; ?>

<!-- Botones flotantes -->
<button id="selectProductsButton" class="btn btn-secondary floating-button"
        style="background-color: #500;font-size:10pt">
    Seleccionar productos
</button>
<button id="makeOrderButton" class="btn btn-primary floating-button" style="display:none;">
    Hacer pedido
</button>
<button id="pedidoExistenteButton" class="btn btn-primary floating-button" style="display:none;">
    Ya tienes un pedido
</button>

<!-- Modal descripción del producto -->
<?php include 'partials/descriptionModal.php'; ?>

<!-- Scripts -->
<!-- jQuery primero -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<!-- Popper y Bootstrap JS (para modales) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- lazySizes si lo usas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.2.2/lazysizes.min.js" async></script>

<!-- Si necesitas inyectar pedidosPendientes desde PHP -->
<script>
  window.pedidosPendientes = <?php echo $pedidosPendientesJSON ?? '[]'; ?>;
</script>
<script>const pedidosPendientes = <?php echo $pedidosPendientesJSON; ?>;</script>
<!-- Tu archivo JS (donde está todo el código de +, −, modales...) -->
<script src="/menu/js/script.js?cache=dggkl"></script>

</body>
</html>


</body>
</html>
