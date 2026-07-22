<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['numero_pedido'])) {
        throw new Exception('N¨²mero de pedido no especificado');
    }

    $numero_pedido = $data['numero_pedido'];

    $db = new Database();
    $conn = $db->getConnection();

    // Actualizar el estado del pedido a "enviado"
    $query = "UPDATE pedidos SET estado = 'enviado' WHERE numero_pedido = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    $stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->errorInfo()[2]);
    }

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
