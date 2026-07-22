<?php
// Mostrar todos los errores de PHP
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

    // Consulta para obtener los datos del cliente basado en el id_cliente relacionado desde la tabla turnero
    $queryCliente = "SELECT c.cliente, c.celular, c.direccion, c.barrio
                     FROM turnero t
                     JOIN clientes c ON t.id_cliente = c.id
                     WHERE t.id_pedido = :id_pedido";

    $stmtCliente = $conn->prepare($queryCliente);
    $stmtCliente->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmtCliente->execute();

    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);  // Obtener un solo registro del cliente

    // Consulta para obtener los productos relacionados con el pedido y sus precios
    $queryProductos = "SELECT p.cantidad, p.detalle, pr.precio, pr.tipo_prod, prod.prefijo, prod.nombre AS nombre_producto
                       FROM pedidos p
                       JOIN precios pr ON p.id_pro = pr.idproduc AND p.tipo_producto = pr.tipo_prod
                       JOIN productos prod ON p.id_pro = prod.id_pro
                       WHERE p.numero_pedido = :id_pedido";

    $stmtProductos = $conn->prepare($queryProductos);
    $stmtProductos->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmtProductos->execute();

    $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);  // Obtener todos los productos relacionados

    // Consulta para obtener el estado del turno en la tabla 'turnero'
    $queryTurnero = "SELECT estado, id_pedido FROM turnero WHERE id_pedido = :id_pedido";
    $stmtTurnero = $conn->prepare($queryTurnero);
    $stmtTurnero->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmtTurnero->execute();

    $turno = $stmtTurnero->fetch(PDO::FETCH_ASSOC);  // Obtener el estado del turno

    // Consulta para verificar si el pedido está pagado en la tabla 'caja'
    $queryPago = "SELECT COUNT(*) as pagado FROM caja WHERE id_pedidoc = :id_pedido";
    $stmtPago = $conn->prepare($queryPago);
    $stmtPago->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmtPago->execute();

    $pago = $stmtPago->fetch(PDO::FETCH_ASSOC);  // Verificar si hay un registro en la tabla 'caja'
    $pagado = $pago['pagado'] > 0;  // Si hay registros, el pedido está pagado


    // Consulta para obtener el comentario de la tabla comentarios
    $queryComentario = "SELECT comentario FROM comentarios WHERE id_pedido = :id_pedido";
    $stmtComentario = $conn->prepare($queryComentario);
    $stmtComentario->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmtComentario->execute();

    $comentario = $stmtComentario->fetch(PDO::FETCH_ASSOC);  // Obtener el comentario
    
    
    
$queryDomicilio = "SELECT precio FROM domicilios WHERE id_pedido = :id_pedido LIMIT 1";
$stmtDomicilio = $conn->prepare($queryDomicilio);
$stmtDomicilio->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
$stmtDomicilio->execute();
$rowDomicilio = $stmtDomicilio->fetch(PDO::FETCH_ASSOC);

$costoDomicilio = $rowDomicilio ? $rowDomicilio['precio'] : null;

    // Verificar si se encontraron datos
    if ($cliente && $turno) {
        // Enviar los datos del cliente, los productos, el estado del turno, si está pagado o no, y el comentario
        echo json_encode([
            'cliente' => $cliente,
            'productos' => $productos,
            'estado' => $turno['estado'],  // Estado del turno
            'numero_pedido' => $turno['id_pedido'],
            'pagado' => $pagado,  // Si el pedido está pagado
            'comentario' => $comentario ? $comentario['comentario'] : null,
            'costo_domicilio' => $costoDomicilio// Comentario del pedido
        ]);
    } else {
        echo json_encode(['message' => 'No se encontraron datos para este pedido.']);
    }
} else {
    echo json_encode(['message' => 'ID de pedido no proporcionado.']);
}
?>
