<?php
/* ───────────────────────────  CABECERAS CORS  ────────────────────────── */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/post_pedido.log');

/* Rotación simple: si el log supera 5 MB se archiva y empieza uno nuevo.
   (Un log sin control llegó a 240 MB y agotó la memoria del hosting.) */
$logFile = __DIR__.'/post_pedido.log';
if (is_file($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
    @rename($logFile, $logFile.'.1');
}

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

/* ─────────────────────────────  CONEXIÓN  ────────────────────────────── */
$db = (new Database())->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $data = json_decode(file_get_contents("php://input"));

    /* ── Validación básica ── */
    if (empty($data->productos) || !is_array($data->productos) ||
        empty($data->numeroMesa) || empty($data->tipo_solicitud)) {
        throw new Exception("Se requieren 'productos', 'numeroMesa' y 'tipo_solicitud'.", 400);
    }

    $estado     = !empty($data->estado)     ? $data->estado     : 'nuevo';
    $id_cliente = !empty($data->id_cliente) ? $data->id_cliente : 1;
    /* La app envía id_mesero como cadena vacía cuando no se selecciona
       mesero; la columna es numérica y el modo estricto rechaza ''. */
    $id_mesero  = !empty($data->id_mesero)  ? (int) $data->id_mesero : null;

    /* ── Pre-cargar nombre y prefijo del catálogo ANTES de abrir la
       transacción: así los bloqueos de escritura duran lo mínimo.
       (nombre y prefijo se buscan en la BD — fuente confiable; si no se
       encuentra, se usa lo que envió la app) ── */
    $stmtProd  = $db->prepare("SELECT nombre, prefijo FROM productos WHERE id_pro = :id LIMIT 1");
    $catalogo  = [];
    foreach ($data->productos as $p) {
        if (($p->cantidad ?? 0) <= 0) {
            throw new Exception("Cantidad inválida en producto {$p->id_pro}", 422);
        }
        if (!isset($catalogo[$p->id_pro])) {
            $stmtProd->execute([':id' => $p->id_pro]);
            $catalogo[$p->id_pro] = $stmtProd->fetch(PDO::FETCH_ASSOC) ?: [];
        }
    }

    /* ─────────────────────  TRANSACCIÓN (corta)  ─────────────────────── */
    $db->beginTransaction();

    /* ─────────────────────  1. Generar numero_pedido  ──────────────────
       Usa tabla 'consecutivos': bloquea UNA sola fila, sin table‑lock   */
    $db->exec("
        UPDATE consecutivos
        SET valor = LAST_INSERT_ID(valor + 1)
        WHERE nombre = 'num_pedido'
    ");
    $numero_pedido = (int)$db->lastInsertId();      // ← nuevo ID único

    /* ─────────────────────  2. Generar número de turno  ───────────────
       Contador atómico por día+tipo (tabla turnos_contador). Reemplaza
       al SELECT MAX(turno) ... FOR UPDATE, que tomaba bloqueos sobre
       turnero y serializaba TODOS los pedidos del día (deadlocks y
       pedidos en cola en hora pico). Aquí solo se bloquea UNA fila
       puntual durante milisegundos.                                    */
    $db->prepare("
        INSERT INTO turnos_contador (fecha, tipo, valor)
        VALUES (CURDATE(), :tipo, LAST_INSERT_ID(1))
        ON DUPLICATE KEY UPDATE valor = LAST_INSERT_ID(valor + 1)
    ")->execute([':tipo' => $data->tipo_solicitud]);
    $numero_turno = (int)$db->lastInsertId();

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
       IMPORTANTE: el panel admin cruza los pedidos por id_cliente,
       id_pro, estado y estado_boton. Si estas columnas quedan vacías el
       pedido existe en la BD pero se muestra mal en el administrador.
       Por eso aquí se llenan todas.                                    */
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
        $prodInfo = $catalogo[$p->id_pro] ?? [];

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
            ':mesero'    => $id_mesero,
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
    /* PDOException::getCode() puede devolver un SQLSTATE tipo '42S22'
       (string); http_response_code solo acepta enteros HTTP válidos. */
    $codigo = (int) $e->getCode();
    http_response_code(($codigo >= 400 && $codigo < 600) ? $codigo : 500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
