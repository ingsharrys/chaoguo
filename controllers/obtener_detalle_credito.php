<?php
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Tomar la idcr del GET
$idcr = isset($_GET['idcr']) ? intval($_GET['idcr']) : 0;
if ($idcr <= 0) {
    echo json_encode(["status" => "error", "message" => "Parámetro 'idcr' inválido."]);
    exit;
}

// Consulta para obtener los datos del crédito, junto con el nombre de cliente
$sqlCredito = "
    SELECT c.idcr,
           c.id_clientecr,
           c.fecha,
           cli.cliente AS nombre_cliente
    FROM creditos c
    LEFT JOIN clientes cli ON c.id_clientecr = cli.id
    WHERE c.idcr = :idcr
    LIMIT 1
";

$stmt = $conn->prepare($sqlCredito);
$stmt->bindParam(':idcr', $idcr, PDO::PARAM_INT);
$stmt->execute();
$credito = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$credito) {
    echo json_encode(["status" => "error", "message" => "No se encontró el crédito con idcr=$idcr."]);
    exit;
}

// Consulta de los abonos correspondientes a este crédito
$sqlAbonos = "
    SELECT id,
           m_pagocr,
           efectivo,
           fecha_abono
    FROM abono_credito
    WHERE id_credito = :idcr
    ORDER BY fecha_abono DESC
";
$stmt = $conn->prepare($sqlAbonos);
$stmt->bindParam(':idcr', $idcr, PDO::PARAM_INT);
$stmt->execute();
$abonos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retornar JSON con la información
echo json_encode([
    "status"  => "success",
    "credito" => $credito,
    "abonos"  => $abonos
]);
