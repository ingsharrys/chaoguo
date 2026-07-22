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
    // Consulta para obtener mesas, pedidos, meseros y comentarios
    $sql = "SELECT 
                mesas.idm, 
                mesas.numero_mesa, 
                pedidos.id_pedido, 
                pedidos.producto, 
                pedidos.prefijos, 
                pedidos.cantidad,
                pedidos.detalle,
                pedidos.tipo_producto,
                pedidos.estado, 
                meseros.nombre_mese,
                comentarios.comentario  
            FROM mesas
            LEFT JOIN pedidos ON mesas.id_pedido = pedidos.numero_pedido
            LEFT JOIN meseros ON pedidos.mesero = meseros.id_mese
            LEFT JOIN comentarios ON pedidos.numero_pedido = comentarios.id_pedido";  // Relacionar comentarios con pedidos

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $result = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[] = $row;
    }

    if (count($result) > 0) {
        echo json_encode($result);
    } else {
        echo json_encode(["message" => "No records found"]);
    }
} else {
    echo json_encode(["error" => "Failed to connect to the database"]);
    http_response_code(500);
}
?>
