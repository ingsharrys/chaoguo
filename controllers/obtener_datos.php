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

// Función para verificar si el pedido está pagado
function pedidoEstaPagado($conn, $numero_pedido) {
    $query = "SELECT COUNT(*) as count FROM caja WHERE id_pedidoc = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
}

// Función para obtener productos relacionados con un numero_pedido y sus precios
function obtenerProductosPorNumeroPedido($conn, $numero_pedido) {
    $query = "SELECT p.cantidad, p.detalle, p.tipo_producto, p.mesa, pr.precio, prod.nombre 
              FROM pedidos p
              JOIN precios pr ON p.id_pro = pr.idproduc AND p.tipo_producto = pr.tipo_prod
              JOIN productos prod ON p.id_pro = prod.id_pro
              WHERE p.numero_pedido = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $numero_pedido, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener el nombre del mesero según el ID del mesero
function obtenerNombreMesero($conn, $id_mese) {
    $query = "SELECT nombre_mese FROM meseros WHERE id_mese = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $id_mese, PDO::PARAM_INT);
    $stmt->execute();
    $mesero = $stmt->fetch(PDO::FETCH_ASSOC);
    return $mesero ? $mesero['nombre_mese'] : 'No asignado';  // Devuelve el nombre del mesero o "No asignado"
}

// Función para obtener comentarios de la tabla comentarios relacionados con el pedido
function obtenerComentarios($conn, $numero_pedido) {
    $query = "SELECT comentario FROM comentarios WHERE id_pedido = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $numero_pedido, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN); // Devuelve solo los valores de la columna "comentario"
}

// Función para obtener los detalles de un pedido específico, incluyendo comentarios
function obtenerDatosPedido($conn, $numero_pedido) {
    $query = "SELECT p.*, m.numero_mesa 
              FROM pedidos p 
              LEFT JOIN mesas m ON p.mesa = m.idm 
              WHERE p.numero_pedido = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $numero_pedido, PDO::PARAM_INT);
    $stmt->execute();
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pedido) {
        // Obtener productos relacionados con el pedido y sus precios
        $pedido['productos'] = obtenerProductosPorNumeroPedido($conn, $numero_pedido);
        // Verificar si el pedido está pagado
        $pedido['pagado'] = pedidoEstaPagado($conn, $numero_pedido);
        // Obtener el nombre del mesero si existe
        $pedido['nombre_mesero'] = obtenerNombreMesero($conn, $pedido['mesero'] ?? 0);
        // Obtener los comentarios del pedido
        $pedido['comentarios'] = obtenerComentarios($conn, $numero_pedido);
    }

    return $pedido;
}



// Obtener mesas que están libres (no tienen pedidos asignados)
$query_mesas_libres = "SELECT numero_mesa FROM mesas WHERE id_pedido IS NULL OR id_pedido = ''";
$stmt_mesas_libres = $conn->prepare($query_mesas_libres);
$stmt_mesas_libres->execute();
$mesas_libres = $stmt_mesas_libres->fetchAll(PDO::FETCH_ASSOC);

// Si no hay mesas libres, aseguramos que la variable no sea null
if (!$mesas_libres) {
    $mesas_libres = [];
}

// Verificar si se ha pasado un numero_pedido en la solicitud GET
if (isset($_GET['numero_pedido'])) {
    $numero_pedido = $_GET['numero_pedido'];
    $pedido = obtenerDatosPedido($conn, $numero_pedido);

    // Incluir mesas libres en la respuesta del pedido
    $pedido['mesas_libres'] = $mesas_libres;

    // Devolver los detalles del pedido y los productos como JSON
    header('Content-Type: application/json');
    echo json_encode($pedido);
    exit;
}

// Obtener datos de la tabla 'turnero'
// Solo turnos de hoy y que NO estén entregados + pagados
$query_turnero = "
    SELECT  t.id_pedido,
            t.turno,
            t.estado
    FROM    turnero t
    LEFT JOIN caja c           ON c.id_pedidoc = t.id_pedido
    WHERE   DATE(t.fecha) = CURDATE()
      AND  NOT (t.estado = 'entregado' AND c.id_pedidoc IS NOT NULL)
";

$stmt_turnero = $conn->prepare($query_turnero);
$stmt_turnero->execute();
$turnos = $stmt_turnero->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos de la tabla 'mesas'
$query_mesas = "
    SELECT
        m.numero_mesa,
        COALESCE(m.estado,'')                                         AS estado,
        m.id_pedido,
        m.fecha,
        CASE
            WHEN EXISTS (SELECT 1 FROM caja WHERE id_pedidoc = m.id_pedido)
            THEN 1 ELSE 0
        END                                                           AS pagado
    FROM mesas m
    
    WHERE (m.id_pedido IS NULL)
       OR (DATE(m.fecha) = CURDATE())

    
      -- AND NOT (m.estado = 'entregado'
      --           AND EXISTS (SELECT 1 FROM caja WHERE id_pedidoc = m.id_pedido))

    ORDER BY m.numero_mesa
";



$stmt_mesas = $conn->prepare($query_mesas);
$stmt_mesas->execute();
$mesas = $stmt_mesas->fetchAll(PDO::FETCH_ASSOC);


foreach ($mesas as &$mesa) {
    $mesa['pagado'] = (int)$mesa['pagado'];  // Convertir a entero (1 o 0)
}
unset($mesa);



// Preparar la respuesta JSON incluyendo mesas libres
$response = [
    'turnos' => $turnos ?? [],  // Asegurar que `turnos` siempre sea un array
    'mesas' => $mesas ?? [],  // Asegurar que `mesas` siempre sea un array
    'mesas_libres' => $mesas_libres // Ahora siempre tiene un valor (puede ser vacío pero existe)
];

// Establecer el encabezado de respuesta como JSON
header('Content-Type: application/json');

// Imprimir la respuesta JSON
echo json_encode($response);
exit;
?>