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

    /* ─────────────────────  4. Insertar cada producto  ────────────────
       IMPORTANTE: el panel admin lee los pedidos con
       "JOIN clientes ON pedidos.id_cliente = clientes.id" y
       "JOIN productos ON pedidos.producto = productos.nombre",
       y filtra/ordena por estado y estado_boton. Si estas columnas
       quedan vacías el pedido existe en la BD pero NUNCA aparece en
       el administrador. Por eso aquí se llenan todas.               */
    $estado     = !empty($data->estado)     ? $data->estado     : 'nuevo';
    $id_cliente = !empty($data->id_cliente) ? $data->id_cliente : 1;

    $stmtProd = $db->prepare("SELECT nombre, prefijo FROM productos WHERE id_pro = :id LIMIT 1");

    $stmtP = $db->prepare("
        INSERT INTO pedidos
        (id_cliente,id_pro,producto,prefijos,cantidad,numero_pedido,
         tipo_solicitud,detalle,tipo_producto,mesa,mesero,
         estado,estado_boton,fecha)
        VALUES
        (:id_cli,:id_pro,:producto,:prefijos,:cant,:num_ped,
         :tipo,:det,:tipo_prod,:mesa,:mesero,
         :estado,'nuevo',NOW())
    ");

    foreach ($data->productos as $p) {
        if (($p->cantidad ?? 0) <= 0) {
            throw new Exception("Cantidad inválida en producto {$p->id_pro}", 422);
        }

        /* nombre y prefijo se buscan en la BD (fuente confiable);
           si no se encuentra, se usa lo que envió la app */
        $stmtProd->execute([':id' => $p->id_pro]);
        $prodInfo = $stmtProd->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmtP->execute([
            ':id_cli'    => $id_cliente,
            ':id_pro'    => $p->id_pro,
            ':producto'  => $prodInfo['nombre']  ?? ($p->nombre  ?? ''),
            ':prefijos'  => $prodInfo['prefijo'] ?? ($p->prefijo ?? ''),
            ':cant'      => $p->cantidad,
            ':num_ped'   => $numero_pedido,
            ':tipo'      => $data->tipo_solicitud,
            ':det'       => $p->detalle ?? '',
            ':tipo_prod' => $p->tipo_prod ?? '',
            ':mesa'      => $data->numeroMesa,
            ':mesero'    => $data->id_mesero ?? null,
            ':estado'    => $estado
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
