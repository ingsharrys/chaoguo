<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Responder inmediatamente al preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once '../config/database.php';
//include_once '../objects/mesa.php';

$database = new Database();
$db = $database->getConnection();

// Consulta para obtener información de las mesas y verificar si el pedido está pagado
$query = "SELECT m.numero_mesa AS mesa, m.estado, m.id_pedido, 
                 (SELECT COUNT(*) FROM caja c WHERE c.id_pedidoc = m.id_pedido) as pagado
          FROM mesas m";
$stmt = $db->prepare($query);
$stmt->execute();

$num = $stmt->rowCount();

if($num > 0){
    $mesas_arr = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        extract($row);
        $mesa_item = array(
            "mesa" => $mesa,
            "estado" => $estado,
            "id_pedido" => $id_pedido,
            "pagado" => $pagado > 0 ? true : false  // Convertir el resultado a booleano
        );
        array_push($mesas_arr, $mesa_item);
    }
    echo json_encode($mesas_arr);
} else {
    echo json_encode(array());
}
?>
