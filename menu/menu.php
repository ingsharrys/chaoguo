<?php
require_once '../config/database.php';

// Obtener el valor de $pedido
$pedido = isset($_GET['pedido']) ? $_GET['pedido'] : null;
$telefono = isset($_GET['phone']) ? $_GET['phone'] : null;
$celular = (substr($telefono, 0, 1) === '+') ? substr($telefono, 3) : $telefono;

$tipo_solicitud = ($pedido === 'qr') ? 51 : (($pedido === 'wp') ? 50 : (($pedido === 'call') ? 53 : null));

date_default_timezone_set('America/Bogota');  
$fecha_actual = date('Y-m-d');

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Consultar si el celular existe en la tabla clientes
$queryCliente = "SELECT * FROM clientes WHERE celular = ?";
$stmtCliente = $conn->prepare($queryCliente);
$stmtCliente->execute([$celular]);
$cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

// Si el cliente existe, obtener su ID para consultar los pedidos
if ($cliente) {
    $id_cliente = $cliente['id']; 

        // Consulta SQL para obtener los pedidos pendientes del cliente
        $queryPedidos = "SELECT p.id_pedido, p.cantidad, p.numero_pedido, p.fecha, t.estado, t.turno, pr.nombre AS producto
        FROM pedidos p
        INNER JOIN turnero t ON p.numero_pedido = t.id_pedido
        INNER JOIN productos pr ON p.id_pro = pr.id_pro
        WHERE p.id_cliente = ? AND DATE(p.fecha) = ?";

$stmtPedidos = $conn->prepare($queryPedidos);
$stmtPedidos->execute([$id_cliente, $fecha_actual]);
$pedidosPendientes = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);


        // Consulta SQL para obtener el estado y el turno del pedido desde la tabla turnero
        if (count($pedidosPendientes) > 0) {
            $id_pedido = $pedidosPendientes[0]['numero_pedido'];
            $queryTurno = "SELECT estado, turno FROM turnero WHERE id_pedido = ?";
            $stmtTurno = $conn->prepare($queryTurno);
            $stmtTurno->execute([$id_pedido]);
            $turnoData = $stmtTurno->fetch(PDO::FETCH_ASSOC);
            
            // Si se encuentra información del turno, extraerla
            if ($turnoData) {
                $estado = $turnoData['estado'];
                $turno = $turnoData['turno'];
            } else {
                $estado = 'desconocido';
                $turno = 'No asignado';
            }
        }


} else {
    $pedidosPendientes = []; 
}

// Obtener detalles del cliente para mostrarlos en el formulario
$nombreCliente = $cliente ? $cliente['cliente'] : '';
$direccionCliente = $cliente ? $cliente['direccion'] : '';
$emailCliente = $cliente ? $cliente['email'] : '';
$cedulaCliente = $cliente ? $cliente['cedula'] : '';
$barrioCliente = $cliente ? $cliente['barrio'] : '';




$query = "SELECT p.*, pr.tipo_prod, pr.precio as precio_tipo 
          FROM productos p 
          LEFT JOIN precios pr ON p.id_pro = pr.idproduc";
$stmt = $conn->prepare($query);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar productos por id_pro
$productosOrganizados = [];
foreach ($productos as $producto) {
    $id_pro = $producto['id_pro'];
    if (!isset($productosOrganizados[$id_pro])) {
        $productosOrganizados[$id_pro] = [
            'id_pro' => $producto['id_pro'],
            'nombre' => $producto['nombre'],
            'prefijo' => $producto['prefijo'],
            'img' => $producto['img'],
            'descript' => $producto['descript'],
            'cat' => $producto['cat'],
            'precios' => [],
        ];
    }
    $productosOrganizados[$id_pro]['precios'][] = [
        'tipo_prod' => $producto['tipo_prod'],
        'precio_tipo' => $producto['precio_tipo'],
    ];
}

// Obtener el día de la semana (1 = Lunes, 7 = Domingo)
$dia_semana = date('N');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/style_prueba.css?cache=<?php echo(rand(10,100)); ?>">
    <script src="https://kit.fontawesome.com/6798722c94.js" crossorigin="anonymous"></script>
</head>

    <style>
        .bombillo-rojo {
            color: red;
        }
        .bombillo-naranja {
            color: orange;
        }
        .bombillo-verde {
            color: green;
        }
    </style>
</head>
<script>
    $(document).ready(function() {
        // Verifica si ya se ha recargado la página
        if (!sessionStorage.getItem('pageReloaded')) {
            // Establece una bandera para indicar que la página ya se ha recargado
            sessionStorage.setItem('pageReloaded', 'true');
            // Recarga la página
            window.location.reload();
        }
    });
</script>

<body>

<header class="d-flex justify-content-center py-3">
    <img src="path/to/images/logo-Heiyubai.jpg" style="width: 140px;height:56px" alt="Logotipo" class="header-logo">
</header>

<div class="container mt-5">
    <h4 class="text-center mb-4">Pedidos Pendientes para Hoy de <?php echo htmlspecialchars($nombreCliente); ?></h4>

    <div class="d-flex align-items-center">
                        <!-- Mostrar el estado del pedido con el bombillo correspondiente -->
                        <?php if ($turnoData['estado'] === 'nuevo'): ?>
                            <span class="bombillo-rojo mr-3"><i class="fas fa-lightbulb"></i></span>
                            <a href="https://wa.me/573174742056?text=Hola,%20me%20gustaría%20agregar%20o%20quitar%20algo%20del%20pedido%20con%20turno%20<?php echo $turnoData['turno']; ?>" class="btn btn-outline-danger btn-sm">Modificar Pedido</a>
                        <?php elseif ($turnoData['estado'] === 'en_cocina'): ?>
                            <span class="bombillo-naranja mr-3"><i class="fas fa-lightbulb"></i></span>
                            <a href="https://wa.me/573174742056?text=Hola,%20me%20gustaría%20agregar%20o%20quitar%20algo%20del%20pedido%20con%20turno%20<?php echo $turnoData['turno']; ?>" class="btn btn-outline-warning btn-sm">Modificar Pedido</a>
                        <?php elseif ($turnoData['estado'] === 'entregado'): ?>
                            <span class="bombillo-verde mr-3"><i class="fas fa-lightbulb"></i></span>
                        <?php endif; ?>
                    </div>

    <?php if (count($pedidosPendientes) > 0): ?>
        <div class="card">
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <?php foreach ($pedidosPendientes as $pedido): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Pedido:</strong> <?php echo htmlspecialchars($pedido['producto']); ?> 
                        <span class="badge badge-pill badge-info">Cantidad: <?php echo htmlspecialchars($pedido['cantidad']); ?></span>
                        <!-- Mostrando el turno -->
                        <span class="badge badge-pill badge-secondary">Turno: <?php echo htmlspecialchars($pedido['turno']); ?></span>
                        <?php if ($pedido['estado'] === 'entregado'): ?>
                            <span class="badge badge-pill badge-success">Pedido entregado</span>
                        <?php endif; ?>
                    </div>
                    
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

    <?php else: ?>
        <div class="alert alert-info text-center">No tienes pedidos pendientes para hoy.</div>
    <?php endif; ?>
</div>
<div class="container mb-4">
    <!-- Carrusel de botones para móviles -->
    <input type="text" id="productSearch" class="form-control" placeholder="Buscar productos...">
    <div id="filterCarousel" class="carousel slide" data-ride="carousel">
        <div class="carousel-inner d-flex flex-nowrap" id="scrollContainer">
        <?php include 'nav.php' ?>
        </div>
        
       
    </div>
    <!-- Botones para escritorio -->
  
</div>
<?php if ($pedido !== 'wp' && $pedido !== 'fb'): ?>
<a href="https://wa.me/573174742056" class="whatsapp-button">
    <i class="fab fa-whatsapp"></i>
</a>
<?php endif; ?>

<div class="container">
    <div class="row" id="product-list">
        <?php foreach ($productosOrganizados as $producto): ?>
            <?php 
            // No mostrar el producto con id=51 si no es sábado o domingo
            if ($producto['id_pro'] == 51 && ($dia_semana != 6 && $dia_semana != 7)) {
                continue;
            }
            ?>
            <div class="col-sm-12 col-12 product-card" data-category="<?php echo $producto['cat']; ?>">
                <h5 class="card-title">
                    <label class="form-check-label" style="color: #000;font-size: 12pt;margin-bottom: 3%;" for="product-<?php echo htmlspecialchars($producto['id_pro']); ?>">
                        <?php echo htmlspecialchars($producto['nombre']); ?>
                    </label>
                </h5>
            </div>
            <div class="col-sm-12 col-md-12 mb-4 product-card" data-category="<?php echo $producto['cat']; ?>" data-prefix="<?php echo $producto['prefijo']; ?>">
                <div class="card mb-3" style="max-width: 540px;">
                    <div class="row g-0">
                        <div class="col-sm-4 col-4">
                            <img data-src="<?php echo 'https://heiyubai.datarie.info/path/to/productos/' . htmlspecialchars($producto['img']); ?>" class="card-img-top product-image lazyload" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" data-toggle="modal" data-target="#descriptionModal" data-image="<?php echo 'https://rosticeria.datarie.info/path/to/images/' . htmlspecialchars($producto['img']); ?>" data-description="<?php echo htmlspecialchars($producto['descript']); ?>">
                        </div>
                        <div class="col-sm-8 col-8">
                            <div class="card-body">
                                <h5 class="card-title">
                                  
                                </h5>
                                <div class="row">
                                    <?php foreach ($producto['precios'] as $precio): ?>
                                        <?php if ($precio['tipo_prod'] && $precio['precio_tipo']): ?>
                                            <div class="col-12 mb-2">
                                                <div class="row" style="background: black;border-radius: 10px;">
                                                    <div class="col-6">
                                                        <p class="card-text mb-0"><?php echo htmlspecialchars($precio['tipo_prod']); ?></p>
                                                        <p class="card-text mb-0">$<?php echo number_format($precio['precio_tipo'], 0, '', ','); ?></p>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                                            <button type="button" class="btn btn-secondary btn-minus"><i class="fas fa-minus" style="color: white;"></i></button>
                                                            <input type="number" style="color: #fff !important;background:#000 !important" class="form-control quantity-input" data-id="<?php echo $producto['id_pro']; ?>" data-product-name="<?php echo htmlspecialchars($producto['nombre']); ?>" data-prefix="<?php echo $producto['prefijo']; ?>" data-product-type="<?php echo htmlspecialchars($precio['tipo_prod']); ?>" data-price="<?php echo $precio['precio_tipo']; ?>" value="0" min="0" readonly>
                                                            <button type="button" class="btn btn-secondary btn-plus"><i class="fas fa-plus" style="color: white;"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </div>
</div>



<!-- Botones flotantes y modales -->
<?php if (count($pedidosPendientes) > 0): ?>


<?php if ($pedido['estado'] === 'nuevo'): ?>
        <button class="btn btn-warning floating-button" >cuando entreguemos tu pedido podrás seleccioanr productos</button>
        <?php elseif ($pedido['estado'] === 'en_cocina'): ?>
         <button class="btn btn-warning floating-button" >cuando entreguemos tu pedido podrás seleccioanr productos</button>
    <?php elseif ($pedido['estado'] === 'entregado'): ?>
    <button id="selectProductsButton" class="btn btn-secondary floating-button" style="background-color: #000;font-size:10pt;color: white;">Seleccionar productos</button>
<button id="makeOrderButton" class="btn btn-primary floating-button" style="display:none;color: white;">Hacer pedido</button>
<?php endif; ?>





<?php else: ?>
<button id="selectProductsButton" class="btn btn-secondary floating-button" style="background-color: #000;font-size:10pt;color: white;">Seleccionar productos</button>
<button id="makeOrderButton" class="btn btn-primary floating-button" style="display:none;color: white;">Hacer pedido</button>
        
    <?php endif; ?>

<div class="modal fade" id="descriptionModal" tabindex="-1" role="dialog" aria-labelledby="descriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background: #00000085;border: 2px solid #5d520673;">
            <div class="modal-header">
                <h5 class="modal-title" style="color:white" id="descriptionModalLabel">Descripción del Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img id="product-image-modal" src="" alt="Imagen del producto" class="img-fluid mb-3">
                <p id="product-description"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>



<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.2.2/lazysizes.min.js" async></script>
<script>
$(document).ready(function() {
    
 
    
    // Función para actualizar el estado de los botones
    function updateButtons() {
        const selectedProducts = $('.quantity-input').filter(function() {
            return $(this).val() > 0;
        });
        if (selectedProducts.length > 0) {
            $('#selectProductsButton').hide();
            $('#makeOrderButton').show();
        } else {
            $('#selectProductsButton').show();
            $('#makeOrderButton').hide();
        }
    }

    // Función para aumentar la cantidad del producto
    function aumentarCantidad($input, $checkbox, $productSelected) {
        $input.val(parseInt($input.val()) + 1);
        if ($input.val() > 0) {
            $checkbox.prop('checked', true);
            $productSelected.addClass('show');
        }
        updateButtons();
    }

    // Cargar datos desde localStorage
    const customerName = localStorage.getItem('customerName') || '';
    const customerPhone = localStorage.getItem('customerPhone') || '';
    const customerAddress = localStorage.getItem('customerAddress') || '';
    const customerBarrio = localStorage.getItem('customerBarrio') || '';
    const customerEmail = localStorage.getItem('customerEmail') || '';
    const customerId = localStorage.getItem('customerId') || '';

    // Rellenar formulario con datos desde localStorage
    if (customerName) $('#customerName').val(customerName);
    if (customerPhone) $('#customerPhone').val(customerPhone);
    if (customerAddress) $('#customerAddress').val(customerAddress);
    if (customerBarrio) $('#customerBarrio').val(customerBarrio);
    if (customerEmail && customerEmail !== 'sincorreo') $('#customerEmail').val(customerEmail);
    if (customerId && customerId !== '0') $('#customerId').val(customerId);
    if (customerEmail || customerId) {
        $('#electronicInvoice').prop('checked', false);
    }

    // Mostrar/ocultar campos de factura electrónica
    $('#electronicInvoice').change(function() {
        if ($(this).is(':checked')) {
            $('#invoiceDetails').show();
        } else {
            $('#invoiceDetails').hide();
        }
    });

    // Reducir cantidad al hacer clic en el botón de -
    $('.btn-minus').click(function() {
        var $input = $(this).siblings('.quantity-input');
        var value = parseInt($input.val());
        if (value > 0) {
            $input.val(value - 1);
        }
        var $checkbox = $(this).closest('.card-body').find('.product-checkbox');
        var $productSelected = $(this).closest('.card').find('.product-selected');
        if ($input.val() == 0) {
            $checkbox.prop('checked', false);
            $productSelected.removeClass('show');
        }
        updateButtons();
    });

    // Aumentar cantidad al hacer clic en el botón de +
    $('.btn-plus').click(function() {
        var $input = $(this).siblings('.quantity-input');
        var $checkbox = $(this).closest('.card-body').find('.product-checkbox');
        var $productSelected = $(this).closest('.card').find('.product-selected');
        aumentarCantidad($input, $checkbox, $productSelected);
    });

    // Aumentar cantidad al hacer clic en la imagen del producto
    $('.product-image').click(function() {
        var $cardBody = $(this).closest('.card-body');
        var $input = $cardBody.find('.quantity-input');
        var $checkbox = $cardBody.find('.product-checkbox');
        var $productSelected = $(this).closest('.card').find('.product-selected');
        aumentarCantidad($input, $checkbox, $productSelected);
    });

    // Inicializar botones
    updateButtons();

    $('#makeOrderButton').click(function() {
        const selectedProducts = [];
        $('.quantity-input').each(function() {
            const quantity = $(this).val();
            if (quantity > 0) {
                const productId = $(this).data('id');
                const productName = $(this).data('product-name');
                const productPrice = $(this).data('price');
                const productType = $(this).data('product-type');
                const escapedProductType = escapeSelector(productType);

                // Obtener el prefijo del producto desde el elemento más cercano con la clase 'product-card'
                const productCard = $(this).closest('.product-card');
                const productPrefix = productCard.data('prefix'); // Asegúrate de que esto sea correcto

                console.log("Producto ID:", productId);
                console.log("Prefijo del Producto:", productPrefix); // Verifica que el prefijo no sea nulo
                console.log("Producto completo:", productCard); // Verifica el producto card seleccionado

                const productOption = $(`input[name=option-${productId}-${escapedProductType}]:checked`).val() || null;
                const productSubOption = $(`input[name=suboption-${productId}-${escapedProductType}]:checked`).val() || null;
                
                selectedProducts.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    type: productType,
                    quantity: quantity,
                    prefix: productPrefix, // Añadir prefijo al objeto
                    option: productOption,
                    suboption: productSubOption
                });
            }
        });
        console.log("Productos seleccionados con prefijos:", selectedProducts); 

        let productListHtml = '';
        selectedProducts.forEach(product => {
            productListHtml += `
                <div class="form-group" style="background: #4b4b4b;padding: 2%;border-radius: 10px;">
                    <label style="color:#fff">${product.name} - ${product.type} (Cantidad: ${product.quantity})</label>
                    ${[10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24].includes(product.id) ? `
                        <div class="form-check">
                            <input class="form-check-input option-radio" type="radio" name="option-${product.id}-${product.type}" id="option-${product.id}-${product.type}-arroz" value="arroz" required>
                            <label class="form-check-label" for="option-${product.id}-${product.type}-arroz">Arroz</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input option-radio" type="radio" name="option-${product.id}-${product.type}" id="option-${product.id}-${product.type}-papa" value="papa" required>
                            <label class="form-check-label" for="option-${product.id}-${product.type}-papa">Papa</label>
                        </div>
                        <div id="suboptions-${product.id}-${product.type}" class="suboptions" style="display:none;">
                            <div class="form-check">
                                <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-amarillo" value="amarillo">
                                <label class="form-check-label" for="suboption-${product.id}-${product.type}-amarillo">Amarillo</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-cafe" value="cafe">
                                <label class="form-check-label" for="suboption-${product.id}-${product.type}-cafe">Café</label>
                            </div>
                        </div>
                    ` : ''}
                    ${[1, 2, 3, 4, 5, 6, 7, 8, 9, 34, 25, 29, 30, 31, 32, 35, 42].includes(product.id) ? `
                        <div class="form-check">
                            <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-amarillo" value="amarillo" required>
                            <label class="form-check-label" for="suboption-${product.id}-${product.type}-amarillo">Amarillo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-cafe" value="cafe" required>
                            <label class="form-check-label" for="suboption-${product.id}-${product.type}-cafe">Café</label>
                        </div>
                    ` : ''}
                </div>
            `;
        });

        $('#selectedProductsContainer').html(productListHtml);
        $('#orderFormModal').modal('show');

        $('input[type=radio][name^=option-]').change(function() {
            const id = $(this).attr('name').split('-')[1];
            const type = $(this).attr('name').split('-')[2];
            if (this.value === 'arroz') {
                $(`#suboptions-${id}-${type}`).show();
                $(`#suboptions-${id}-${type} .suboption-radio`).attr('required', true);
            } else {
                $(`#suboptions-${id}-${type}`).hide();
                $(`#suboptions-${id}-${type} .suboption-radio`).removeAttr('required');
                $(`#suboptions-${id}-${type} .suboption-radio`).prop('checked', false);
            }
        });
    });

    function escapeSelector(selector) {
        return selector.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
    }

$('#orderForm').submit(function(e) {
    e.preventDefault();

    // Deshabilitar el botón de enviar pedido para evitar envíos duplicados
    const submitButton = $(this).find('button[type="submit"]');
    submitButton.prop('disabled', true).text('Enviando...');

    // Obtener el valor de tipo_solicitud desde el input hidden
    const tipoSolicitud = $('#tipo_solicitud').val();
    const customerName = $('#customerName').val();
    const customerPhone = $('#customerPhone').val();
    const customerAddress = $('#customerAddress').val();
    const customerBarrio = $('#customerBarrio').val();
    const customerEmail = $('#customerEmail').val() || 'sincorreo';
    const customerId = $('#customerId').val() || '0';
    const comments = $('#comments').val();

    // Obtener productos seleccionados
    const selectedProducts = [];
    $('.quantity-input').each(function() {
        const quantity = $(this).val();
        if (quantity > 0) {
            const productId = $(this).data('id');
            const productName = $(this).data('product-name');
            const productPrice = $(this).data('price');
            const productType = $(this).data('product-type');
            const escapedProductType = escapeSelector(productType);
            const productCard = $(this).closest('.product-card');
            const productPrefix = productCard.data('prefix');

            const productOption = $(`input[name=option-${productId}-${escapedProductType}]:checked`).val() || null;
            const productSubOption = $(`input[name=suboption-${productId}-${escapedProductType}]:checked`).val() || null;

            selectedProducts.push({
                id: productId,
                price: productPrice,
                type: productType,
                quantity: quantity,
                prefix: productPrefix,
                option: productOption,
                suboption: productSubOption
            });
        }
    });

    // Guardar datos en la base de datos
    $.post('guardar_pedido.php', {
        name: customerName,
        phone: customerPhone,
        address: customerAddress,
        barrio: customerBarrio,
        email: customerEmail,
        id: customerId,
        products: selectedProducts,
        tipo_solicitud: $('#tipo_solicitud').val(),
        comments: comments
    }).done(function(response) {
        console.log("Respuesta del servidor:", response);
        const res = JSON.parse(response);
        if (res.status === 'success') {
            // Limpiar los inputs
            $('.quantity-input').val(0);
            $('.product-checkbox').prop('checked', false);
            $('.product-selected').removeClass('show');

            // Cerrar modal del formulario
            $('#orderFormModal').modal('hide');

            // Mostrar modal de "Pedido enviado"
            $('#orderSentModal').modal('show');
            $('#orderNumber').text(res.order_number); // Número de pedido
            $('#turnoNumber').text(res.turno); // Número de turno

            // Recargar la página para simular un F5
            setTimeout(function() {
                window.location.reload(); // Recargar la página después de un pequeño retraso
            }, 2000); // Recarga después de 2 segundos (puedes ajustar este valor)
        } else {
            console.error(res.message);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error("Error en la solicitud AJAX:", textStatus, errorThrown);

        // Volver a habilitar el botón en caso de fallo
        submitButton.prop('disabled', false).text('Enviar pedido');
    }).always(function() {
        // Esto solo se ejecuta si el pedido se envía correctamente, así que no hace falta volver a habilitar el botón aquí.
    });
});




    // Ver detalles del producto
    $('.btn-details').click(function() {
        const description = $(this).data('description');
        const image = $(this).data('image');
        $('#product-description').text(description);
        $('#product-image-modal').attr('src', image);
        $('#descriptionModal').modal('show');
    });

    // Filtrar productos por categoría
$('.filter-btn').click(function() {
    var category = $(this).data('category');
    console.log("Category clicked: " + category); 
    if (category === 'all') {
        $('.product-card').show();
    } else {
        $('.product-card').hide();
        $(`.product-card[data-category='${category}']`).show();
    }
});

$('#filterCarousel .carousel-item .filter-btn').click(function() {
    var category = $(this).data('category');
    console.log("Category clicked: " + category); 
    if (category === 'all') {
        $('.product-card').show();
    } else {
        $('.product-card').hide();
        $(`.product-card[data-category='${category}']`).show();
    }
});

// Filtrar productos por búsqueda
$('#productSearch').on('keyup', function() {
    var searchText = $(this).val().toLowerCase();
    
    if (searchText === '') {
        $('.product-card').show();
    } else {
        $('.product-card').hide();
        
        $('.product-card').filter(function() {
            var productName = $(this).find('label').text().toLowerCase(); // Cambié el selector
            return productName.includes(searchText);
        }).show();
    }
});





    var $button = $('.btn-plus');
    $button.addClass('grow-shrink-animation');
    setTimeout(function() {
        $button.removeClass('grow-shrink-animation');
    }, 10000); 
});

</script>
</body>
</html>
