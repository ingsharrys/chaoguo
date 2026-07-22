<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repartidor = $_POST['repartidor'];
    $celu_reparti = $_POST['celu_reparti'];
    $calificacion = $_POST['calificacion'];

    $db = new Database();
    $conn = $db->getConnection();

    $query = "INSERT INTO domiciliarios (repartidor, celu_reparti, calificacion) VALUES (:repartidor, :celu_reparti, :calificacion)";
    $stmt = $conn->prepare($query);

    try {
        $stmt->bindParam(':repartidor', $repartidor);
        $stmt->bindParam(':celu_reparti', $celu_reparti);
        $stmt->bindParam(':calificacion', $calificacion);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo agregar el domiciliario.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}
?>
