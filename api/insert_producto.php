<?php
// insert_producto.php

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

if (
    !empty($data->id_pro) &&
    !empty($data->tipo_prod) &&
    isset($data->cantidad) &&
    !empty($data->numero_pedido) &&
    !empty($data->mesa)
) {
    try {
        $stmt = $db->prepare("
            INSERT INTO pedidos 
            (id_pro, tipo_producto, cantidad, numero_pedido, detalle, mesa, fecha)
            VALUES 
            (:id_pro, :tipo_producto, :cantidad, :numero_pedido, :detalle, :mesa, NOW())
        ");
        $stmt->execute([
            ':id_pro'        => (int) $data->id_pro,
            ':tipo_producto' => $data->tipo_prod,
            ':cantidad'      => (int) $data->cantidad,
            ':numero_pedido' => (int) $data->numero_pedido,
            ':detalle'       => $data->detalle ?? 'Sin detalle',
            ':mesa'          => (int) $data->mesa
        ]);

        if ($stmt->rowCount() > 0) {
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Producto insertado correctamente."]);
        } else {
            http_response_code(503);
            echo json_encode([
                "success" => false,
                "error" => "No se pudo insertar el producto.",
                "query" => $stmt->queryString,
                "params" => [
                    ':id_pro'        => (int) $data->id_pro,
                    ':tipo_producto' => $data->tipo_prod,
                    ':cantidad'      => (int) $data->cantidad,
                    ':numero_pedido' => (int) $data->numero_pedido,
                    ':detalle'       => $data->detalle ?? 'Sin detalle',
                    ':mesa'          => (int) $data->mesa
                ]
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(503);
        echo json_encode([
            "success" => false,
            "error"   => $e->getMessage(),
            "query"   => $stmt->queryString,
            "params"  => [
                'id_pro'        => (int) $data->id_pro,
                'tipo_producto' => $data->tipo_prod,
                'cantidad'      => (int) $data->cantidad,
                'numero_pedido' => (int) $data->numero_pedido,
                'detalle'       => $data->detalle ?? 'Sin detalle',
                'mesa'          => (int) $data->mesa
            ]
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Datos incompletos.",
        "received" => $data
    ]);
}
