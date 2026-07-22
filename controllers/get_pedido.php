<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Leemos el numero_pedido de la URL ?id=1234
$numero_pedido = isset($_GET['id']) ? $_GET['id'] : die();

$query = "
    SELECT 
        ped.id_pro,
        ped.cantidad,
        ped.numero_pedido,
        ped.tipo_solicitud,
        ped.detalle,
        ped.tipo_producto,
        ped.mesa,
        ped.mesero,
        -- Campos de la tabla productos
        prod.nombre        AS nombre_producto,
        prod.prefijo       AS prefijo,
        prod.descript      AS descript,
        -- Campos de la tabla precios (si existe precio para ese tipo_prod)
        IFNULL(pre.precio, 0) AS precio_tipo
    FROM 
        pedidos ped
        LEFT JOIN productos prod ON ped.id_pro = prod.id_pro
        LEFT JOIN precios  pre  ON prod.id_pro = pre.idproduc 
                               AND ped.tipo_producto = pre.tipo_prod
    WHERE 
        ped.numero_pedido = ?
";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $numero_pedido, PDO::PARAM_INT);

if ($stmt->execute()) {
    $num = $stmt->rowCount();

    if ($num > 0) {
        $productos_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Extraemos cada columna para armar el JSON
            extract($row);
            
            $producto_item = array(
                "id_pro"       => $id_pro,
                "nombre"       => $nombre_producto,  // tomado de prod.nombre
                "prefijo"      => $prefijo,          // tomado de prod.prefijo
                "descript"     => $descript,         // tomado de prod.descript
                "tipo_prod"    => $tipo_producto,    // en la tabla pedidos se llama tipo_producto
                "precio_tipo"  => $precio_tipo,      // tomado de la tabla precios
                "cantidad"     => $cantidad,
                "detalle"      => $detalle,
                "mesero"       => $mesero,
                "numero_pedido"=> $numero_pedido
            );
            array_push($productos_arr, $producto_item);
        }

        // Devolvemos un array JSON con todos los productos del pedido
        echo json_encode($productos_arr);
    } else {
        // Si no hay productos para ese numero_pedido, retornamos un array vac¨ªo
        echo json_encode(array());
    }
} else {
    http_response_code(500);
    echo json_encode(array("message" => "Error al ejecutar la consulta."));
}
?>
