<?php
include_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(isset($data->numero_pedido)) {
    $numero_pedido = $data->numero_pedido;

    // Iniciar la transacción
    $conn->beginTransaction();

    try {
        // Actualizar el estado del pedido sin cambiar la fecha
        $query = "UPDATE pedidos SET estado_boton = 'en_cocina' WHERE numero_pedido = :numero_pedido";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':numero_pedido', $numero_pedido);
        $stmt->execute();

        // Obtener el número de mesa asociado al pedido
        $mesa_query = "SELECT mesa FROM pedidos WHERE numero_pedido = :numero_pedido";
        $mesa_stmt = $conn->prepare($mesa_query);
        $mesa_stmt->bindParam(':numero_pedido', $numero_pedido);
        $mesa_stmt->execute();
        $mesa = $mesa_stmt->fetch(PDO::FETCH_ASSOC)['mesa'];

        if ($mesa) {
            // Actualizar el estado de la mesa y el campo id_pedido
            $update_mesa_query = "UPDATE mesas SET estado = 'en_cocina', id_pedido = :numero_pedido WHERE numero_mesa = :numero_mesa";
            $update_mesa_stmt = $conn->prepare($update_mesa_query);
            $update_mesa_stmt->bindParam(':numero_mesa', $mesa);
            $update_mesa_stmt->bindParam(':numero_pedido', $numero_pedido);
            $update_mesa_stmt->execute();
        }

        // Confirmar la transacción
        $conn->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el estado del pedido y la mesa.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Número de pedido no proporcionado.']);
}
?>
