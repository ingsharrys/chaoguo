<?php
require_once '../config/database.php';

if (isset($_GET['id_mese'])) {
    $db = new Database();
    $conn = $db->getConnection();

    $id_mese = $_GET['id_mese'];

    $query = "SELECT * FROM meseros WHERE id_mese = :id_mese";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_mese', $id_mese);
    $stmt->execute();

    $mesero = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($mesero) {
        echo json_encode(['status' => 'success', 'mesero' => $mesero]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Mesero no encontrado.']);
    }
}
?>
