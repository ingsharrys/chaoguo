<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['abonos']) || !is_array($data['abonos'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos de abonos no recibidos']);
    exit;
}

$abonos = $data['abonos'];
if (empty($abonos)) {
    echo json_encode(['status' => 'error', 'message' => 'Lista de abonos vacía']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();
    $sql = "INSERT INTO abono_credito (id_credito, m_pagocr, efectivo, fecha_abono)
            VALUES (:id_credito, :m_pagocr, :efectivo, NOW())";
    $stmt = $conn->prepare($sql);

    foreach ($abonos as $ab) {
        $id_credito = (int)$ab['id_credito'];
        $metodo     = $ab['m_pagocr'] ?? '';
        $efectivo   = (float)($ab['efectivo'] ?? 0);

        // Opcional: validaciones
        if ($id_credito <= 0 || $metodo === '' || $efectivo <= 0) {
            throw new Exception('Datos de abono inválidos');
        }

        $stmt->bindParam(':id_credito', $id_credito,  \PDO::PARAM_INT);
        $stmt->bindParam(':m_pagocr',   $metodo);
        $stmt->bindParam(':efectivo',   $efectivo);
        $stmt->execute();
    }
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Abonos insertados con éxito']);
}
catch (\Exception $e) {
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
