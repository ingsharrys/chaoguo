<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db = new Database();
        $conn = $db->getConnection();

        $nombre = $_POST['nombre_mese'] ?? null;
        $telefono = $_POST['phon_mese'] ?? null;
        $cedula = $_POST['cedula_mese'] ?? null;
        $cargo = $_POST['cargo_mese'] ?? null;
        $codigo = $_POST['cod_mese'] ?? null;

        if ($nombre && $telefono && $cedula && $cargo && $codigo) {
            $query = "INSERT INTO meseros (nombre_mese, phon_mese, cedula_mese, cargo, cod_mese) VALUES (:nombre, :telefono, :cedula, :cargo, :codigo)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':cedula', $cedula);
            $stmt->bindParam(':cargo', $cargo);
            $stmt->bindParam(':codigo', $codigo);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al agregar el mesero.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
}
?>
