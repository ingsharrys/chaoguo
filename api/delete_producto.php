<?php
// delete_producto.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id_pro) && !empty($data->numero_pedido) && !empty($data->tipo_prod)) {
    try {
        $stmt = $db->prepare("
            DELETE FROM pedidos 
            WHERE id_pro = :id_pro 
              AND numero_pedido = :numero_pedido 
              AND tipo_producto = :tipo_prod
        ");
        $stmt->execute([
            ':id_pro' => $data->id_pro,
            ':numero_pedido' => $data->numero_pedido,
            ':tipo_prod' => $data->tipo_prod
        ]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Producto eliminado correctamente."]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "error" => "Producto no encontrado."]);
        }
    } catch (PDOException $e) {
        http_response_code(503);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Datos incompletos."]);
}

?>
