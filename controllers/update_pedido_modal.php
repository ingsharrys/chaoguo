<?php
// Encabezados CORS
header("Access-Control-Allow-Origin: *"); // Permite cualquier origen
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");

// Manejo de preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../objects/pedido_actualizado.php';

try {
    // 1) Conectar a la base de datos
    $database = new Database();
    $db       = $database->getConnection();

    // 2) Instanciar la clase Pedido
    $pedido   = new Pedido($db);

    // 3) Obtener el body JSON
    $data = json_decode(file_get_contents("php://input"));

    // 4) Array para la respuesta final
    $response = [];

    /*
      Validamos que vengan:
      - $data->productos (array)
      - $data->numeroMesa
      - $data->estado        (si la tabla `pedidos` tiene la columna `estado`)
      - $data->comentario    (puede ser vacío)
      - $data->tipo_solicitud
      - $data->numero_pedido (para saber cuál pedido actualizar)
    */
    if (
        !empty($data->productos) &&
        !empty($data->numeroMesa) &&
        !empty($data->estado) &&
        isset($data->comentario) &&
        !empty($data->tipo_solicitud) &&
        !empty($data->numero_pedido)
    ) {
        // Número de pedido existente
        $numero_pedido = $data->numero_pedido;

        // Recorrer cada producto
        foreach ($data->productos as $producto) {
            // 1) Verificar si ya existe en 'pedidos' (id_pro + numero_pedido)
            if ($pedido->checkIfProductExists($producto->id_pro, $numero_pedido)) {
                // 1.1) Actualizar producto existente
                if ($pedido->updateProduct($producto, $numero_pedido)) {
                    $response[] = [
                        "message" => "Producto (id_pro={$producto->id_pro}) actualizado exitosamente."
                    ];
                } else {
                    $response[] = [
                        "message" => "No se pudo actualizar (id_pro={$producto->id_pro}).",
                        "error"   => $pedido->getLastError()
                    ];
                }
            } else {
                // 1.2) Insertar producto nuevo en 'pedidos' (este es el "INSERT")
                // Llenar propiedades de la clase $pedido
                $pedido->id_produ       = $producto->id_pro;
                $pedido->cantidad       = $producto->cantidad ?? 1;
                $pedido->numero_pedido  = $numero_pedido;
                $pedido->estado         = $data->estado; // Solo si en tu DB existe la columna 'estado'
                $pedido->tipo_solicitud = $data->tipo_solicitud;
                $pedido->detalle        = $producto->detalle ?? '';
                $pedido->tipo_producto  = $producto->tipo_prod ?? '';
                $pedido->mesa           = $producto->mesa ?? $data->numeroMesa;
                $pedido->mesero         = $data->mesero ?? null;

                // AQUÍ se llama al método 'create()' que hace el INSERT
                if ($pedido->create()) {
                    $response[] = [
                        "message" => "Producto (id_pro={$producto->id_pro}) creado exitosamente."
                    ];
                } else {
                    $response[] = [
                        "message" => "No se pudo crear (id_pro={$producto->id_pro}).",
                        "error"   => $pedido->getLastError()
                    ];
                }
            }
        }

        // 2) Guardar el comentario (si no está vacío)
        if (!empty($data->comentario)) {
            if ($pedido->createComment($numero_pedido, $data->comentario)) {
                $response[] = ["message" => "Comentario guardado."];
            } else {
                $response[] = [
                    "message" => "No se pudo guardar el comentario.",
                    "error"   => $pedido->getLastError()
                ];
            }
        }

        // 3) Respuesta final con código 201
        http_response_code(201);
        echo json_encode($response);

    } else {
        // Faltan campos
        http_response_code(400);
        echo json_encode([
            "message" => "Datos incompletos para actualizar el pedido."
        ]);
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Error en el servidor.",
        "error"   => $e->getMessage()
    ]);
}
