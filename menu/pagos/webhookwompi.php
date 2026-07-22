<?php
require_once '../config/database.php';

function sendJsonResponse($status, $message) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Leer y decodificar el JSON de entrada
$json = file_get_contents('php://input');
$data = json_decode($json);

if ($data === null) {
    sendJsonResponse(400, "Invalid JSON");
}

if ($data->event === 'transaction.updated' && $data->data->transaction->status === 'APPROVED') {
    $numero_pedido = $data->data->transaction->reference;

    // Conectar a la base de datos y actualizar el estado
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Verificar conexión
        if (!$conn) {
            sendJsonResponse(500, "Database connection failed");
        }

        $query = "UPDATE pedidos SET estado = 'pagado' WHERE numero_pedido = ?";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            sendJsonResponse(500, "Failed to prepare statement");
        }

        $stmt->bindParam(1, $numero_pedido, PDO::PARAM_STR);

        if ($stmt->execute()) {
            sendJsonResponse(200, "Transaction Approved and database updated");
        } else {
            $errorInfo = $stmt->errorInfo();
            sendJsonResponse(500, "Failed to execute statement: " . $errorInfo[2]);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        sendJsonResponse(500, "Database error: " . $e->getMessage());
    }
} else {
    sendJsonResponse(422, "Unprocessable Entity");
}
?>
