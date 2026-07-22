<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    isset($data->cantidad) &&
    isset($data->numero_pedido)
) {
    $query = "UPDATE pedidos SET cantidad = :cantidad WHERE id_pro = :id_pro AND numero_pedido = :numero_pedido";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cantidad', $data->cantidad);
    $stmt->bindParam(':id_pro', $data->id_pro);
    $stmt->bindParam(':numero_pedido', $data->numero_pedido);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array("message" => "Producto actualizado exitosamente."));
    } else {
        http_response_code(503);
        $errorInfo = $stmt->errorInfo();
        echo json_encode(array("message" => "No se pudo actualizar el producto.", "error" => $errorInfo));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Datos incompletos."));
}
