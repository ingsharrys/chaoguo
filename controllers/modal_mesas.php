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

function obtenerProductosPedido($conn, $numero_pedido) {
    $productos_query = "SELECT producto, prefijos, cantidad, detalle, tipo_producto FROM pedidos WHERE numero_pedido = ?";
    $productos_stmt = $conn->prepare($productos_query);
    $productos_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $productos_stmt->execute();
    return $productos_stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerEstadoPago($conn, $numero_pedido) {
    // Consulta para verificar si existe el numero_pedido en la tabla caja
    $pago_query = "SELECT COUNT(*) as count FROM caja WHERE id_pedidoc = ?";
    $pago_stmt = $conn->prepare($pago_query);
    $pago_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $pago_stmt->execute();
    
    // Devuelve verdadero si el conteo es mayor a cero, de lo contrario falso
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

function obtenerDatosPedido($conn, $numero_pedido) {
    $pedido_query = "SELECT p.numero_pedido, p.detalle, p.fecha, c.cliente, c.celular, c.email, c.direccion, p.estado, p.tipo_solicitud, p.estado_boton, p.mesa, t.turno
                     FROM pedidos p 
                     JOIN clientes c ON p.id_cliente = c.id
                     LEFT JOIN turnero t ON p.numero_pedido = t.id_pedido
                     WHERE p.numero_pedido = ? 
                     LIMIT 1";
    $pedido_stmt = $conn->prepare($pedido_query);
    $pedido_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $pedido_stmt->execute();
    $pedido = $pedido_stmt->fetch(PDO::FETCH_ASSOC);

    // Añadir detalles adicionales al pedido
    if ($pedido) {
        $pedido['detalles'] = obtenerDetallesPedido($conn, $numero_pedido);
        $pedido['productos'] = obtenerProductosPedido($conn, $numero_pedido); // Obtener productos
        $pedido['costo_domicilio'] = obtenerCostoDomicilio($conn, $numero_pedido);
        $pedido['comentarios'] = obtenerComentarios($conn, $numero_pedido);
        $pedido['pagado'] = obtenerEstadoPago($conn, $numero_pedido);
    }

    return $pedido;
}

// Verifica si se ha proporcionado un número de pedido
if (isset($_GET['numero_pedido'])) {
    $numero_pedido = $_GET['numero_pedido'];

    // Obtener datos del pedido específico
    $pedido = obtenerDatosPedido($conn, $numero_pedido);

    // Preparar la respuesta JSON para un solo pedido
    echo json_encode($pedido);
} else {
    // Resto del código existente para obtener todos los pedidos de la fecha actual y las mesas...
}
?>
