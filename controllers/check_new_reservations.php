<?php
require_once '../helpers/Session.php';
Session::start();
require_once '../config/database.php';

if (!Session::get('user_id')) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $query = "SELECT COUNT(*) as count FROM reservas WHERE is_new = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_reservations_count = $result['count'];
    echo json_encode(['success' => true, 'count' => $new_reservations_count]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
