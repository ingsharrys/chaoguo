<?php
// Mostrar errores de PHP solo para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Obtener el JSON de la solicitud
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['numero_pedido'], $data['nueva_mesa'], $data['mesa_actual'])) {
    $numero_pedido = $data['numero_pedido'];
    $nueva_mesa = $data['nueva_mesa'];
    $mesa_actual = $data['mesa_actual'];

    // Verificar si los datos están correctamente recibidos
    if (empty($numero_pedido)) {
        echo json_encode(['status' => 'error', 'message' => 'Número de pedido es nulo o inválido.']);
        exit;
    }

    try {
        // Iniciar la transacción
        $conn->beginTransaction();

        // Limpiar la mesa actual (vaciar los campos id_pedido y estado)
        $query_limpiar_mesa_actual = "UPDATE mesas SET id_pedido = NULL, estado = '' WHERE numero_mesa = :mesa_actual";
        $stmt_limpiar = $conn->prepare($query_limpiar_mesa_actual);
        $stmt_limpiar->bindParam(':mesa_actual', $mesa_actual, PDO::PARAM_STR);
        $stmt_limpiar->execute();

        // Asignar el pedido a la nueva mesa (llenar los campos id_pedido y estado)
        $query_actualizar_mesa_nueva = "UPDATE mesas SET id_pedido = :numero_pedido, estado = 'nuevo' WHERE numero_mesa = :nueva_mesa";
        $stmt_actualizar = $conn->prepare($query_actualizar_mesa_nueva);
        $stmt_actualizar->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_STR);
        $stmt_actualizar->bindParam(':nueva_mesa', $nueva_mesa, PDO::PARAM_STR);
        $stmt_actualizar->execute();

        // Actualizar la tabla 'pedidos' para reflejar el cambio de mesa
        $query_actualizar_pedido = "UPDATE pedidos SET mesa = :nueva_mesa WHERE numero_pedido = :numero_pedido";
        $stmt_actualizar_pedido = $conn->prepare($query_actualizar_pedido);
        $stmt_actualizar_pedido->bindParam(':nueva_mesa', $nueva_mesa, PDO::PARAM_STR);
        $stmt_actualizar_pedido->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_STR);
        $stmt_actualizar_pedido->execute();

        // Confirmar la transacción
        $conn->commit();

        // Respuesta exitosa
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        // Si hay algún error, revertir la transacción
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
}
?>
