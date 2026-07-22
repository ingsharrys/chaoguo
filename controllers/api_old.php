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
    $sql = "SELECT idm, numero_mesa FROM mesas";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $mesas = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mesas[] = $row;
    }

    if (count($mesas) > 0) {
        echo json_encode($mesas);
    } else {
        echo json_encode(["message" => "No records found"]);
    }
} else {
    echo json_encode(["error" => "Failed to connect to the database"]);
    http_response_code(500);
}
?>
