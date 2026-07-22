<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    $sql = "SELECT 
        mesas.numero_mesa,
        meseros.nombre_mese,
        JSON_ARRAYAGG(JSON_OBJECT(
            'producto', pedidos.producto,
            'prefijo', pedidos.prefijos,
            'cantidad', pedidos.cantidad,
            'detalle', pedidos.detalle,
            'tipo_producto', pedidos.tipo_producto,
            'estado', pedidos.estado,
            'comentario', comentarios.comentario
        )) AS productos
    FROM mesas
    LEFT JOIN pedidos ON mesas.id_pedido = pedidos.numero_pedido
    LEFT JOIN meseros ON pedidos.mesero = meseros.id_mese
    LEFT JOIN comentarios ON pedidos.numero_pedido = comentarios.id_pedido
    GROUP BY mesas.numero_mesa";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['productos'] = json_decode($row['productos'], true);
        $result[] = $row;
    }

    // ✅ Siempre responder con un array
    echo json_encode($result);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to connect to the database"]);
}
