<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas</title>
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
        .floating-button {
            position: fixed;
            bottom: 20px;
            right: 50%;
            transform: translateX(50%);
            z-index: 1000;
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
        .btn-choose {
            display: block;
            margin: 0 auto;
            background-color: #25D366;
            font-size: 9pt;
            border-color: #25D366;
            color: #fff;
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
        @media (max-width: 576px) {
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
        <div class="col-6 col-sm-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="position-relative">
                        <img data-src="https://rosticeria.datarie.info/path/to/images/reserva.jpg" class="card-img-top product-image lazyload" alt="Sin decoración">
                    </div>
                    <div class="price-and-quantity">
                        <div class="price">$10.000</div>
                    </div>
                    <h5 class="card-title">Sin decoración</h5>
                    <button type="button" class="btn btn-info mt-2 btn-details" data-toggle="modal" data-target="#descriptionModal" data-title="Sin decoración" data-description="El costo de reservación es remidible en la compra, solo si llegan durante los primeros 15 minutos de la hora de reservación incluyen una cortesía para el homenajeado">Ver Detalles</button>
                    <button type="button" class="btn btn-choose mt-2 btn-choose" data-title="Sin decoración">
                        <i class="fab fa-whatsapp"></i> Enviar a WhatsApp
                    </button>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="position-relative">
                        <img data-src="https://rosticeria.datarie.info/path/to/images/sencilla.jpg" class="card-img-top product-image lazyload" alt="Decoración Sencilla">
                    </div>
                    <div class="price-and-quantity">
                        <div class="price">$15.000</div>
                    </div>
                    <h5 class="card-title">Decoración Sencilla</h5>
                    <button type="button" class="btn btn-info mt-2 btn-details" data-toggle="modal" data-target="#descriptionModal" data-title="Decoración Sencilla" data-description="Centro de mesa alusivo al tipo de celebración, incluyen una cortesía para el homenajeado">Ver Detalles</button>
                    <button type="button" class="btn btn-choose mt-2 btn-choose" data-title="Decoración Sencilla">
                        <i class="fab fa-whatsapp"></i> Enviar a WhatsApp
                    </button>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="position-relative">
                        <img data-src="https://rosticeria.datarie.info/path/to/images/feston.jpg" class="card-img-top product-image lazyload" alt="Decoración Festón">
                    </div>
                    <div class="price-and-quantity">
                        <div class="price">$30.000</div>
                    </div>
                    <h5 class="card-title">Decoración Festón</h5>
                    <button type="button" class="btn btn-info mt-2 btn-details" data-toggle="modal" data-target="#descriptionModal" data-title="Decoración Festón" data-description="Centro de mesa con bomba alusivo al tipo de celebración y festón, incluyen una cortesía para el homenajeado">Ver Detalles</button>
                    <button type="button" class="btn btn-choose mt-2 btn-choose" data-title="Decoración Festón">
                        <i class="fab fa-whatsapp"></i> Enviar a WhatsApp
                    </button>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="position-relative">
                        <img data-src="https://rosticeria.datarie.info/path/to/images/rostideco.jpg" class="card-img-top product-image lazyload" alt="Rostidecoración">
                    </div>
                    <div class="price-and-quantity">
                        <div class="price">$60.000</div>
                    </div>
                    <h5 class="card-title">Rostidecoración</h5>
                    <button type="button" class="btn btn-info mt-2 btn-details" data-toggle="modal" data-target="#descriptionModal" data-title="Rostidecoración" data-description="Centro de mesa con bomba alusivo al tipo de celebración, festón y nube de bombas, incluyen una cortesía para el homenajeado">Ver Detalles</button>
                    <button type="button" class="btn btn-choose mt-2 btn-choose" data-title="Rostidecoración">
                        <i class="fab fa-whatsapp"></i> Enviar a WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar detalles de la reserva -->
<div class="modal fade" id="descriptionModal" tabindex="-1" role="dialog" aria-labelledby="descriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background: #00000085;border: 2px solid #5d520673;">
            <div class="modal-header">
                <h5 class="modal-title" id="descriptionModalLabel">Detalles de la Reserva</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img id="modal-image" src="" alt="Imagen de la reserva" class="img-fluid mb-3">
                <p id="modal-description"></p>
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
    let selectedTitle = '';

    $('.btn-details').click(function() {
        const title = $(this).data('title');
        const description = $(this).data('description');
        const image = $(this).siblings('.position-relative').find('img').data('src');

        $('#descriptionModalLabel').text(title);
        $('#modal-description').text(description);
        $('#modal-image').attr('src', image);
    });

    $('.btn-choose').click(function() {
        selectedTitle = $(this).data('title');
        const message = `${selectedTitle}`;
        const whatsappUrl = 'https://wa.me/573023573840?text=' + encodeURIComponent(message);
        window.open(whatsappUrl, '_blank');
    });
});
</script>
</body>
</html>
