<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id_mese, nombre_mese FROM meseros";
$stmt = $db->prepare($query);

if ($stmt->execute()) {
    $meseros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($meseros);
} else {
    http_response_code(500);
    echo json_encode(array("message" => "Error al obtener meseros."));
}
?>
