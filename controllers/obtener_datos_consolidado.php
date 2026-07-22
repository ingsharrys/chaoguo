<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Obtener la fecha desde la petición GET
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : null;

if (!$fecha) {
    // Si no se proporciona la fecha, devolver un error
    echo json_encode(array("error" => "Fecha no proporcionada."));
    exit();
}

// Log de la fecha recibida para depuración
error_log("Fecha recibida: " . $fecha);

// Consultar datos de la tabla caja, turnero y clientes
$query = "
    SELECT caja.id_pedidoc, caja.fecha_caja, caja.m_pago,
           t.turno, t.estado, t.id_cliente,
           c.cliente
    FROM caja
    JOIN turnero t ON caja.id_pedidoc = t.id_pedido
    JOIN clientes c ON t.id_cliente = c.id
    WHERE DATE(caja.fecha_caja) = :fecha
    GROUP BY caja.id_pedidoc
";

$stmt = $db->prepare($query);
$stmt->bindParam(':fecha', $fecha);
$stmt->execute();

$num = $stmt->rowCount();

if ($num > 0) {
    $turnos_arr = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Agregar los datos del pedido en el array
        $turno_item = array(
            "id_pedidoc" => $row['id_pedidoc'],
            "fecha_caja" => $row['fecha_caja'],
            "m_pago" => $row['m_pago'],
            "turno" => $row['turno'],
            "estado" => $row['estado'],
            "cliente" => $row['cliente']
        );
        array_push($turnos_arr, $turno_item);
    }
    echo json_encode(array("turnos" => $turnos_arr));
} else {
    // Si no se encontraron registros
    echo json_encode(array("turnos" => array()));
}
?>
