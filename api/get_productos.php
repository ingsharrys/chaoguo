<?php
if (!headers_sent()) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json");
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error al conectar con la base de datos."]);
    exit;
}

$sql = "
    SELECT 
        p.id_pro, 
        p.nombre, 
        p.prefijo, 
        p.descript, 
        p.cat, 
        p.img,
        p.tcomida,
        pr.tipo_prod,
        pr.precio AS precio_tipo
    FROM productos p
    LEFT JOIN precios pr ON p.id_pro = pr.idproduc
    ORDER BY p.id_pro, pr.tipo_prod
";

$stmt = $conn->prepare($sql);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error al ejecutar la consulta."]);
    exit;
}

$productos = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row['id_pro'];

    if (!isset($productos[$id])) {
        $productos[$id] = [
            "id_pro"   => $row['id_pro'],
            "nombre"   => $row['nombre'],
            "prefijo"  => $row['prefijo'],
            "descript" => $row['descript'],
            "cat"      => $row['cat'],
            "img"      => $row['img'],
            "tcomida"  => $row['tcomida'],
            "tipos"    => []
        ];
    }

    if (!empty($row['tipo_prod'])) {
        $productos[$id]["tipos"][] = [
            "tipo_prod"   => $row['tipo_prod'],
            "precio_tipo" => (float) $row['precio_tipo']
        ];
    }
}

// Salida final
echo json_encode([
    "success" => true,
    "productos" => array_values($productos)
]);
