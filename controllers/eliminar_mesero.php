<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id_mese'])) {
    $db = new Database();
    $conn = $db->getConnection();

    $id_mese = $_GET['id_mese'];

    $query = "DELETE FROM meseros WHERE id_mese = :id_mese";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_mese', $id_mese);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el mesero.']);
    }
}
?>
