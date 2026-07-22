<?php
require_once '../config/database.php';

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');
$fechaActual = date('Y-m-d');

$db = new Database();
$conn = $db->getConnection();

// Obtener la base registrada en la fecha actual
$query = "SELECT COALESCE(SUM(base), 0) as base, GROUP_CONCAT(DISTINCT cajero_base SEPARATOR ', ') as cajero_base 
          FROM base 
          WHERE fechab = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$fechaActual]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$baseHoy = $result['base'];
$cajeroBase = $result['cajero_base'] ?? 'No registrado';

// 🔥 Corrección: Se pasa la fecha como parámetro a la consulta
$query_efectivo = "SELECT COALESCE(SUM(efectivo), 0) as efectivo FROM caja WHERE fecha_caja = ?";
$stmt2 = $conn->prepare($query_efectivo);
$stmt2->execute([$fechaActual]); // ✅ Se pasa la fecha como parámetro
$result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
$efectivoTotal = $result2['efectivo'] + $baseHoy; // Sumamos la base del día actual al efectivo

echo json_encode([
    "base" => $baseHoy,
    'cajero_base' => $cajeroBase,
    "efectivo_total" => $efectivoTotal
]);
?>
