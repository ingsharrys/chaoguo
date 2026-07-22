<?php
require_once '../config/database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    // Capturar los datos JSON recibidos
    $data = json_decode(file_get_contents('php://input'), true);

    // Verificar que los datos están presentes
    if (!isset($data['pedido']) || !isset($data['repartidor'])) {
        throw new Exception("Datos incompletos.");
    }

    $numero_pedido = $data['pedido'];
    $id_repartidor = $data['repartidor'];

    // Conexión a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // Preparar la consulta SQL
    $query = "UPDATE domicilios SET id_domi = :id_repartidor WHERE id_pedido = :numero_pedido";
    $stmt = $conn->prepare($query);

    // Asignar valores a los parámetros de la consulta
    $stmt->bindValue(':id_repartidor', $id_repartidor, PDO::PARAM_INT);
    $stmt->bindValue(':numero_pedido', $numero_pedido, PDO::PARAM_STR);

    // Ejecutar la consulta
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . implode(", ", $stmt->errorInfo()));
    }

    // Retornar respuesta de éxito
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    // Capturar errores y enviar respuesta de error
    error_log("Error capturado: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
