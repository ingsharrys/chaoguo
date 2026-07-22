<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Obtener el id del pedido desde la petición GET
$id_pedido = isset($_GET['id_pedido']) ? $_GET['id_pedido'] : null;

if (!$id_pedido) {
    // Si no se proporciona el id del pedido, devolver un error
    echo json_encode(array("error" => "Id del pedido no proporcionado."));
    exit();
}

// Consultar los productos del pedido
$query = "
    SELECT pr.nombre AS producto, p.cantidad, precio.precio
    FROM pedidos p
    JOIN productos pr ON p.id_pro = pr.id_pro
    JOIN precios precio ON precio.idproduc = p.id_pro AND precio.tipo_prod = p.tipo_producto
    WHERE p.numero_pedido = :id_pedido
";

$stmt = $db->prepare($query);
$stmt->bindParam(':id_pedido', $id_pedido);
$stmt->execute();

$num = $stmt->rowCount();

if ($num > 0) {
    $productos_arr = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $producto_item = array(
            "producto" => $row['producto'],
            "cantidad" => $row['cantidad'],
            "precio" => $row['precio']
        );
        array_push($productos_arr, $producto_item);
    }
    echo json_encode(array("productos" => $productos_arr));
} else {
    // Si no se encontraron productos
    echo json_encode(array("productos" => array()));
}
?>
