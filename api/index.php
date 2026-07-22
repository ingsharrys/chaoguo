<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// Permitir solicitudes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obtener datos del cuerpo de la solicitud o de $_POST
$data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$action = $data['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'error' => 'No se recibió ninguna acción.']);
    exit;
}

switch ($action) {
    case 'get_mesas':
        include 'api.php';
        break;

    case 'get_datos_mesas':
        include 'api_datos.php';
        break;

    case 'get_estado_mesas':
        include 'get_estado_mesas.php';
        break;

    case 'reset_mesa':
        include 'update_estado_mesa.php';
        break;

    case 'get_productos':
        include 'get_productos.php';
        break;

    case 'get_meseros':
        include 'get_meseros.php';
        break;

    case 'get_pedido':
        include 'get_pedido.php';
        break;

    case 'post_pedido':
        include 'post_pedido.php';
        break;

    case 'update_pedido':
        include 'update_pedido_modal.php';
        break;

    case 'update_producto':
        include 'update_producto.php';
        break;

    case 'delete_producto':
        include 'delete_producto.php';
        break;
    
    case 'insert_producto':
        include 'insert_producto.php';
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
        break;
}
