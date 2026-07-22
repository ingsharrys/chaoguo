<?php
// Iniciar el almacenamiento en búfer de salida para evitar cualquier salida no deseada
ob_start();

// Mostrar todos los errores de PHP para depuración (puedes desactivarlo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de configuración de la base de datos
require_once '../config/database.php';

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Verificar si el id_pedido se ha pasado correctamente
if (isset($_GET['id_pedido'])) {
    $id_pedido = $_GET['id_pedido'];

    // Consultar los datos del turno, el cliente y el estado del pedido desde la tabla turnero
    $queryTurnero = "
        SELECT t.estado, t.id_pedido, t.id_cliente, c.cliente, c.celular, c.direccion, c.barrio
        FROM turnero t
        JOIN clientes c ON t.id_cliente = c.id
        WHERE t.id_pedido = :id_pedido
    ";
    $stmtTurnero = $conn->prepare($queryTurnero);
    $stmtTurnero->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmtTurnero->execute();
    $turno = $stmtTurnero->fetch(PDO::FETCH_ASSOC);

    if (!$turno) {
        echo json_encode(['message' => 'No se encontraron datos del turno para este pedido.']);
        exit();
    }

    // Consulta para obtener los productos relacionados con el pedido y sus precios
    $queryProductos = "
        SELECT p.cantidad, p.detalle, pr.precio, pr.tipo_prod, prod.nombre AS nombre_producto
        FROM pedidos p
        JOIN precios pr ON p.id_pro = pr.idproduc AND p.tipo_producto = pr.tipo_prod
        JOIN productos prod ON p.id_pro = prod.id_pro
        WHERE p.numero_pedido = :id_pedido
    ";
    $stmtProductos = $conn->prepare($queryProductos);
    $stmtProductos->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmtProductos->execute();
    $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

    if (!$productos) {
        echo json_encode(['message' => 'No se encontraron productos para este pedido.']);
        exit();
    }

    // Consulta para verificar si el pedido está pagado en la tabla 'caja'
    $queryPago = "SELECT COUNT(*) as pagado FROM caja WHERE id_pedidoc = :id_pedido";
    $stmtPago = $conn->prepare($queryPago);
    $stmtPago->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmtPago->execute();
    $pago = $stmtPago->fetch(PDO::FETCH_ASSOC);
    $pagado = $pago['pagado'] > 0;

    // Consulta para obtener el costo de domicilio si ya está registrado
    $queryDomicilio = "
        SELECT d.precio, r.repartidor, r.celu_reparti
        FROM domicilios d
        LEFT JOIN domiciliarios r ON d.id_domi = r.id_e
        WHERE d.id_pedido = :id_pedido
    ";
    $stmtDomicilio = $conn->prepare($queryDomicilio);
    $stmtDomicilio->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmtDomicilio->execute();
    $domicilio = $stmtDomicilio->fetch(PDO::FETCH_ASSOC);

    $costo_domicilio = $domicilio ? $domicilio['precio'] : null;
    $domiciliario = $domicilio ? [
        'repartidor' => $domicilio['repartidor'],
        'celu_reparti' => $domicilio['celu_reparti']
    ] : null;

    // Consulta para obtener el comentario de la tabla comentarios
    $queryComentario = "SELECT comentario FROM comentarios WHERE id_pedido = :id_pedido";
    $stmtComentario = $conn->prepare($queryComentario);
    $stmtComentario->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmtComentario->execute();
    $comentario = $stmtComentario->fetch(PDO::FETCH_ASSOC);

    // Devolver todos los datos
    echo json_encode([
        'cliente' => [
            'nombre' => $turno['cliente'],
            'celular' => $turno['celular'],
            'direccion' => $turno['direccion'],
            'barrio' => $turno['barrio']
        ],
        'productos' => $productos,
        'estado' => $turno['estado'],
        'numero_pedido' => $turno['id_pedido'],
        'pagado' => $pagado,
        'costo_domicilio' => $costo_domicilio,
        'domiciliario' => $domiciliario,
        'comentario' => $comentario ? $comentario['comentario'] : null  // Incluir el comentario en la respuesta
    ]);
} else {
    echo json_encode(['message' => 'ID de pedido no proporcionado.']);
}

ob_end_flush();
