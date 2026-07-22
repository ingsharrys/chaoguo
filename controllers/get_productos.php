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
    $sql = "
    SELECT 
        p.id_pro, 
        p.nombre, 
        p.prefijo, 
        p.descript, 
        p.cat, 
        p.img,
        p.tcomida,
        pr.idpre,
        pr.tipo_prod,
        pr.precio AS precio_tipo
    FROM 
        productos p
    LEFT JOIN 
        precios pr 
    ON 
        p.id_pro = pr.idproduc";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $productos = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $productos[] = $row;
    }

    if (count($productos) > 0) {
        echo json_encode($productos);
    } else {
        echo json_encode(["message" => "No records found"]);
    }
} else {
    echo json_encode(["error" => "Failed to connect to the database"]);
    http_response_code(500);
}
?>
