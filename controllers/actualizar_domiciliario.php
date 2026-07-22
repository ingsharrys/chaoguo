<?php
header('Content-Type: application/json');

try {
    // Conexión a la base de datos
    include_once '../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();

    // Obtener los datos enviados
    $data = json_decode(file_get_contents('php://input'), true);

    // Verificar que los datos existen
    if (isset($data['id_pedido'], $data['id_domi'])) {
        // Preparar la consulta SQL para actualizar el id_domi
        $stmt = $conn->prepare("UPDATE domicilios SET id_domi = :id_domi WHERE id_pedido = :id_pedido");
        $stmt->bindParam(':id_pedido', $data['id_pedido']);
        $stmt->bindParam(':id_domi', $data['id_domi']);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el domiciliario.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>