<?php
// Incluir la conexión a la base de datos
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Obtener los datos enviados en el cuerpo de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['numero_mesa'])) {
        throw new Exception('No se envió el número de la mesa.');
    }

    $numero_mesa = $input['numero_mesa'];

    // Preparar el query para liberar la mesa
    $query = "UPDATE mesas SET estado = '', id_pedido = NULL WHERE numero_mesa = :numero_mesa";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':numero_mesa', $numero_mesa, PDO::PARAM_INT);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Mesa liberada correctamente']);
    } else {
        throw new Exception('No se pudo liberar la mesa.');
    }

} catch (Exception $e) {
    // En caso de error, devolver un mensaje de error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
