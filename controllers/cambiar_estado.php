<?php
// Mostrar todos los errores de PHP (solo para desarrollo, no en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de configuración de la base de datos
require_once '../config/database.php';

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el cuerpo de la solicitud
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Verificar si los parámetros necesarios están presentes en el cuerpo de la solicitud
    if (isset($data['numero_pedido']) && isset($data['nuevo_estado'])) {
        $numero_pedido = $data['numero_pedido'];
        $nuevo_estado = $data['nuevo_estado'];

        // Iniciar una transacción
        $conn->beginTransaction();

        try {
            // Consulta para actualizar el estado en la tabla `mesas`
            $query_mesas = "UPDATE mesas SET estado = :nuevo_estado WHERE id_pedido = :numero_pedido";
            $stmt_mesas = $conn->prepare($query_mesas);
            $stmt_mesas->bindParam(':nuevo_estado', $nuevo_estado, PDO::PARAM_STR);
            $stmt_mesas->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_STR);
            $stmt_mesas->execute();

            // Consulta para actualizar el estado_boton en la tabla `pedidos`
            $query_pedidos = "UPDATE pedidos SET estado_boton = :nuevo_estado WHERE numero_pedido = :numero_pedido";
            $stmt_pedidos = $conn->prepare($query_pedidos);
            $stmt_pedidos->bindParam(':nuevo_estado', $nuevo_estado, PDO::PARAM_STR);
            $stmt_pedidos->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_STR);
            $stmt_pedidos->execute();

            // Confirmar la transacción
            $conn->commit();

            // Responder con un JSON de éxito
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $conn->rollBack();
            // Responder con un JSON de error
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el estado del pedido.']);
        }
    } else {
        // Responder con un JSON de error si faltan parámetros
        echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros requeridos.']);
    }
} else {
    // Responder con un JSON de error si la solicitud no es POST
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
