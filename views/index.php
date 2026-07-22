<?php
require_once '../config/database.php';

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Obtener productos de la base de datos
$query = "SELECT * FROM productos";
$stmt = $conn->prepare($query);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar el parámetro GET 'info'
$info = isset($_GET['info']) ? $_GET['info'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #000;
            color: #fff;
        }
        .card-body {
            flex: 1 1 auto;
            min-height: 1px;
            padding: 0;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
        }
        .quantity-controls button {
            width: 30px;
            height: 30px;
            font-size: 18px;
            background: #333;
            border: none;
            color: #fff;
            border-radius: 50%;
            margin: 0 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }
        .quantity-controls button:hover {
            background: #555;
        }
        .quantity-controls input {
            width: 50px;
            text-align: center;
            height: 30px;
            background: #000;
            color: #fff;
            border: 1px solid #fff;
            border-radius: 5px;
        }
        .product-image {
            height: 100px;
            object-fit: cover;
            width: 100%;
            position: relative;
        }
        .card {
            margin-bottom: 20px;
            background-color: #222;
        }
        .card-title,
        .card-text {
            color: #fff;
            font-size: 12pt;
            margin: 0;
            padding: 5px;
        }
        .form-check {
            position: relative;
            display: block;
            padding-left: 0;
        }
        .floating-button {
            position: fixed;
            bottom: 20px;
            right: 50%;
            transform: translateX(50%);
            z-index: 1000;
        }
        .product-checkbox {
            display: none;
        }
        .product-selected {
            position: absolute;
            top: 0px;
            right: 0px;
            background-color: green;
            color: #fff;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: transform 0.3s ease, opacity 0.3s ease;
            transform: scale(0);
            opacity: 0;
        }
        .product-selected.show {
            display: flex;
            transform: scale(1);
            opacity: 1;
        }
        .product-selected i {
            color: #fff;
            font-size: 16px;
        }
        .product-selected::after {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            color: #fff;
        }
        .price-overlay {
            position: absolute;
            bottom: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            text-align: center;
            padding: 5px;
            font-size: 18px;
        }
        .btn-details {
            display: block;
            margin: 0 auto;
            background: #000;
            font-size: 9pt;
            border-color: white;
        }
        .form-control {
            display: block;
            width: 100%;
            height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #fff !important;
            background-color: #fff0 !important;
            background-clip: padding-box !important;
            border: 0 !important;
        }
        .price-and-quantity {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0px;
        }
        .price {
            font-size: 18px;
            margin-right: 10px;
        }
        @media (max-width: 576px) {
            .quantity-controls button {
                width: 25px;
                height: 25px;
                font-size: 16px;
            }
            .quantity-controls input {
                width: 20px;
                height: 25px;
                font-size: 14px;
                padding: 0;
            }
            .price {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
<header class="d-flex justify-content-center py-3">
    <img src="https://rosticeria.datarie.info/path/to/images/logorosti.jpg" style="width: 150px;" alt="Logotipo" class="header-logo">
</header>
<div class="container">
    <div class="row">
        <?php foreach ($productos as $producto): ?>
        <div class="col-6 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="position-relative">
                        <img data-src="<?php echo 'https://rosticeria.datarie.info/path/to/images/' . htmlspecialchars($producto['img']); ?>" class="card-img-top product-image lazyload" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" data-toggle="modal" data-target="#descriptionModal" data-image="<?php echo 'https://rosticeria.datarie.info/path/to/images/' . htmlspecialchars($producto['img']); ?>" data-description="<?php echo htmlspecialchars($producto['descript']); ?>">
                    </div>
                    <div class="product-selected"></div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input product-checkbox" id="product-<?php echo htmlspecialchars($producto['id']); ?>" data-name="<?php echo htmlspecialchars($producto['nombre']); ?>" data-price="<?php echo htmlspecialchars($producto['precio']); ?>">
                        <label class="form-check-label" style="height: 46px;" for="product-<?php echo htmlspecialchars($producto['id']); ?>">
                            <h5 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                        </label>
                    </div>
                    <div class="price-and-quantity">
                        <div class="price">$<?php echo number_format($producto['precio'], 0, '', ','); ?></div>
                        <div class="quantity-controls">
                            <button type="button" class="btn btn-secondary btn-minus"><i class="fas fa-minus"></i></button>
                            <input type="number" class="form-control quantity-input" value="0" min="0" readonly>
                            <button type="button" class="btn btn-secondary btn-plus"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-info mt-2 btn-details" data-toggle="modal" data-target="#descriptionModal" data-description="<?php echo htmlspecialchars($producto['descript']); ?>" data-image="<?php echo 'https://rosticeria.datarie.info/path/to/images/' . htmlspecialchars($producto['img']); ?>">Ver detalles</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<button id="sendToWhatsapp" class="btn btn-success floating-button">Enviar pedido a WhatsApp</button>

<!-- Modal -->
<div class="modal fade" id="descriptionModal" tabindex="-1" role="dialog" aria-labelledby="descriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background: #00000085;border: 2px solid #5d520673;">
            <div class="modal-header">
                <h5 class="modal-title" id="descriptionModalLabel">Descripción del Producto</h5>
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
    $('.btn-plus').click(function() {
        var $input = $(this).siblings('.quantity-input');
        $input.val(parseInt($input.val()) + 1);

        var $checkbox = $(this).closest('.card-body').find('.product-checkbox');
        var $productSelected = $(this).closest('.card').find('.product-selected');
        if ($input.val() > 0) {
            $checkbox.prop('checked', true);
            $productSelected.addClass('show');
        }
    });

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
    });

    $('#sendToWhatsapp').click(function() {
        var selectedProducts = $('.product-checkbox:checked');
        var message = '';
        selectedProducts.each(function() {
            var $checkbox = $(this);
            var name = $checkbox.data('name');
            var $quantityInput = $checkbox.closest('.card-body').find('.quantity-input');
            var quantity = $quantityInput.val();
            if (quantity > 0) {
                message += `${quantity} ${name}\n`;
            }
        });

        if (selectedProducts.length > 0) {
            var whatsappUrl = 'https://wa.me/573023573840?text=' + encodeURIComponent(message);
            window.open(whatsappUrl, '_blank');
        } else {
            alert('Por favor, selecciona al menos un producto.');
        }
    });

    $('.btn-details, .product-image').click(function() {
        var description = $(this).data('description');
        var image = $(this).data('image');
        $('#product-description').text(description);
        $('#product-image-modal').attr('src', image);
    });
});
</script>
</body>
</html>
