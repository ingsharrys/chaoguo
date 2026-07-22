<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    
    // Consulta para obtener todos los domiciliarios
    $query = "SELECT * FROM domiciliarios where elimina = 1";
    $stmt = $conn->prepare($query);

    try {
        $stmt->execute();
        $domiciliarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($domiciliarios) {
            echo json_encode(['status' => 'success', 'domiciliarios' => $domiciliarios]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No hay domiciliarios disponibles.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }

} else {
    
    $id = $_GET['id'];
    $query = "SELECT id_e, repartidor, celu_reparti, calificacion FROM domiciliarios
            WHERE id_e = :id_e ";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_e', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $domiciliarios = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($domiciliarios) {
            echo json_encode(['status' => 'success', 'domiciliarios' => $domiciliarios]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'domiciliario no encontrado.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta.']);
    }

}




?>
