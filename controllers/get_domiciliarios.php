<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Mensaje de depuración: Conexión exitosa
    error_log("Conexión a la base de datos establecida.");

    $query = "SELECT id_e, repartidor FROM domiciliarios";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    // Mensaje de depuración: Consulta preparada
    error_log("Consulta preparada.");

    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }

    // Mensaje de depuración: Consulta ejecutada
    error_log("Consulta ejecutada.");

    $domiciliarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar si $domiciliarios está vacío
    if (!$domiciliarios) {
        throw new Exception("No se encontraron domiciliarios.");
    }

    // Mensaje de depuración: Datos obtenidos
    error_log("Datos obtenidos: " . json_encode($domiciliarios));

    echo json_encode(['status' => 'success', 'domiciliarios' => $domiciliarios]);
} catch (Exception $e) {
    // Mensaje de depuración: Error capturado
    error_log("Error capturado: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
