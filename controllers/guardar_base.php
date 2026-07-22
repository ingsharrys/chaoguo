<?php
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $base = isset($_POST['base']) ? (int) $_POST['base'] : 0;
    $cajero = isset($_POST['cajero']) ? trim($_POST['cajero']) : '';

    if ($base <= 0 || empty($cajero)) {
        echo json_encode(["status" => "error", "message" => "Datos inválidos."]);
        exit();
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Insertar base con fecha actual
        $query = "INSERT INTO base (base, fechab, cajero_base) VALUES (:base, CURDATE(), :cajero)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':base', $base, PDO::PARAM_INT);
        $stmt->bindParam(':cajero', $cajero, PDO::PARAM_STR);
        $stmt->execute();

        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
