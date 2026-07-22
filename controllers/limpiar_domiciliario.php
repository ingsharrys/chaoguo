<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Obtener el número de pedido desde el cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);
$numero_pedido = isset($data['numero_pedido']) ? $data['numero_pedido'] : null;

if ($numero_pedido) {
    error_log("Limpiando domiciliario para el pedido: " . $numero_pedido); // Log para verificar el pedido
    // Actualizar el campo id_domi a NULL en la tabla domicilios
    $query = "UPDATE domicilios SET id_domi = NULL WHERE id_pedido = :numero_pedido";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        error_log("Error al ejecutar la actualización en la base de datos."); // Log de error en la base de datos
        echo json_encode(['status' => 'error', 'message' => 'No se pudo limpiar el domiciliario.']);
    }
} else {
    error_log("Número de pedido no proporcionado."); // Log para verificar que el número de pedido fue enviado
    echo json_encode(['status' => 'error', 'message' => 'Número de pedido no proporcionado.']);
}
?>
