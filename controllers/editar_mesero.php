<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();

    $id_mese = $_POST['id_mese'];
    $nombre = $_POST['nombre_mese'];
    $telefono = $_POST['phon_mese'];
    $cedula = $_POST['cedula_mese'];
    $cargo = $_POST['cargo_mese'];
    $codigo = $_POST['cod_mese'];

    $query = "UPDATE meseros SET nombre_mese = :nombre, phon_mese = :telefono, cedula_mese = :cedula, cargo = :cargo, cod_mese = :codigo WHERE id_mese = :id_mese";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_mese', $id_mese);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':cedula', $cedula);
    $stmt->bindParam(':cargo', $cargo);
    $stmt->bindParam(':codigo', $codigo);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el mesero.']);
    }
}
?>
