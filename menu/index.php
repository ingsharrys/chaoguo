<?php
require_once __DIR__ . '/app/config/database.php';

// Cargar automáticamente las clases con Composer (si usas autoload). 
// Si no, inclúyelas manualmente.
require_once __DIR__ . '/vendor/autoload.php';

use App\Controllers\PedidoController;

// Podrías usar un router sencillo basado en la variable 'route' de $_GET
$route = $_GET['route'] ?? 'home';

switch ($route) {
    case 'pedidos':
        $controller = new PedidoController();
        $controller->index();
        break;

    case 'pedido-store':
        $controller = new PedidoController();
        $controller->store(); // <-- Método donde insertas el pedido y devuelves JSON
        break;

    default:
        echo "<h1>Bienvenido a mi aplicación</h1>";
        break;
}

