<?php
/* ───────────────────────────  CABECERAS CORS  ────────────────────────── */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/post_pedido.log');

set_exception_handler(function($e) {
    error_log('[FATAL] '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    exit;
});

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ───────────────────────────  DEPENDENCIAS  ─────────────────────────── */
require_once '../config/database.php';
date_default_timezone_set('America/Bogota');

/* ─────────────────────  CONEXIÓN Y TRANSACCIÓN  ─────────────────────── */
$db = (new Database())->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->beginTransaction();

try {
    $data = json_decode(file_get_contents("php://input"));

    /* ── Validación básica ── */
    if (empty($data->productos) || !is_array($data->productos) ||
        empty($data->numeroMesa) || empty($data->tipo_solicitud)) {
        throw new Exception("Se requieren 'productos', 'numeroMesa' y 'tipo_solicitud'.", 400);
    }

    /* ─────────────────────  1. Generar numero_pedido  ──────────────────
       Usa tabla 'consecutivos': bloquea UNA sola fila, sin table‑lock   */
    $db->exec("
        UPDATE consecutivos
        SET valor = LAST_INSERT_ID(valor + 1)
        WHERE nombre = 'num_pedido'
    ");
    $numero_pedido = (int)$db->lastInsertId();      // ← nuevo ID único

    /* ─────────────────────  2. Generar número de turno  ─────────────── */
    $stmtTurno = $db->prepare("
        SELECT COALESCE(MAX(turno),0)+1
        FROM turnero
        WHERE DATE(fecha)=CURDATE() AND tipo_solicitud=:tipo
        FOR UPDATE
    ");
    $stmtTurno->execute([':tipo'=>$data->tipo_solicitud]);
    $numero_turno = (int)$stmtTurno->fetchColumn();

    /* ─────────────────────  3. Insertar en turnero  ─────────────────── */
    $db->prepare("
        INSERT INTO turnero
        (id_pedido,turno,fecha,tipo_solicitud,estado,id_cliente)
        VALUES (:id_pedido,:turno,NOW(),:tipo,'nuevo',:cli)
    ")->execute([
        ':id_pedido'=>$numero_pedido,
        ':turno'    =>$numero_turno,
        ':tipo'     =>$data->tipo_solicitud,
        ':cli'      =>$data->id_cliente ?? 1
    ]);

    /* ─────────────────────  4. Insertar cada producto  ──────────────── */
    $stmtP = $db->prepare("
        INSERT INTO pedidos
        (id_pro,cantidad,numero_pedido,tipo_solicitud,detalle,
         tipo_producto,mesa,mesero,fecha)
        VALUES
        (:id_pro,:cant,:num_ped,:tipo,:det,:tipo_prod,:mesa,:mesero,NOW())
    ");

    foreach ($data->productos as $p) {
        if (($p->cantidad ?? 0) <= 0) {
            throw new Exception("Cantidad inválida en producto {$p->id_pro}", 422);
        }
        $stmtP->execute([
            ':id_pro'    => $p->id_pro,
            ':cant'      => $p->cantidad,
            ':num_ped'   => $numero_pedido,
            ':tipo'      => $data->tipo_solicitud,
            ':det'       => $p->detalle ?? '',
            ':tipo_prod' => $p->tipo_prod ?? '',
            ':mesa'      => $data->numeroMesa,
            ':mesero'    => $data->id_mesero ?? null
        ]);
    }

    /* ─────────────────────  5. Comentario opcional  ─────────────────── */
    if (!empty($data->comentario)) {
        $db->prepare("
            INSERT INTO comentarios (id_pedido,comentario)
            VALUES (:id,:com)
        ")->execute([':id'=>$numero_pedido, ':com'=>$data->comentario]);
    }

    /* ─────────────────────  6. Actualizar mesa  ─────────────────────── */
    $db->prepare("
        UPDATE mesas
        SET estado='nuevo', id_pedido=:p, fecha=NOW()
        WHERE numero_mesa=:m
    ")->execute([':p'=>$numero_pedido, ':m'=>$data->numeroMesa]);

    /* ─────────────────────  7. Fin  ─────────────────────────────────── */
    $db->commit();

    http_response_code(201);
    echo json_encode([
        'success'       => true,
        'message'       => 'Pedido creado con éxito.',
        'numero_pedido' => $numero_pedido,
        'turno'         => $numero_turno
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('[post_pedido] '.$e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
