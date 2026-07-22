<?php

require_once '../config/database.php';
require_once '../helpers/TotalCalculator.php';

// Conectar con la base de datos
$db = new Database();
$conn = $db->getConnection();

$user_id = Session::get('user_id');
$usuario = Session::get('usuario');
$cajero  = $usuario['cajero'] ?? 'No identificado';
$cargo   = $usuario['rol']    ?? 'Sin cargo';
$idsmese   = $usuario['id_mese']    ?? 'Sin cargo';

date_default_timezone_set('America/Bogota');
$fecha_actual = date('Y-m-d');

// ----------------------------------------------------------------------
// 1) Calcular los abonos del d¨ªa por m¨¦todo de pago, sin importarlos pedido a pedido
// ----------------------------------------------------------------------
$abonosEfectivoHoy = 0;
$abonosTarjetaHoy  = 0;
$abonosTransferHoy = 0;

/**
 * Usamos SUM + CASE para agrupar por m¨¦todo:
 * - m_pagocr='efectivo' -> suma a abonos_efectivo
 * - m_pagocr='tarjeta' -> suma a abonos_tarjeta
 * - m_pagocr='transferencia' -> suma a abonos_transfer
 */
$queryAbonosMetodo = "
    SELECT
       SUM(CASE WHEN m_pagocr='credito' THEN CAST(efectivo AS DECIMAL(10,2)) ELSE 0 END) AS abonos_efectivocr,
       SUM(CASE WHEN m_pagocr='efectivo' THEN CAST(efectivo AS DECIMAL(10,2)) ELSE 0 END) AS abonos_efectivo,
       SUM(CASE WHEN m_pagocr='tarjeta' THEN CAST(efectivo AS DECIMAL(10,2)) ELSE 0 END) AS abonos_tarjeta,
       SUM(CASE WHEN m_pagocr='transferencia' THEN CAST(efectivo AS DECIMAL(10,2)) ELSE 0 END) AS abonos_transfer
    FROM abono_credito
    WHERE DATE(fecha_abono)=:fecha_actual
";
$stmtAbonosMetodo = $conn->prepare($queryAbonosMetodo);
$stmtAbonosMetodo->execute([':fecha_actual' => $fecha_actual]);
$resultAbonosMetodo = $stmtAbonosMetodo->fetch(PDO::FETCH_ASSOC);

if ($resultAbonosMetodo) {
    $abonosEfectivocrHoy = floatval($resultAbonosMetodo['abonos_efectivocr']);
    $abonosEfectivoHoy = floatval($resultAbonosMetodo['abonos_efectivo']);
    $abonosTarjetaHoy  = floatval($resultAbonosMetodo['abonos_tarjeta']);
    $abonosTransferHoy = floatval($resultAbonosMetodo['abonos_transfer']);
}

// ----------------------------------------------------------------------
// 2) Obtener gastos y n¨®mina
// ----------------------------------------------------------------------
$query = "SELECT SUM(monto) AS total_gastos FROM gastos WHERE fecha = CURDATE()";
$stmt  = $conn->prepare($query);
$stmt->execute();
$result       = $stmt->fetch(PDO::FETCH_ASSOC);
$total_gastos = $result['total_gastos'] ?? 0;

// Obtener gastos de n¨®mina
$query_abonos = "SELECT SUM(cantidad) AS total_nomina FROM abono_nomina WHERE DATE(fecha) = :fecha_actual";
$stmt_abonos  = $conn->prepare($query_abonos);
$stmt_abonos->bindParam(':fecha_actual', $fecha_actual);
$stmt_abonos->execute();
$total_nomina = $stmt_abonos->fetchColumn() ?: 0;

// ----------------------------------------------------------------------
// 3) Consultar caja (ingresos del d¨ªa) por fecha_caja y cajero
// ----------------------------------------------------------------------
$query = "
    SELECT *
    FROM caja c
    WHERE DATE(c.fecha_caja) = :fecha_actual
      AND c.id_cajero = :cajero
";
$stmt = $conn->prepare($query);
$stmt->bindParam(':fecha_actual', $fecha_actual);
$stmt->bindParam(':cajero', $idsmese);
$stmt->execute();
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializar totales
$total_efectivo      = 0;
$total_tarjeta       = 0;
$total_transferencia = 0;

// ----------------------------------------------------------------------
// 4) Recorrer cada registro de caja y sumar seg¨²n m_pago
//    (Ya no es necesario buscar abonos en el case 'credito' porque
//     los abonos del d¨ªa ya los sumaremos abajo.)
// ----------------------------------------------------------------------
foreach ($registros as $registro) {
    $costo    = floatval($registro['costo']);
    $efectivo = floatval($registro['efectivo']);
    $m_pago   = $registro['m_pago'];

    switch ($m_pago) {
        case 'efectivo':
            $total_efectivo += $costo;
            break;
        case 'transferencia':
            $total_transferencia += $costo;
            break;
        case 'tarjeta':
            $total_tarjeta += $costo;
            break;
        case 'efectivo_transferencia':
            $total_efectivo += $efectivo;
            $dif = $costo - $efectivo;
            if ($dif > 0) {
                $total_transferencia += $dif;
            }
            break;
        case 'tarjeta_efectivo':
            $total_efectivo += $efectivo;
            $dif = $costo - $efectivo;
            if ($dif > 0) {
                $total_tarjeta += $dif;
            }
            break;
        case 'credito':
            /**
             * Si deseas, puedes sumar $costo o no, dependiendo de tu flujo.
             * Normalmente, "credito" no se cuenta como ingreso en caja
             * hasta que abonen.
             * Por ahora, no lo sumamos aqu¨ª,
             * ya que se refleja en los abonos que se calculan aparte.
             */
            break;
    }
}

// ----------------------------------------------------------------------
// 5) Sumar la n¨®mina al efectivo (si deseas)
// ----------------------------------------------------------------------
$total_efectivo += $total_nomina;

// ----------------------------------------------------------------------
// 6) Sumar los abonos del d¨ªa a los totales
//    (efectivo, tarjeta, transferencia)
// ----------------------------------------------------------------------
$total_crefect = 0; // Se inicializa para evitar error
$total_crefect += $abonosEfectivoHoy + $abonosEfectivocrHoy;
$total_efectivo      += $total_crefect;
$total_tarjeta       += $abonosTarjetaHoy;
$total_transferencia += $abonosTransferHoy;


// ----------------------------------------------------------------------
// 7) Calcular diferencia (Efectivo - Gastos), si lo deseas as¨ª
// ----------------------------------------------------------------------
$diferencia = $total_efectivo - $total_gastos;

// ----------------------------------------------------------------------
// 8) Variables para usar en header.php
// ----------------------------------------------------------------------
?>
