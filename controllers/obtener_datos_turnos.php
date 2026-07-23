<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$tipo_solicitud = isset($_GET['tipo_solicitud']) ? intval($_GET['tipo_solicitud']) : 0;
$since_raw = $_GET['since'] ?? '';

if ($tipo_solicitud === 0) {
    echo json_encode(array("error" => "Tipo de solicitud no proporcionado"));
    exit();
}

date_default_timezone_set('America/Bogota');
$fecha_actual = date('Y-m-d');

/* Marca de agua para el polling incremental. El cliente reenvía el valor
   'ahora' que este mismo endpoint le devolvió (reloj del SERVIDOR), lo que
   elimina los problemas de relojes desajustados o zonas horarias que hacían
   perder pedidos nuevos. Se acepta también el formato viejo en milisegundos
   por compatibilidad con pestañas abiertas con la versión anterior. */
if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $since_raw)) {
    $since_mysql = $since_raw;
} elseif (ctype_digit($since_raw) && (int) $since_raw > 0) {
    $since_mysql = date('Y-m-d H:i:s', ((int) $since_raw) / 1000);
} else {
    $since_mysql = null; // sincronización completa
}

/* La marca de agua se toma ANTES de consultar, para no perder filas que
   cambien mientras se arma la respuesta. */
$ahora_servidor = $db->query("SELECT NOW()")->fetchColumn();

/* ENRUTAMIENTO POR TIPO DE SOLICITUD (regla del negocio):
   - 52 pedidos de MESA (app)  → SOLO en la sección de mesas, NUNCA aquí
   - 51 para llevar            → tabla de turnos del dashboard
   - 50 domicilios             → whatsapp.php
   - 53 recoger                → llamadas.php
   Cada vista recibe exclusivamente su tipo. */
$tipos_sql = (string) $tipo_solicitud;

/* Se muestran los turnos de HOY y, además, los de días anteriores que
   sigan asociados a una mesa abierta y sin pagar. Sin esta condición,
   una mesa que quedó abierta de un día para otro desaparecía del panel
   y ya no se podía cobrar ni cerrar. */
$query = "
    SELECT t.id_t, t.id_pedido, t.turno, t.fecha, t.tipo_solicitud, t.estado,
           t.updated_at,
           c.cliente, c.celular, c.direccion, c.barrio,
           (SELECT COUNT(*) FROM caja WHERE id_pedidoc = t.id_pedido) AS pagado,
           (SELECT COUNT(*) FROM domicilios WHERE id_pedido = t.id_pedido AND id_domi IS NOT NULL) AS tiene_domiciliario,
           (SELECT COUNT(*) FROM domicilios WHERE id_pedido = t.id_pedido) AS tiene_precio
    FROM turnero t
    LEFT JOIN clientes c ON t.id_cliente = c.id
    WHERE t.tipo_solicitud IN ($tipos_sql)
    AND (
        DATE(t.fecha) = :fecha_actual
        OR (
            t.id_pedido IN (SELECT m.id_pedido FROM mesas m WHERE m.id_pedido IS NOT NULL)
            AND NOT EXISTS (SELECT 1 FROM caja cj WHERE cj.id_pedidoc = t.id_pedido)
        )
    )";

if ($since_mysql) {
    $query .= " AND t.updated_at > :since";
}

$stmt = $db->prepare($query);
$stmt->bindParam(':fecha_actual', $fecha_actual, PDO::PARAM_STR);
if ($since_mysql) {
    $stmt->bindParam(':since', $since_mysql, PDO::PARAM_STR);
}
$stmt->execute();

$turnos_arr = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $turnos_arr[] = [
        "id_t" => $row['id_t'],
        "numero_pedido" => $row['id_pedido'],
        "turno" => $row['turno'],
        "fecha" => $row['fecha'],
        "tipo_solicitud" => $row['tipo_solicitud'],
        "estado" => $row['estado'],
        "pagado" => $row['pagado'] > 0,
        "tiene_domiciliario" => $row['tiene_domiciliario'] > 0,
        "tiene_precio" => $row['tiene_precio'] > 0,
        "cliente" => $row['cliente'],
        "direccion" => $row['direccion'] ?? '',
        "barrio" => $row['barrio'] ?? '',
        "telefono" => $row['celular'],
        "updated_at" => $row['updated_at']
    ];
}

echo json_encode([
    "turnos" => $turnos_arr,
    "ahora"  => $ahora_servidor,
    "full"   => $since_mysql === null
]);
?>
