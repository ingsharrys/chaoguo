<?php
require_once 'config/database.php';

// Crear una instancia de la base de datos y obtener la conexión
$db = new Database();
$conn = $db->getConnection();

// Verificar que se reciba el ID de la mesa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mesa_id'])) {
    $mesaId = $_POST['mesa_id'];

    try {
        // Consulta para obtener el id_pedido de la mesa seleccionada
        $queryMesa = "SELECT id_pedido FROM mesas WHERE idm = :mesa_id";
        $stmtMesa = $conn->prepare($queryMesa);
        $stmtMesa->bindParam(':mesa_id', $mesaId, PDO::PARAM_INT);

        if ($stmtMesa->execute()) {
            $mesa = $stmtMesa->fetch(PDO::FETCH_ASSOC);

            if ($mesa) {
                $idPedido = $mesa['id_pedido'];

                // Actualizar el estado en la tabla pedidos
                $queryUpdatePedido = "UPDATE pedidos SET estado = 'entregado' WHERE numero_pedido = :id_pedido";
                $stmtUpdatePedido = $conn->prepare($queryUpdatePedido);
                $stmtUpdatePedido->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);

                if ($stmtUpdatePedido->execute()) {
                    // Actualizar el estado en la tabla mesas
                    $queryUpdateMesa = "UPDATE mesas SET estado = 'entregado' WHERE idm = :mesa_id";
                    $stmtUpdateMesa = $conn->prepare($queryUpdateMesa);
                    $stmtUpdateMesa->bindParam(':mesa_id', $mesaId, PDO::PARAM_INT);

                    if ($stmtUpdateMesa->execute()) {
                        // Enviar respuesta de éxito
                        echo json_encode(['success' => true]);
                        exit;
                    } else {
                        throw new Exception('Error al actualizar el estado de la mesa.');
                    }
                } else {
                    throw new Exception('Error al actualizar el estado del pedido.');
                }
            } else {
                throw new Exception('Mesa no encontrada.');
            }
        } else {
            throw new Exception('Error al consultar la mesa en la base de datos.');
        }
    } catch (Exception $e) {
        // Si ocurre algún error, devolver el mensaje del error
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID de mesa no proporcionado.']);
}
?>
