<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Verificar si se recibieron los datos necesarios
if (isset($_POST['numero_pedido']) && isset($_POST['nuevo_estado'])) {
    $numero_pedido = $_POST['numero_pedido'];
    $nuevo_estado = $_POST['nuevo_estado'];

    // Actualizar el estado del pedido en la tabla turnero
    $query = "UPDATE turnero SET estado = :nuevo_estado WHERE id_pedido = :numero_pedido";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nuevo_estado', $nuevo_estado);
    $stmt->bindParam(':numero_pedido', $numero_pedido);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
}
?>
