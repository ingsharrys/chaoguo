<?php

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();
// Configurar el tipo de respuesta como JSON
header('Content-Type: application/json');

try {
    // Obtener los datos enviados en el cuerpo de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar que se haya enviado el ID del pedido
    if (!isset($input['id_pedido']) || empty($input['id_pedido'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID de pedido no proporcionado.']);
        exit;
    }

    $idPedido = intval($input['id_pedido']);

    // Preparar la consulta SQL para eliminar el producto del pedido
    $query = "DELETE FROM pedidos WHERE id_pedido = :id_pedido";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Producto eliminado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el producto.']);
    }
} catch (Exception $e) {
    // Manejo de errores
    echo json_encode(['status' => 'error', 'message' => 'Error del servidor: ' . $e->getMessage()]);
}

?>
