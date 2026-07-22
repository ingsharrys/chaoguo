<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id_credito']) || !isset($data['abonos'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Datos incompletos.'
    ]);
    exit;
}

$id_credito = (int)$data['id_credito'];
$abonos     = $data['abonos'];

if ($id_credito <= 0 || !is_array($abonos) || empty($abonos)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Datos de abono inválidos.'
    ]);
    exit;
}

try {
    $conn->beginTransaction();

    // Insertar cada abono
    $sqlAbono = "INSERT INTO abono_credito (id_credito, m_pagocr, efectivo, fecha_abono)
                 VALUES (:id_credito, :m_pagocr, :efectivo, NOW())";
    $stmt = $conn->prepare($sqlAbono);

    foreach ($abonos as $ab) {
        $metodo  = $ab['m_pagocr'] ?? '';
        $efectiv = (float)($ab['efectivo'] ?? 0);

        if (!$metodo) {
            throw new Exception('Método de abono no especificado.');
        }
        // Insertar fila
        $stmt->bindParam(':id_credito', $id_credito,  PDO::PARAM_INT);
        $stmt->bindParam(':m_pagocr',   $metodo);
        $stmt->bindParam(':efectivo',   $efectiv);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode([
        'status'  => 'success',
        'message' => 'Abonos guardados correctamente.'
    ]);
}
catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al insertar abonos: ' . $e->getMessage()
    ]);
}
