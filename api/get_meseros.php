<?php
// Encabezados de CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Manejo de preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

// Inicializar conexión
try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "SELECT id_mese, nombre_mese FROM meseros ORDER BY nombre_mese ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $meseros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $meseros
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener meseros',
        'message' => $e->getMessage()
    ]);
}
?>
