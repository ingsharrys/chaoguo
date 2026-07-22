<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$tipo_solicitud = isset($_GET['tipo_solicitud']) ? intval($_GET['tipo_solicitud']) : 0;
$since = isset($_GET['since']) ? intval($_GET['since']) : 0;

if ($tipo_solicitud === 0) {
    echo json_encode(array("error" => "Tipo de solicitud no proporcionado"));
    exit();
}

date_default_timezone_set('America/Bogota');
$fecha_actual = date('Y-m-d');

// Convertir timestamp JS (milisegundos) a formato MySQL
$since_mysql = $since > 0 ? date('Y-m-d H:i:s', $since / 1000) : null;

$query = "
    SELECT t.id_t, t.id_pedido, t.turno, t.fecha, t.tipo_solicitud, t.estado, 
           t.updated_at,
           c.cliente, c.celular, c.direccion, c.barrio, 
           (SELECT COUNT(*) FROM caja WHERE id_pedidoc = t.id_pedido) AS pagado, 
           (SELECT COUNT(*) FROM domicilios WHERE id_pedido = t.id_pedido AND id_domi IS NOT NULL) AS tiene_domiciliario, 
           (SELECT COUNT(*) FROM domicilios WHERE id_pedido = t.id_pedido) AS tiene_precio
    FROM turnero t
    LEFT JOIN clientes c ON t.id_cliente = c.id
    WHERE t.tipo_solicitud = :tipo_solicitud
    AND DATE(t.fecha) = :fecha_actual";

if ($since_mysql) {
    $query .= " AND t.updated_at > :since";
}

$stmt = $db->prepare($query);
$stmt->bindParam(':tipo_solicitud', $tipo_solicitud, PDO::PARAM_INT);
$stmt->bindParam(':fecha_actual', $fecha_actual, PDO::PARAM_STR);
if ($since_mysql) {
    $stmt->bindParam(':since', $since_mysql, PDO::PARAM_STR);
}
$stmt->execute();

$turnos_arr = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $turnos_arr[] = [
        "id_t" => $row['id_t'],
        "numero_pedido" => $row['id_pedido'],
        "turno" => $row['turno'],
        "fecha" => $row['fecha'],
        "tipo_solicitud" => $row['tipo_solicitud'],
        "estado" => $row['estado'],
        "pagado" => $row['pagado'] > 0,
        "tiene_domiciliario" => $row['tiene_domiciliario'] > 0,
        "tiene_precio" => $row['tiene_precio'] > 0,
        "cliente" => $row['cliente'],
        "direccion" => $row['direccion'] ?? '',
        "barrio" => $row['barrio'] ?? '',
        "telefono" => $row['celular'],
        "updated_at" => $row['updated_at']
    ];
}

echo json_encode(["turnos" => $turnos_arr]);
?>
