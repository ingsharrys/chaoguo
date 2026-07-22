<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo de preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

// Convertir el body JSON a objeto PHP
$data = json_decode(file_get_contents("php://input"));
date_default_timezone_set('America/Bogota');

// Iniciar la conexión con la base de datos
$database = new Database();
$db = $database->getConnection();

// Array para devolver la respuesta final
$response = [];

/*
  Validamos que la petición tenga:
  - $data->productos: array de objetos (id_pro, cantidad, tipo_prod, detalle opcional)
  - $data->numeroMesa: la mesa a la que se le asigna
  - $data->tipo_solicitud: valor que indique si es 52 (mesas) u otro
*/

// Validación de campos básicos
if (!empty($data->productos) && !empty($data->numeroMesa) && !empty($data->tipo_solicitud)) {

    // 1) Obtener el máximo numero_pedido actual en "pedidos"
    $queryMax = "SELECT COALESCE(MAX(numero_pedido), 0) AS max_pedido FROM pedidos";
    $stmtMax  = $db->prepare($queryMax);
    $stmtMax->execute();
    $rowMax        = $stmtMax->fetch(PDO::FETCH_ASSOC);
    $numero_pedido = $rowMax['max_pedido'] + 1;

    // ================================
    // 2) Crear el registro (PADRE) en tabla turnero
    // ================================
    // Generar el próximo turno para la fecha actual y tipo_solicitud
    $fechaActual = date('Y-m-d'); // Solo la fecha, sin hora
    $queryMaxTurno = "
        SELECT COALESCE(MAX(turno), 0) AS max_turno
        FROM turnero
        WHERE DATE(fecha) = :fecha_actual
          AND tipo_solicitud = :tipo_solicitud
    ";
    $stmtMaxT = $db->prepare($queryMaxTurno);
    $stmtMaxT->execute([
        ':fecha_actual'   => $fechaActual,
        ':tipo_solicitud' => $data->tipo_solicitud
    ]);
    $rowMaxT     = $stmtMaxT->fetch(PDO::FETCH_ASSOC);
    $numero_turno = $rowMaxT['max_turno'] + 1;

    // Insertar en turnero
    $queryT = "
        INSERT INTO turnero (id_pedido, turno, fecha, tipo_solicitud, estado, id_cliente)
        VALUES (:id_pedido, :turno, NOW(), :tipo_solicitud, :estado, :id_cliente)
    ";
    $stmtT = $db->prepare($queryT);

    // Establece un estado por defecto y cliente (si no están en $data)
    $estado    = !empty($data->estado)     ? $data->estado     : 'nuevo';
    $id_cliente= !empty($data->id_cliente) ? $data->id_cliente : 1;

    $stmtT->execute([
        ':id_pedido'      => $numero_pedido,
        ':turno'          => $numero_turno,
        ':tipo_solicitud' => $data->tipo_solicitud,
        ':estado'         => $estado,
        ':id_cliente'     => $id_cliente
    ]);

    // ================================
    // 3) Insertar productos (HIJO) en tabla "pedidos"
    // ================================
    $queryP = "
        INSERT INTO pedidos
        (id_pro, cantidad, numero_pedido, tipo_solicitud, detalle, tipo_producto, mesa, mesero, fecha)
        VALUES
        (:id_pro, :cantidad, :numero_pedido, :tipo_solicitud, :detalle, :tipo_producto, :mesa, :mesero, NOW())
    ";
    $stmtP = $db->prepare($queryP);

    foreach ($data->productos as $producto) {

        $id_pro        = !empty($producto->id_pro)    ? $producto->id_pro    : 0;
        $cantidad      = !empty($producto->cantidad)  ? $producto->cantidad  : 0;
        $detalle       = !empty($producto->detalle)   ? $producto->detalle   : '';
        $tipo_producto = !empty($producto->tipo_prod) ? $producto->tipo_prod : '';
        $mesero        = !empty($data->id_mesero)     ? $data->id_mesero     : null;

        // Asignar parámetros
        $stmtP->bindParam(':id_pro',         $id_pro);
        $stmtP->bindParam(':cantidad',       $cantidad);
        $stmtP->bindParam(':numero_pedido',  $numero_pedido);
        $stmtP->bindParam(':tipo_solicitud', $data->tipo_solicitud);
        $stmtP->bindParam(':detalle',        $detalle);
        $stmtP->bindParam(':tipo_producto',  $tipo_producto);
        $stmtP->bindParam(':mesa',           $data->numeroMesa);
        $stmtP->bindParam(':mesero',         $mesero);

        if (!$stmtP->execute()) {
            // Capturar error si falla
            $errorInfo = $stmtP->errorInfo();
            $response[] = [
                "error_pedidos" => "No se pudo insertar product (id_pro=$id_pro) en pedido=$numero_pedido",
                "error_info"    => $errorInfo[2]
            ];
        } else {
            $response[] = [
                "insert_pedidos" => "Producto ($id_pro) agregado al pedido $numero_pedido"
            ];
        }
    }

    // ================================
    // [OPCIONAL] Insertar comentario (tabla "comentarios")
    // ================================
    if (!empty($data->comentario)) {
        $queryC = "INSERT INTO comentarios (id_pedido, comentario) VALUES (:pedido, :comment)";
        $stmtC  = $db->prepare($queryC);
        $stmtC->execute([
            ':pedido'  => $numero_pedido,
            ':comment' => $data->comentario
        ]);
        $response[] = [ "comentario" => "Comentario agregado al pedido $numero_pedido"];
    }

    // ================================
    // 4) Actualizar la tabla "mesas"
    // ================================
    $updateMesa = "
        UPDATE mesas
        SET estado = 'nuevo',
            id_pedido = :num_ped,
            fecha = NOW()
        WHERE numero_mesa = :num_mesa
    ";
    $stmtMesa = $db->prepare($updateMesa);
    $stmtMesa->bindParam(':num_ped',  $numero_pedido);
    $stmtMesa->bindParam(':num_mesa', $data->numeroMesa);
    $stmtMesa->execute();

    // Respuesta final: 201
    http_response_code(201);
    $response[] = [
        "message"         => "Pedido creado",
        "numero_pedido"   => $numero_pedido,
        "turno"           => $numero_turno
    ];
    echo json_encode($response);

} else {
    http_response_code(400);
    echo json_encode([
        "message" => "Datos incompletos. Se requieren 'productos', 'numeroMesa' y 'tipo_solicitud'."
    ]);
}
?>
