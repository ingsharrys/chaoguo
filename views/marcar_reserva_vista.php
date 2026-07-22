<?php
require_once '../helpers/Session.php';
Session::start();
require_once '../config/database.php';

if (!Session::get('user_id')) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idreserva = $_POST['idreserva'];
    $db = new Database();
    $conn = $db->getConnection();

    $query = "UPDATE reservas SET is_new = 0 WHERE idreserva = :idreserva";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':idreserva', $idreserva);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la reserva']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
