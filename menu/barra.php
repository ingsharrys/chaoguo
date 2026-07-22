<?php
require_once '../config/database.php';

// Obtener el valor de $pedido
$pedido = isset($_GET['pedido']) ? $_GET['pedido'] : null;

// Determinar el valor de tipo_solicitud basado en el valor de $pedido
$tipo_solicitud = ($pedido === 'qr') ? 51 : 50;

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

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
<body>
<header class="d-flex justify-content-center py-3">
    <img src="https://heiyubai.datarie.info/path/to/images/logo-Heiyubai.jpg" style="width: 140px;height:56px" alt="Logotipo" class="header-logo">
</header>
<div class="container mb-4">
    <input type="text" id="productSearch" class="form-control" placeholder="Buscar productos...">
    <!-- Carrusel de botones para móviles -->
    <div id="filterCarousel" class="carousel slide" data-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <div class="d-flex justify-content-start">
                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="1">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <title>ARROZ</title>
                            <path d="M176 56c0-13.3 10.7-24 24-24h16c13.3 0 24 10.7 24 24s-10.7 24-24 24H200c-13.3 0-24-10.7-24-24zm24 48h16c13.3 0 24 10.7 24 24s-10.7 24-24 24H200c-13.3 0-24-10.7-24-24s10.7-24 24-24zM56 176H72c13.3 0 24 10.7 24 24s-10.7 24-24 24H56c-13.3 0-24-10.7-24-24s10.7-24 24-24zM0 283.4C0 268.3 12.3 256 27.4 256H484.6c15.1 0 27.4 12.3 27.4 27.4c0 70.5-44.4 130.7-106.7 154.1L403.5 452c-2 16-15.6 28-31.8 28H140.2c-16.1 0-29.8-12-31.8-28l-1.8-14.4C44.4 414.1 0 353.9 0 283.4zM224 200c0-13.3 10.7-24 24-24h16c13.3 0 24 10.7 24 24s-10.7 24-24 24H248c-13.3 0-24-10.7-24-24zm-96 0c0-13.3 10.7-24 24-24h16c13.3 0 24 10.7 24 24s-10.7 24-24 24H152c-13.3 0-24-10.7-24-24zm-24-96h16c13.3 0 24 10.7 24 24s-10.7 24-24 24H104c-13.3 0-24-10.7-24-24s10.7-24 24-24zm216 96c0-13.3 10.7-24 24-24h16c13.3 0 24 10.7 24 24s-10.7 24-24 24H344c-13.3 0-24-10.7-24-24zm-24-96h16c13.3 0 24 10.7 24 24s-10.7 24-24 24H296c-13.3 0-24-10.7-24-24s10.7-24 24-24zm120 96c0-13.3 10.7-24 24-24h16c13.3 0 24 10.7 24 24s-10.7 24-24 24H440c-13.3 0-24-10.7-24-24zm-24-96h16c13.3 0 24 10.7 24 24s-10.7 24-24 24H392c-13.3 0-24-10.7-24-24s10.7-24 24-24zM296 32h16c13.3 0 24 10.7 24 24s-10.7 24-24 24H296c-13.3 0-24-10.7-24-24s10.7-24 24-24z"/>
                        </svg><br>
                        <span>ARROZ</span>
                    </button>

                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="2">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <title>CHOP SUEY</title>
                            <path d="M0 192c0-35.3 28.7-64 64-64c.5 0 1.1 0 1.6 0C73 91.5 105.3 64 144 64c15 0 29 4.1 40.9 11.2C198.2 49.6 225.1 32 256 32s57.8 17.6 71.1 43.2C339 68.1 353 64 368 64c38.7 0 71 27.5 78.4 64c.5 0 1.1 0 1.6 0c35.3 0 64 28.7 64 64c0 11.7-3.1 22.6-8.6 32H8.6C3.1 214.6 0 203.7 0 192zm0 91.4C0 268.3 12.3 256 27.4 256H484.6c15.1 0 27.4 12.3 27.4 27.4c0 70.5-44.4 130.7-106.7 154.1L403.5 452c-2 16-15.6 28-31.8 28H140.2c-16.1 0-29.8-12-31.8-28l-1.8-14.4C44.4 414.1 0 353.9 0 283.4z"/>
                        </svg><br>
                        <span>CHOP SUEY</span>
                    </button>
                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="3">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                            <title>ESPAGUETIS</title>
                            <path d="M208 64a16 16 0 1 0 -32 0 16 16 0 1 0 32 0zm48 0c0 16.2-6 31.1-16 42.3l15.6 31.2c18.7-6 39.9-9.5 64.4-9.5s45.8 3.5 64.4 9.5L400 106.3C390 95.1 384 80.2 384 64c0-35.3 28.7-64 64-64s64 28.7 64 64s-28.7 64-64 64c-1.7 0-3.4-.1-5.1-.2L427.8 158c21.1 13.6 37.7 30.2 51.4 46.4c7.1 8.3 13.5 16.6 19.3 24l1.4 1.8c6.3 8.1 11.6 14.8 16.7 20.4C527.3 262.3 532.7 264 536 264c2.5 0 4.3-.6 7.1-3.3c3.7-3.5 7.1-8.8 12.5-17.4l.6-.9c4.6-7.4 11-17.6 19.4-25.7c9.7-9.3 22.9-16.7 40.4-16.7c13.3 0 24 10.7 24 24s-10.7 24-24 24c-2.5 0-4.3 .6-7.1 3.3c-3.7 3.5-7.1 8.8-12.5 17.4l-.6 .9c-4.6 7.4-11 17.6-19.4 25.7c-9.7 9.3-22.9 16.7-40.4 16.7c-18.5 0-32.9-8.5-44.3-18.6c-3.1 4-6.6 8.3-10.5 12.7c1.4 4.3 2.8 8.5 4 12.5c.9 3 1.8 5.8 2.6 8.6c3 9.8 5.5 18.2 8.6 25.9c3.9 9.8 7.4 15.4 10.8 18.5c2.6 2.4 5.9 4.3 12.8 4.3c8.7 0 16.9-4.2 33.7-13.2c15-8 35.7-18.8 62.3-18.8c13.3 0 24 10.7 24 24s-10.7 24-24 24c-13.4 0-24.7 5.2-39.7 13.2c-1 .6-2.1 1.1-3.2 1.7C559.9 414 541.4 424 520 424c-18.4 0-33.6-6.1-45.5-17.2c-11.1-10.3-17.9-23.7-22.7-36c-3.6-9-6.7-19.1-9.5-28.5c-16.4 12.3-36.1 23.6-58.9 31.3c3.6 10.8 8.4 23.5 14.4 36.2c7.5 15.9 16.2 30.4 25.8 40.5C433 460.5 441.2 464 448 464c13.3 0 24 10.7 24 24s-10.7 24-24 24c-25.2 0-45-13.5-59.5-28.8c-14.5-15.4-25.7-34.9-34.2-53c-8-17-14.1-33.8-18.3-46.9c-5.2 .4-10.6 .6-16 .6s-10.8-.2-16-.6c-4.2 13-10.3 29.9-18.3 46.9c-8.5 18.1-19.8 37.6-34.2 53C237 498.5 217.2 512 192 512c-13.3 0-24-10.7-24-24s10.7-24 24-24c6.8 0 15-3.5 24.5-13.7c9.5-10.1 18.3-24.6 25.8-40.5c5.9-12.6 10.7-25.4 14.4-36.2c-22.8-7.7-42.5-19-58.9-31.3c-2.9 9.4-6 19.5-9.5 28.5c-4.8 12.2-11.6 25.6-22.7 36C153.6 417.9 138.4 424 120 424c-21.4 0-39.9-10-53.1-17.1l0 0c-1.1-.6-2.2-1.2-3.2-1.7c-15-8-26.3-13.2-39.7-13.2c-13.3 0-24-10.7-24-24s10.7-24 24-24c26.6 0 47.3 10.8 62.3 18.8c16.8 9 25 13.2 33.7 13.2c6.8 0 10.2-1.9 12.8-4.3c3.4-3.2 7-8.8 10.8-18.5c3-7.7 5.6-16.1 8.6-25.9c.8-2.7 1.7-5.6 2.6-8.6c1.2-4 2.6-8.2 4-12.5c-3.9-4.5-7.4-8.8-10.5-12.7C136.9 303.5 122.5 312 104 312c-17.5 0-30.7-7.4-40.4-16.7c-8.4-8.1-14.8-18.3-19.4-25.7l-.6-.9c-5.4-8.6-8.8-13.9-12.5-17.4c-2.8-2.7-4.6-3.3-7.1-3.3c-13.3 0-24-10.7-24-24s10.7-24 24-24c17.5 0 30.7 7.4 40.4 16.7c8.4 8.1 14.8 18.3 19.4 25.7l.6 .9c5.4 8.6 8.8 13.9 12.5 17.4c2.8 2.7 4.6 3.3 7.1 3.3c3.3 0 8.7-1.7 19.4-13.4c5.1-5.6 10.4-12.3 16.7-20.4l1.4-1.8c5.8-7.4 12.2-15.7 19.3-24c13.8-16.2 30.3-32.8 51.4-46.4l-15.1-30.2c-1.7 .1-3.4 .2-5.1 .2c-35.3 0-64-28.7-64-64s28.7-64 64-64s64 28.7 64 64zm208 0a16 16 0 1 0 -32 0 16 16 0 1 0 32 0z"/>
                        </svg><br>
                        <span>ESPAGUETIS</span>
                    </button>
                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="4">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <title>POLLO</title>
                            <path d="M160 265.2c0 8.5-3.4 16.6-9.4 22.6l-26.8 26.8c-12.3 12.3-32.5 11.4-49.4 7.2C69.8 320.6 65 320 60 320c-33.1 0-60 26.9-60 60s26.9 60 60 60c6.3 0 12 5.7 12 12c0 33.1 26.9 60 60 60s60-26.9 60-60c0-5-.6-9.8-1.8-14.5c-4.2-16.9-5.2-37.1 7.2-49.4l26.8-26.8c6-6 14.1-9.4 22.6-9.4H336c6.3 0 12.4-.3 18.5-1c11.9-1.2 16.4-15.5 10.8-26c-8.5-15.8-13.3-33.8-13.3-53c0-61.9 50.1-112 112-112c8 0 15.7 .8 23.2 2.4c11.7 2.5 24.1-5.9 22-17.6C494.5 62.5 422.5 0 336 0C238.8 0 160 78.8 160 176v89.2z"/>
                        </svg><br>
                        <span>POLLO</span>
                    </button>
                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="5">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <title>TORTILLAS</title>
                            <path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zM312.6 63.7c-6.2-6.2-16.4-6.2-22.6 0L256 97.6 222.1 63.7c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6l33.9 33.9-45.3 45.3-56.6-56.6c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6l56.6 56.6-45.3 45.3L86.3 199.4c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6L97.6 256 63.7 289.9c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0l33.9-33.9 45.3 45.3-56.6 56.6c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0l56.6-56.6 45.3 45.3-33.9 33.9c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L256 414.4l33.9 33.9c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6l-33.9-33.9 45.3-45.3 56.6 56.6c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6l-56.6-56.6 45.3-45.3 33.9 33.9c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6L414.4 256l33.9-33.9c6.2-6.2 6.2-16.4 0-22.6s-16.4-6.2-22.6 0l-33.9 33.9-45.3-45.3 56.6-56.6c6.2-6.2 6.2-16.4 0-22.6s-16.4-6.2-22.6 0l-56.6 56.6-45.3-45.3 33.9-33.9c6.2-6.2 6.2-16.4 0-22.6zM142.9 256l45.3-45.3L233.4 256l-45.3 45.3L142.9 256zm67.9 67.9L256 278.6l45.3 45.3L256 369.1l-45.3-45.3zM278.6 256l45.3-45.3L369.1 256l-45.3 45.3L278.6 256zm22.6-67.9L256 233.4l-45.3-45.3L256 142.9l45.3 45.3z"/>
                        </svg><br>
                        <span>TORTILLAS</span>
                    </button>
                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="6">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                            <title>ESPECIALES</title>
                            <path d="M416 0C400 0 288 32 288 176V288c0 35.3 28.7 64 64 64h32V480c0 17.7 14.3 32 32 32s32-14.3 32-32V352 240 32c0-17.7-14.3-32-32-32zM64 16C64 7.8 57.9 1 49.7 .1S34.2 4.6 32.4 12.5L2.1 148.8C.7 155.1 0 161.5 0 167.9c0 45.9 35.1 83.6 80 87.7V480c0 17.7 14.3 32 32 32s32-14.3 32-32V255.6c44.9-4.1 80-41.8 80-87.7c0-6.4-.7-12.8-2.1-19.1L191.6 12.5c-1.8-8-9.3-13.3-17.4-12.4S160 7.8 160 16V150.2c0 5.4-4.4 9.8-9.8 9.8c-5.1 0-9.3-3.9-9.8-9L127.9 14.6C127.2 6.3 120.3 0 112 0s-15.2 6.3-15.9 14.6L83.7 151c-.5 5.1-4.7 9-9.8 9c-5.4 0-9.8-4.4-9.8-9.8V16zm48.3 152l-.3 0-.3 0 .3-.7 .3 .7z"/>
                        </svg><br>
                        <span>ESPECIALES</span>
                    </button>
                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="7">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <title>CONSOMES</title>
                            <path d="M176 32c44.2 0 80 35.8 80 80v16c0 8.8-7.2 16-16 16c-44.2 0-80-35.8-80-80V48c0-8.8 7.2-16 16-16zM56 64h48c13.3 0 24 10.7 24 24s-10.7 24-24 24H56c-13.3 0-24-10.7-24-24s10.7-24 24-24zM24 136H136c13.3 0 24 10.7 24 24s-10.7 24-24 24H24c-13.3 0-24-10.7-24-24s10.7-24 24-24zm8 96c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24s-10.7 24-24 24H56c-13.3 0-24-10.7-24-24zM272 48c0-8.8 7.2-16 16-16c44.2 0 80 35.8 80 80v16c0 8.8-7.2 16-16 16c-44.2 0-80-35.8-80-80V48zM400 32c44.2 0 80 35.8 80 80v16c0 8.8-7.2 16-16 16c-44.2 0-80-35.8-80-80V48c0-8.8 7.2-16 16-16zm80 160v16c0 44.2-35.8 80-80 80c-8.8 0-16-7.2-16-16V256c0-44.2 35.8-80 80-80c8.8 0 16 7.2 16 16zM352 176c8.8 0 16 7.2 16 16v16c0 44.2-35.8 80-80 80c-8.8 0-16-7.2-16-16V256c0-44.2 35.8-80 80-80zm-96 16v16c0 44.2-35.8 80-80 80c-8.8 0-16-7.2-16-16V256c0-44.2 35.8-80 80-80c8.8 0 16 7.2 16 16zM3.5 347.6C1.6 332.9 13 320 27.8 320H484.2c14.8 0 26.2 12.9 24.4 27.6C502.3 397.8 464.2 437 416 446v2c0 17.7-14.3 32-32 32H128c-17.7 0-32-14.3-32-32v-2c-48.2-9-86.3-48.2-92.5-98.4z"/>
                        </svg><br>
                        <span>CONSOMES</span>
                    </button>
                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="10">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                            <title>BEBIDAS</title>
                            <path d="M120 0l80 0c13.3 0 24 10.7 24 24l0 40L96 64l0-40c0-13.3 10.7-24 24-24zM32 167.5c0-19.5 10-37.6 26.6-47.9l15.8-9.9C88.7 100.7 105.2 96 122.1 96l75.8 0c16.9 0 33.4 4.7 47.7 13.7l15.8 9.9C278 129.9 288 148 288 167.5c0 17-7.5 32.3-19.4 42.6C280.6 221.7 288 238 288 256c0 19.1-8.4 36.3-21.7 48c13.3 11.7 21.7 28.9 21.7 48s-8.4 36.3-21.7 48c13.3 11.7 21.7 28.9 21.7 48c0 35.3-28.7 64-64 64L96 512c-35.3 0-64-28.7-64-64c0-19.1 8.4-36.3 21.7-48C40.4 388.3 32 371.1 32 352s8.4-36.3 21.7-48C40.4 292.3 32 275.1 32 256c0-18 7.4-34.3 19.4-45.9C39.5 199.7 32 184.5 32 167.5zM96 240c0 8.8 7.2 16 16 16l96 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-96 0c-8.8 0-16 7.2-16 16zm16 112c-8.8 0-16 7.2-16 16s7.2 16 16 16l96 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-96 0z"/>
                        </svg><br>
                        <span>BEBIDAS</span>
                    </button>
                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="8">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <title>PLATOS PERSONALES</title>
                            <path d="M64 32C28.7 32 0 60.7 0 96s28.7 64 64 64h1c3.7 88.9 77 160 167 160h56V128H264 88.8 64c-17.7 0-32-14.3-32-32s14.3-32 32-32H464c8.8 0 16-7.2 16-16s-7.2-16-16-16H64zM224 456c0 13.3 10.7 24 24 24h72V407.8l-64.1-22.4c-12.5-4.4-26.2 2.2-30.6 14.7s2.2 26.2 14.7 30.6l4.5 1.6C233 433.9 224 443.9 224 456zm128 23.3c36.4-3.3 69.5-17.6 96.1-39.6l-86.5-34.6c-3 1.8-6.2 3.2-9.6 4.3v69.9zM472.6 415c24.6-30.3 39.4-68.9 39.4-111c0-12.3-1.3-24.3-3.7-35.9L382.8 355.1c.8 3.4 1.2 7 1.2 10.6c0 4.6-.7 9-1.9 13.1L472.6 415zM336 128H320V320h18.3c9.9 0 19.1 3.2 26.6 8.5l133.5-92.4C471.8 172.6 409.1 128 336 128zM168 192a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/>
                        </svg><br>
                        <span>PLATOS PERSONALES</span>
                    </button>
                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="9">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <title>PORCIONES</title>
                            <path d="M257.5 27.6c-.8-5.4-4.9-9.8-10.3-10.6v0c-22.1-3.1-44.6 .9-64.4 11.4l-74 39.5C89.1 78.4 73.2 94.9 63.4 115L26.7 190.6c-9.8 20.1-13 42.9-9.1 64.9l14.5 82.8c3.9 22.1 14.6 42.3 30.7 57.9l60.3 58.4c16.1 15.6 36.6 25.6 58.7 28.7l83 11.7c22.1 3.1 44.6-.9 64.4-11.4l74-39.5c19.7-10.5 35.6-27 45.4-47.2l36.7-75.5c9.8-20.1 13-42.9 9.1-64.9v0c-.9-5.3-5.3-9.3-10.6-10.1c-51.5-8.2-92.8-47.1-104.5-97.4c-1.8-7.6-8-13.4-15.7-14.6c-54.6-8.7-97.7-52-106.2-106.8zM208 144a32 32 0 1 1 0 64 32 32 0 1 1 0-64zM144 336a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm224-64a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
                        </svg><br>
                        <span>PORCIONES</span>
                    </button>
                    
                    <button class="btncategorias btn filter-btn mx-2 my-1" data-category="all">
                        <svg class="icono_categoria" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <title>TODO</title>
                            <path d="M315.4 15.5C309.7 5.9 299.2 0 288 0s-21.7 5.9-27.4 15.5l-96 160c-5.9 9.9-6.1 22.2-.4 32.2s16.3 16.2 27.8 16.2H384c11.5 0 22.2-6.2 27.8-16.2s5.5-22.3-.4-32.2l-96-160zM288 312V456c0 22.1 17.9 40 40 40H472c22.1 0 40-17.9 40-40V312c0-22.1-17.9-40-40-40H328c-22.1 0-40 17.9-40 40zM128 512a128 128 0 1 0 0-256 128 128 0 1 0 0 256z"/>
                        </svg><br>
                        <span>TODO</span>
                    </button>
                </div>
            </div>
        </div>
       


</div>
<?php if ($pedido != 'wp' && $pedido != 'fb'): ?>
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
                        <label class="form-check-label" style="color: #000;font-size: 12pt;margin-bottom: 3%;" for="product-<?php echo htmlspecialchars($producto['id_pro']); ?>">
                            <?php echo htmlspecialchars($producto['nombre']); ?>
                        </label>
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
                                                <button type="button" class="btn btn-secondary btn-minus"><i class="fas fa-minus"></i></button>
                                                <input type="number" style="color: #fff !important;background:#000 !important" class="form-control quantity-input" data-id="<?php echo $producto['id_pro']; ?>" data-product-name="<?php echo htmlspecialchars($producto['nombre']); ?>" data-prefix="<?php echo $producto['prefijo']; ?>" data-product-type="<?php echo htmlspecialchars($precio['tipo_prod']); ?>" data-price="<?php echo $precio['precio_tipo']; ?>" value="0" min="0" readonly>
                                                <button type="button" class="btn btn-secondary btn-plus"><i class="fas fa-plus"></i></button>
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

<!-- Botón para seleccionar productos -->
<button id="selectProductsButton" class="btn btn-secondary floating-button" style="background-color: #500;font-size:10pt">Seleccionar productos</button>

<!-- Botón para abrir el formulario -->
<button id="makeOrderButton" class="btn btn-primary floating-button" style="display:none;">Hacer pedido</button>

<!-- Modal -->
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

<!-- Modal del formulario -->
<div class="modal fade" id="orderFormModal" tabindex="-1" role="dialog" aria-labelledby="orderFormModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background: #000000de; border: 2px solid #5d520673;">
            <div class="modal-header">
                <h5 class="modal-title" style="color:white" id="orderFormModalLabel">Detalles del pedido</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="orderForm">
                    <input type="hidden" id="tipo_solicitud" name="tipo_solicitud" value="<?php echo $tipo_solicitud; ?>">
                    <div class="form-group">
                        <label for="customerName" style="color:#fff">Nombre</label>
                        <input type="text" class="form-control" id="customerName" required>
                    </div>
                    <div class="form-group">
                        <label for="customerPhone" style="color:#fff">Teléfono</label>
                        <input type="tel" class="form-control" id="customerPhone" required>
                    </div>
                    <?php if ($tipo_solicitud != 51): ?>
                    <div class="form-group">
                        <label for="customerAddress" style="color:#fff">Dirección</label>
                        <input type="text" class="form-control" id="customerAddress" required>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="orderComments" style="color:#fff">Comentarios del pedido</label>
                         <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="electronicInvoice">
                        <label class="form-check-label" for="electronicInvoice" style="color:#fff">¿Desea factura electrónica?</label>
                    </div>
                    <div id="invoiceDetails" style="display: none;">
                        <div class="form-group">
                            <label for="customerEmail" style="color:#fff">Email</label>
                            <input type="email" class="form-control" id="customerEmail">
                        </div>
                        <div class="form-group">
                            <label for="customerId" style="color:#fff">Número de cédula</label>
                            <input type="text" class="form-control" id="customerId">
                        </div>
                    </div>
                    <div id="selectedProductsContainer"></div>
                    <button type="submit" class="btn btn-primary">Enviar pedido</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Modal para "Pedido enviado" -->
<div class="modal fade" id="orderSentModal" tabindex="-1" role="dialog" aria-labelledby="orderSentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background: #000000de; border: 2px solid #5d520673;">
            <div class="modal-header">
                <h5 class="modal-title" style="color: white;" id="orderSentModalLabel">Pedido enviado</h5>
            </div>
            <div class="modal-body">
                <p style="color: white;">Tu pedido ha sido registrado</p>
                <p style="color: white;">Número de Pedido: <span id="orderNumber"></span></p>
                <p style="color: white;">Número de Turno: <span id="turnoNumber"></span></p> <!-- Contenedor para el número de turno -->
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
            selectedProducts.push({
                id: productId,
                name: productName,
                price: productPrice,
                type: productType,
                quantity: quantity
            });
        }
    });

    let productListHtml = '';
    selectedProducts.forEach(product => {
        productListHtml += `
            <div class="form-group" style="background: #4b4b4b;padding: 2%;border-radius: 10px;">
                <label style="color:#fff">${product.name} - ${product.type} (Cantidad: ${product.quantity})</label>
                ${[10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 42].includes(product.id) ? `
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
                ${[1, 2, 3, 4, 5, 6, 7, 8, 9, 34, 25, 29, 30, 31, 32, 42].includes(product.id) ? `
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
            // Requerir subopciones solo si se selecciona "arroz"
            $(`#suboptions-${id}-${type} .suboption-radio`).attr('required', true);
        } else {
            $(`#suboptions-${id}-${type}`).hide();
            // Eliminar el atributo required de las subopciones si se selecciona "papa"
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
    
    // Obtener el valor de tipo_solicitud desde el input hidden
    const tipoSolicitud = $('#tipo_solicitud').val();

    const customerName = $('#customerName').val();
    const customerPhone = $('#customerPhone').val();
    const customerAddress = $('#customerAddress').val();
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
            const productOption = $(`input[name=option-${productId}-${escapedProductType}]:checked`).val() || null;
            const productSubOption = $(`input[name=suboption-${productId}-${escapedProductType}]:checked`).val() || null;
            selectedProducts.push({
                id: productId,
                name: productName,
                price: productPrice,
                type: productType,
                quantity: quantity,
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
        email: customerEmail,
        id: customerId,
        products: selectedProducts,
        tipo_solicitud: tipoSolicitud,
        comments: comments
    }, function(response) {
        console.log(response);
        const res = JSON.parse(response);
        if (res.status === 'success') {
            // Restablecer los valores de cantidad de los productos
            $('.quantity-input').val(0);
            $('.product-checkbox').prop('checked', false);
            $('.product-selected').removeClass('show');

            // Esconde el modal del formulario
            $('#orderFormModal').modal('hide');

            // Muestra el modal "Pedido enviado"
            $('#orderSentModal').modal('show');

            // Insertar el número de pedido en el modal
            const orderNumber = res.order_number;
            const turno = res.turno;  // Aquí recibimos el turno desde el backend
            $('#orderNumber').text(orderNumber);  // Actualizar el número de pedido
            $('#turnoNumber').text(turno);  // Actualizar el número de turno con el turno recibido
        } else {
            console.error(res.message);
        }
        updateButtons(); // Actualizar botones después de enviar el pedido
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
        console.log("Category clicked: " + category); // Añadir console.log para depuración
        if (category === 'all') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $(`.product-card[data-category='${category}']`).show();
        }
    });

    $('#filterCarousel .carousel-item .filter-btn').click(function() {
        var category = $(this).data('category');
        console.log("Category clicked: " + category); // Añadir console.log para depuración
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
    console.log("Searching for: " + searchText);
    
    if (searchText === '') {
        // Mostrar todas las tarjetas si no hay texto de búsqueda
        $('.product-card').show();
    } else {
        // Ocultar todas las tarjetas primero
        $('.product-card').hide();
        
        // Mostrar las tarjetas que coinciden con la búsqueda
        $('.product-card').filter(function() {
            var productName = $(this).find('.card-title label').text().toLowerCase();
            return productName.includes(searchText); // Evaluar si el nombre coincide con el texto de búsqueda
        }).show(); // Mostrar la tarjeta completa si coincide
    }
});


    var $button = $('.btn-plus');
    // Añade la clase de animación al cargar la página
    $button.addClass('grow-shrink-animation');
    // Elimina la clase después de 10 segundos
    setTimeout(function() {
        $button.removeClass('grow-shrink-animation');
    }, 10000); // 10000 milisegundos = 10 segundos
});

</script>
</body>
</html>
