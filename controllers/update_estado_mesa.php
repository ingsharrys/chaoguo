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

if (isset($data->numero_mesa)) {
  //  $query = "UPDATE mesas SET estado = '', SET id_pedido = ''  WHERE numero_mesa = :numero_mesa";
    
    $query = "UPDATE mesas SET estado = '', id_pedido = '' WHERE numero_mesa = :numero_mesa";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':numero_mesa', $data->numero_mesa);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array("message" => "Estado de mesa actualizado."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "No se pudo actualizar el estado de la mesa."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Datos incompletos."));
}
?>
