<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $numero_pedido = isset($input['numero_pedido']) ? (int)$input['numero_pedido'] : null;

    if (!$numero_pedido) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Falta el parámetro "numero_pedido".'
        ]);
        exit;
    }

    $db = (new Database())->getConnection();

    $sql = "
        SELECT 
            ped.id_pro,
            ped.cantidad,
            ped.numero_pedido,
            ped.tipo_solicitud,
            ped.detalle,
            ped.tipo_producto,
            ped.mesa,
            ped.mesero,
            prod.nombre AS nombre_producto,
            prod.prefijo,
            prod.descript,
            IFNULL(pre.precio, 0) AS precio_tipo
        FROM pedidos ped
        LEFT JOIN productos prod ON ped.id_pro = prod.id_pro
        LEFT JOIN precios pre ON prod.id_pro = pre.idproduc AND ped.tipo_producto = pre.tipo_prod
        WHERE ped.numero_pedido = :numero_pedido
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
    $stmt->execute();

    $rawProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reorganizar productos al formato que espera el frontend
    $pedido = [];

    foreach ($rawProductos as $row) {
        $pedido[] = [
            'id_pro'        => $row['id_pro'],
            'nombre'        => $row['nombre_producto'],
            'descript'      => $row['descript'],
            'prefijo'       => $row['prefijo'],
            'tipo_prod'     => $row['tipo_producto'],
            'precio_tipo'   => floatval($row['precio_tipo']),
            'cantidad'      => (int) $row['cantidad'],
            'detalle'       => $row['detalle'],
            'numero_pedido' => $row['numero_pedido'],
            'mesero'        => $row['mesero']
        ];
    }

    echo json_encode([
        'success' => true,
        'pedido' => $pedido
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error al consultar el pedido.',
        'error' => $e->getMessage()
    ]);
}
