<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_GET['idCliente'])) {
    echo json_encode(['status' => 'error', 'message' => 'idCliente no proporcionado']);
    exit;
}

$idCliente = (int)$_GET['idCliente'];
if ($idCliente <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'idCliente inválido']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// 1) Obtener nombre del cliente
$queryCliente = "SELECT cliente FROM clientes WHERE id = :id LIMIT 1";
$stmt = $conn->prepare($queryCliente);
$stmt->bindParam(':id', $idCliente, PDO::PARAM_INT);
$stmt->execute();
$rowCliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no existe el cliente, puedes manejarlo así:
if (!$rowCliente) {
    echo json_encode(['status' => 'error', 'message' => 'No existe ese cliente']);
    exit;
}
$nombreCliente = $rowCliente['cliente'];

// 2) Obtener TODOS los créditos de este cliente
//    Unimos con "caja" vía creditos.m_pedidocr = caja.id_pedidoc
//    Para sacar la columna "caja.costo" que es el valor del crédito
$queryCreditos = "
    SELECT 
      c.idcr,
      c.fecha AS fecha_credito,
      c.m_pedidocr,
      ca.costo AS costo_credito,
      -- No es obligatorio traer c.id_clientecr ni cli.cliente otra vez, 
      -- ya tenemos $nombreCliente y $idCliente.
      c.id_clientecr
    FROM creditos c
    LEFT JOIN caja ca ON c.m_pedidocr = ca.id_pedidoc
    WHERE c.id_clientecr = :idCliente
    ORDER BY c.fecha
";
$stmt2 = $conn->prepare($queryCreditos);
$stmt2->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
$stmt2->execute();

$creditos = [];

while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $idcr         = $row['idcr'];
    $fechaCredito = $row['fecha_credito'];  // la columna 'fecha' renombrada
    $costo        = $row['costo_credito'] ?: 0;  // si es NULL, pones 0
    // 2.1) Obtener abonos de abono_credito
    $queryAbonos = "
       SELECT fecha_abono, m_pagocr, efectivo
       FROM abono_credito
       WHERE id_credito = :idcr
       ORDER BY fecha_abono DESC
    ";
    $stmtA = $conn->prepare($queryAbonos);
    $stmtA->bindParam(':idcr', $idcr, PDO::PARAM_INT);
    $stmtA->execute();
    $abonos = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    $creditos[] = [
        'idcr'   => $idcr,
        'fecha'  => $fechaCredito,
        'costo'  => (float)$costo,  // forzamos float
        'abonos' => $abonos
    ];
}

// 3) Construir la respuesta final
echo json_encode([
    'status'        => 'success',
    'nombreCliente' => $nombreCliente,   // para el mensaje "Créditos de X"
    'creditos'      => $creditos         // array con todos los créditos
]);
