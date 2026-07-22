<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Si la solicitud es de tipo OPTIONS, responde con un código 200 y finaliza la ejecución
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ... el resto de tu código ...


include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->id) && 
    !empty($data->productos) && 
    !empty($data->numeroMesa)
) {
    $numero_pedido = htmlspecialchars(strip_tags($data->id));
    $productos_actualizados = $data->productos;
    $comentario = !empty($data->comentario) ? htmlspecialchars(strip_tags($data->comentario)) : null;
    
    // 1. Actualizar el comentario y otros detalles del pedido, si aplica
    $query = "UPDATE pedidos SET comentario = :comentario WHERE numero_pedido = :numero_pedido";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':comentario', $comentario);
    $stmt->bindParam(':numero_pedido', $numero_pedido);
    $stmt->execute();
    
    // 2. Obtener los productos actuales de la base de datos para el numero_pedido
    $query = "SELECT id_pro, tipo_prod FROM pedidos WHERE numero_pedido = :numero_pedido";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':numero_pedido', $numero_pedido);
    $stmt->execute();
    $productos_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $productos_existentes_map = [];
    foreach ($productos_existentes as $producto) {
        $productos_existentes_map[$producto['id_pro'] . '_' . $producto['tipo_prod']] = true;
    }
    
    // 3. Recorrer los productos actualizados
    foreach ($productos_actualizados as $producto) {
        $id_pro = htmlspecialchars(strip_tags($producto->id_pro));
        $tipo_prod = htmlspecialchars(strip_tags($producto->tipo_prod));
        $cantidad = htmlspecialchars(strip_tags($producto->cantidad));
        $detalle = htmlspecialchars(strip_tags($producto->detalle));

        if (isset($productos_existentes_map[$id_pro . '_' . $tipo_prod])) {
            // 3.1. Si el producto ya existe en la base de datos, actualizarlo
            $query = "UPDATE pedidos 
                      SET cantidad = :cantidad, detalle = :detalle 
                      WHERE numero_pedido = :numero_pedido AND id_pro = :id_pro AND tipo_prod = :tipo_prod";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':detalle', $detalle);
            $stmt->bindParam(':numero_pedido', $numero_pedido);
            $stmt->bindParam(':id_pro', $id_pro);
            $stmt->bindParam(':tipo_prod', $tipo_prod);
            $stmt->execute();
            
            // Eliminar el producto del array de productos existentes para que al final podamos saber cuáles eliminar
            unset($productos_existentes_map[$id_pro . '_' . $tipo_prod]);
        } else {
            // 3.2. Si el producto no existe en la base de datos, insertarlo
            $query = "INSERT INTO pedidos 
                      SET numero_pedido = :numero_pedido, id_pro = :id_pro, tipo_prod = :tipo_prod, cantidad = :cantidad, detalle = :detalle";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':numero_pedido', $numero_pedido);
            $stmt->bindParam(':id_pro', $id_pro);
            $stmt->bindParam(':tipo_prod', $tipo_prod);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':detalle', $detalle);
            $stmt->execute();
        }
    }
    
    // 4. Eliminar los productos que no fueron incluidos en la actualización
    if (!empty($productos_existentes_map)) {
        foreach ($productos_existentes_map as $key => $value) {
            list($id_pro, $tipo_prod) = explode('_', $key);
            $query = "DELETE FROM pedidos WHERE numero_pedido = :numero_pedido AND id_pro = :id_pro AND tipo_prod = :tipo_prod";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':numero_pedido', $numero_pedido);
            $stmt->bindParam(':id_pro', $id_pro);
            $stmt->bindParam(':tipo_prod', $tipo_prod);
            $stmt->execute();
        }
    }

    http_response_code(200);
    echo json_encode(array("message" => "Pedido actualizado correctamente."));
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Datos incompletos. No se pudo actualizar el pedido."));
}
?>
