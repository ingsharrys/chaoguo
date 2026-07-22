<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id_e'])) {
    $id_e = $_GET['id_e'];

    $db = new Database();
    $conn = $db->getConnection();

    // Desabilitar
    $query = "UPDATE domiciliarios SET elimina = 0 WHERE id_e = :id_e";
    //$query = "DELETE FROM domiciliarios WHERE id_e = :id_e";
    $stmt = $conn->prepare($query);

    try {
        $stmt->bindParam(':id_e', $id_e, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el domiciliario.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH' && isset($_GET['id_e'])) {
    $id_e = $_GET['id_e'];

    $db = new Database();
    $conn = $db->getConnection();

    // Habilitar
    $query = "UPDATE domiciliarios SET elimina = 1 WHERE id_e = :id_e";
    $stmt = $conn->prepare($query);

    try {
        $stmt->bindParam(':id_e', $id_e, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo restaurar el domiciliario.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}


?>
