<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_e = $_POST['id_e'];
    $repartidor = $_POST['repartidor'];
    $celu_reparti = $_POST['celu_reparti'];
    $calificacion = $_POST['calificacion'];

    $db = new Database();
    $conn = $db->getConnection();

    $query = "UPDATE domiciliarios SET repartidor = :repartidor, celu_reparti = :celu_reparti, calificacion = :calificacion WHERE id_e = :id_e";
    $stmt = $conn->prepare($query);

    try {
        $stmt->bindParam(':repartidor', $repartidor);
        $stmt->bindParam(':celu_reparti', $celu_reparti);
        $stmt->bindParam(':calificacion', $calificacion);
        $stmt->bindParam(':id_e', $id_e, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el domiciliario.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}
?>
