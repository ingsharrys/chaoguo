<?php
// Mostrar todos los errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de configuración de la base de datos
require_once '../config/database.php';

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Leer el cuerpo de la solicitud JSON
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['numero_pedido']) && isset($data['nuevo_estado'])) {
    $numero_pedido = $data['numero_pedido'];
    $nuevo_estado = $data['nuevo_estado'];

    // Actualizar el estado de la mesa en la base de datos
    $query = "UPDATE mesas SET estado = :nuevo_estado WHERE id_pedido = :numero_pedido";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':nuevo_estado', $nuevo_estado);
    $stmt->bindParam(':numero_pedido', $numero_pedido);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo actualizar el estado.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
}
?>
