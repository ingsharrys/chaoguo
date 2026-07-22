<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);

    if (
        empty($input['id_pro']) ||
        !isset($input['cantidad']) ||
        empty($input['numero_pedido'])
    ) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos: se requiere id_pro, cantidad y numero_pedido.'
        ]);
        exit;
    }

    $db = (new Database())->getConnection();

    /* Un mismo producto (id_pro) puede estar en el pedido con varios tipos
       (ej: Grande y Pequeño). Se filtra también por tipo_producto para no
       sobrescribir la cantidad de los demás tipos. */
    $sql = "UPDATE pedidos SET cantidad = :cantidad WHERE id_pro = :id_pro AND numero_pedido = :numero_pedido";
    if (!empty($input['tipo_prod'])) {
        $sql .= " AND tipo_producto = :tipo_prod";
    }
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':cantidad', $input['cantidad'], PDO::PARAM_INT);
    $stmt->bindValue(':id_pro', $input['id_pro'], PDO::PARAM_INT);
    $stmt->bindValue(':numero_pedido', $input['numero_pedido'], PDO::PARAM_STR);
    if (!empty($input['tipo_prod'])) {
        $stmt->bindValue(':tipo_prod', $input['tipo_prod'], PDO::PARAM_STR);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado correctamente.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el producto.',
            'error' => $stmt->errorInfo()
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Excepción atrapada.',
        'error' => $e->getMessage()
    ]);
}
