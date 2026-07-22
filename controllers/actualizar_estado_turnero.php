<?php
header('Content-Type: application/json');

// Mostrar errores (opcional, para debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de configuraci¨®n de la base de datos
require_once '../config/database.php';

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Obtener el cuerpo de la solicitud JSON
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['numero_pedido']) && isset($data['nuevo_estado'])) {
    $numero_pedido = $data['numero_pedido'];
    $nuevo_estado = $data['nuevo_estado'];

    // Actualizar el estado en la tabla turnero
    $query = "UPDATE turnero SET estado = :nuevo_estado WHERE id_pedido = :numero_pedido";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':nuevo_estado', $nuevo_estado);
    $stmt->bindParam(':numero_pedido', $numero_pedido);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la actualizaci¨®n.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
}
?>
