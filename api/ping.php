<?php
/**
 * PRUEBA DE VELOCIDAD (temporal) - ChaoGuo
 *
 * Abrir en el navegador:
 *   /api/ping.php        → mide solo la respuesta del servidor (red + PHP)
 *   /api/ping.php?bd=1   → además mide la conexión a la base de datos y
 *                          la consulta del turno del día (la del pedido)
 *
 * Si ping.php ya tarda varios segundos, el problema es la RED o el
 * HOSTING, no la base de datos. Si ping.php es rápido pero ?bd=1 es
 * lento, el problema está en MySQL.
 *
 * Borrar este archivo cuando termine el diagnóstico.
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$t0  = microtime(true);
$res = ['success' => true, 'hora_servidor' => date('Y-m-d H:i:s')];

if (isset($_GET['bd'])) {
    require_once '../config/database.php';

    $t1 = microtime(true);
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $res['conexion_bd_ms'] = round((microtime(true) - $t1) * 1000);

    /* misma consulta que usa post_pedido.php para el turno (sin bloqueo) */
    $t2 = microtime(true);
    $db->query("
        SELECT COALESCE(MAX(turno),0)
        FROM turnero
        WHERE fecha >= CURDATE()
          AND fecha <  CURDATE() + INTERVAL 1 DAY
          AND tipo_solicitud = 52
    ")->fetchColumn();
    $res['consulta_turno_ms'] = round((microtime(true) - $t2) * 1000);

    /* transacciones abiertas hace más de 5 segundos: si hay alguna,
       está reteniendo bloqueos y frena los pedidos */
    try {
        $t3 = microtime(true);
        $trx = $db->query("
            SELECT COUNT(*)
            FROM information_schema.innodb_trx
            WHERE trx_started < NOW() - INTERVAL 5 SECOND
        ")->fetchColumn();
        $res['transacciones_viejas'] = (int)$trx;
        $res['chequeo_bloqueos_ms']  = round((microtime(true) - $t3) * 1000);
    } catch (Exception $e) {
        $res['transacciones_viejas'] = 'sin permiso para consultar (normal en hosting compartido)';
    }
}

$res['total_ms'] = round((microtime(true) - $t0) * 1000);
echo json_encode($res);
