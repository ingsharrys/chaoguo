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
        /* Contexto del pedido existente: se copian tipo_solicitud, mesero,
           cliente y estados de las filas ya registradas para que la nueva
           fila sea visible en el panel admin igual que las demás. */
        $stmtCtx = $db->prepare("
            SELECT tipo_solicitud, mesero, id_cliente, estado, estado_boton
            FROM pedidos
            WHERE numero_pedido = :np
            ORDER BY id_pedido DESC
            LIMIT 1
        ");
        $stmtCtx->execute([':np' => (int) $data->numero_pedido]);
        $ctx = $stmtCtx->fetch(PDO::FETCH_ASSOC) ?: [];

        /* Nombre y prefijo del producto desde la BD (los necesita el admin
           para mostrar el detalle del pedido). */
        $stmtProd = $db->prepare("SELECT nombre, prefijo FROM productos WHERE id_pro = :id LIMIT 1");
        $stmtProd->execute([':id' => (int) $data->id_pro]);
        $prodInfo = $stmtProd->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = $db->prepare("
            INSERT INTO pedidos
            (id_cliente, id_pro, producto, prefijos, tipo_producto, cantidad,
             numero_pedido, tipo_solicitud, detalle, mesa, mesero,
             estado, estado_boton, fecha)
            VALUES
            (:id_cliente, :id_pro, :producto, :prefijos, :tipo_producto, :cantidad,
             :numero_pedido, :tipo_solicitud, :detalle, :mesa, :mesero,
             :estado, :estado_boton, NOW())
        ");
        $stmt->execute([
            ':id_cliente'     => $ctx['id_cliente'] ?? 1,
            ':id_pro'         => (int) $data->id_pro,
            ':producto'       => $prodInfo['nombre']  ?? ($data->nombre  ?? ''),
            ':prefijos'       => $prodInfo['prefijo'] ?? ($data->prefijo ?? ''),
            ':tipo_producto'  => $data->tipo_prod,
            ':cantidad'       => (int) $data->cantidad,
            ':numero_pedido'  => (int) $data->numero_pedido,
            ':tipo_solicitud' => $ctx['tipo_solicitud'] ?? 52,
            ':detalle'        => $data->detalle ?? 'Sin detalle',
            ':mesa'           => (int) $data->mesa,
            ':mesero'         => $ctx['mesero'] ?? null,
            ':estado'         => $ctx['estado'] ?? 'nuevo',
            ':estado_boton'   => $ctx['estado_boton'] ?? 'nuevo'
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
