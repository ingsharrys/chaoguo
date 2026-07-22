<?php
// Mostrar todos los errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar la zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Incluir el archivo de configuración de la base de datos
require_once '../config/database.php';

// Obtener la fecha actual en formato Y-m-d
$fecha_actual = date('Y-m-d');

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Funciones para obtener los detalles del pedido y otros datos relacionados
function obtenerDetallesPedido($conn, $numero_pedido) {
    $detalle_query = "SELECT pr.nombre AS nombre_producto, p.cantidad, prp.precio AS precio_producto, p.detalle, p.tipo_producto, p.mesa
                      FROM pedidos p 
                      JOIN productos pr ON p.producto = pr.nombre 
                      JOIN precios prp ON pr.id_pro = prp.idproduc 
                      WHERE p.numero_pedido = ? AND prp.tipo_prod = p.tipo_producto";
    $detalle_stmt = $conn->prepare($detalle_query);
    $detalle_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $detalle_stmt->execute();
    return $detalle_stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerEstadoPago($conn, $numero_pedido) {
    $pago_query = "SELECT COUNT(*) as count FROM caja WHERE id_pedidoc = ?";
    $pago_stmt = $conn->prepare($pago_query);
    $pago_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $pago_stmt->execute();
    return $pago_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
}

function obtenerCostoDomicilio($conn, $numero_pedido) {
    $domicilio_query = "SELECT precio FROM domicilios WHERE id_pedido = ?";
    $domicilio_stmt = $conn->prepare($domicilio_query);
    $domicilio_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $domicilio_stmt->execute();
    $domicilio = $domicilio_stmt->fetch(PDO::FETCH_ASSOC);
    return $domicilio ? $domicilio['precio'] : 0;
}

function obtenerComentarios($conn, $numero_pedido) {
    $comentarios_query = "SELECT comentario FROM comentarios WHERE id_pedido = (SELECT numero_pedido FROM pedidos WHERE numero_pedido = ? LIMIT 1)";
    $comentarios_stmt = $conn->prepare($comentarios_query);
    $comentarios_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $comentarios_stmt->execute();
    return $comentarios_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener los pedidos de la fecha actual de la base de datos para los turnos
$query_turnos = "SELECT p.numero_pedido, p.detalle, p.fecha, c.cliente, c.celular, c.email, c.direccion, p.estado, p.tipo_solicitud, p.estado_boton, p.mesa, t.turno
                 FROM pedidos p 
                 JOIN clientes c ON p.id_cliente = c.id
                 LEFT JOIN turnero t ON p.numero_pedido = t.id_pedido
                 WHERE DATE(p.fecha) = :fecha_actual
                 GROUP BY p.numero_pedido, p.detalle, p.fecha, c.cliente, c.celular, c.email, c.direccion, p.estado, p.tipo_solicitud, p.estado_boton, p.mesa, t.turno 
                 ORDER BY 
                     t.turno ASC, FIELD(p.estado_boton, 'nuevo', 'en_cocina', 'entregado'), p.fecha DESC";

$stmt_turnos = $conn->prepare($query_turnos);
$stmt_turnos->bindParam(':fecha_actual', $fecha_actual);
$stmt_turnos->execute();
$turnos = $stmt_turnos->fetchAll(PDO::FETCH_ASSOC);

// Añadir detalles adicionales a cada turno
foreach ($turnos as &$turno) {
    $turno['detalles'] = obtenerDetallesPedido($conn, $turno['numero_pedido']);
    $turno['costo_domicilio'] = obtenerCostoDomicilio($conn, $turno['numero_pedido']);
    $turno['comentarios'] = obtenerComentarios($conn, $turno['numero_pedido']);
    $turno['pagado'] = obtenerEstadoPago($conn, $turno['numero_pedido']);
}

// Obtener las mesas de la base de datos
$query_mesas = "SELECT m.numero_mesa, m.estado, m.id_pedido, IFNULL(c.cliente, 'Sin cliente') AS cliente 
                FROM mesas m 
                LEFT JOIN pedidos p ON m.id_pedido = p.numero_pedido 
                LEFT JOIN clientes c ON p.id_cliente = c.id";
$stmt_mesas = $conn->prepare($query_mesas);
$stmt_mesas->execute();
$mesas = $stmt_mesas->fetchAll(PDO::FETCH_ASSOC);

// Añadir el estado de pago para cada mesa
foreach ($mesas as &$mesa) {
    if (!empty($mesa['id_pedido'])) {
        $mesa['pagado'] = obtenerEstadoPago($conn, $mesa['id_pedido']);
    } else {
        $mesa['pagado'] = false; // No hay pedido asociado, no pagado
    }
}

// Preparar la respuesta JSON combinada
$response = [
    'turnos' => $turnos,
    'mesas' => $mesas
];

echo json_encode($response);
?>
