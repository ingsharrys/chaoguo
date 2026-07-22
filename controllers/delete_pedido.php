<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Verificar que los parámetros necesarios estén presentes
if (
    !empty($data->numero_pedido) &&
    !empty($data->id_pro) &&
    !empty($data->tipo_prod)
) {
    $query = "DELETE FROM pedidos WHERE numero_pedido = :numero_pedido AND id_pro = :id_pro AND tipo_prod = :tipo_prod";
    $stmt = $db->prepare($query);

    // Vincular los parámetros
    $stmt->bindParam(':numero_pedido', $data->numero_pedido);
    $stmt->bindParam(':id_pro', $data->id_pro);
    $stmt->bindParam(':tipo_prod', $data->tipo_prod);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array("message" => "Producto eliminado correctamente."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "No se pudo eliminar el producto."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Datos incompletos. No se pudo eliminar el producto."));
}
?>
