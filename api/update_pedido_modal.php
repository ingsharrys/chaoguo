<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../objects/pedido_actualizado.php';

try {
    $db = (new Database())->getConnection();
    $pedido = new Pedido($db);
    $data = json_decode(file_get_contents("php://input"));

    if (
        !isset($data->productos) || !is_array($data->productos) || empty($data->productos) ||
        empty($data->numeroMesa) ||
        !isset($data->comentario) ||
        empty($data->estado) ||
        empty($data->tipo_solicitud) ||
        empty($data->numero_pedido)
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Datos incompletos para actualizar el pedido."
        ]);
        exit();
    }

    $numero_pedido = $data->numero_pedido;
    $response = [];

    /* Contexto del pedido existente (mesero, cliente, estados) para que las
       filas nuevas queden completas y visibles en el panel admin. */
    $stmtCtx = $db->prepare("
        SELECT mesero, id_cliente, estado, estado_boton
        FROM pedidos
        WHERE numero_pedido = :np
        ORDER BY id_pedido DESC
        LIMIT 1
    ");
    $stmtCtx->execute([':np' => $numero_pedido]);
    $ctx = $stmtCtx->fetch(PDO::FETCH_ASSOC) ?: [];

    /* Consulta reutilizable para nombre y prefijo del producto */
    $stmtProd = $db->prepare("SELECT nombre, prefijo FROM productos WHERE id_pro = :id LIMIT 1");

    foreach ($data->productos as $producto) {
        if (!isset($producto->id_pro)) continue;

        if ($pedido->checkIfProductExists($producto->id_pro, $numero_pedido, $producto->tipo_prod ?? null)) {
            $ok = $pedido->updateProduct($producto, $numero_pedido);
            $response[] = [
                "id_pro"  => $producto->id_pro,
                "accion"  => "update",
                "success" => $ok,
                "error"   => $ok ? null : $pedido->getLastError()
            ];
        } else {
            $stmtProd->execute([':id' => $producto->id_pro]);
            $prodInfo = $stmtProd->fetch(PDO::FETCH_ASSOC) ?: [];

            $pedido->id_produ       = $producto->id_pro;
            $pedido->producto       = $prodInfo['nombre']  ?? ($producto->nombre  ?? '');
            $pedido->prefijos       = $prodInfo['prefijo'] ?? ($producto->prefijo ?? '');
            $pedido->cantidad       = $producto->cantidad ?? 1;
            $pedido->numero_pedido  = $numero_pedido;
            $pedido->estado         = $ctx['estado'] ?? ($data->estado ?: 'nuevo');
            $pedido->estado_boton   = $ctx['estado_boton'] ?? 'nuevo';
            $pedido->tipo_solicitud = $data->tipo_solicitud;
            $pedido->detalle        = $producto->detalle ?? '';
            $pedido->tipo_producto  = $producto->tipo_prod ?? '';
            $pedido->mesa           = $producto->mesa ?? $data->numeroMesa;
            /* la columna mesero guarda el id del mesero (id_mese); se toma
               del pedido existente porque la app envía el nombre aquí */
            $pedido->mesero         = $ctx['mesero'] ?? ($data->id_mesero ?? null);
            $pedido->id_cliente     = $ctx['id_cliente'] ?? 1;

            $ok = $pedido->create();
            $response[] = [
                "id_pro"  => $producto->id_pro,
                "accion"  => "insert",
                "success" => $ok,
                "error"   => $ok ? null : $pedido->getLastError()
            ];
        }
    }

    if (!empty($data->comentario)) {
        $ok = $pedido->createComment($numero_pedido, $data->comentario);
        $response[] = [
            "comentario" => $data->comentario,
            "success" => $ok,
            "error" => $ok ? null : $pedido->getLastError()
        ];
    }

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "numero_pedido" => $numero_pedido,
        "resultados" => $response
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error en el servidor.",
        "error" => $e->getMessage()
    ]);
}
