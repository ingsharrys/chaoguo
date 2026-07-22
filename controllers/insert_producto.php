<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (
    isset($data->id_pro) &&
    isset($data->tipo_prod) &&
    isset($data->cantidad) &&
    isset($data->numero_pedido) &&
    isset($data->nombre) &&
    isset($data->precio_tipo) &&
    isset($data->descript) &&
    isset($data->detalle) &&
    isset($data->mesa)
) {
    $query = "INSERT INTO pedidos (id_pro, tipo_prod, cantidad, numero_pedido, nombre, precio_tipo, descript, detalle, mesa)
              VALUES (:id_pro, :tipo_prod, :cantidad, :numero_pedido, :nombre, :precio_tipo, :descript, :detalle, :mesa)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_pro', $data->id_pro);
    $stmt->bindParam(':tipo_prod', $data->tipo_prod);
    $stmt->bindParam(':cantidad', $data->cantidad);
    $stmt->bindParam(':numero_pedido', $data->numero_pedido);
    $stmt->bindParam(':nombre', $data->nombre);
    $stmt->bindParam(':precio_tipo', $data->precio_tipo);
    $stmt->bindParam(':descript', $data->descript);
    $stmt->bindParam(':detalle', $data->detalle);
    $stmt->bindParam(':mesa', $data->mesa);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(array("message" => "Producto insertado."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "No se pudo insertar el producto."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Datos incompletos."));
}
