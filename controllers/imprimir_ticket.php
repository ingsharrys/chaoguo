<?php
// Mostrar errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php'; 

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

// Obtener el número de pedido desde JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['numero_pedido'])) {
    echo json_encode(['status' => 'error', 'message' => 'Número de pedido no proporcionado']);
    exit;
}

$numero_pedido = $input['numero_pedido'];

// 1) Obtener los detalles del pedido
$query_pedido = "
    SELECT 
        pr.nombre AS nombre_producto, 
        p.cantidad, 
        prp.precio AS precio_producto, 
        (p.cantidad * prp.precio) AS subtotal
    FROM pedidos p 
    JOIN productos pr ON p.id_pro = pr.id_pro
    JOIN precios prp  ON pr.id_pro = prp.idproduc 
    WHERE p.numero_pedido = ? 
";
$stmt_pedido = $conn->prepare($query_pedido);
$stmt_pedido->bindValue(1, $numero_pedido, PDO::PARAM_STR);
$stmt_pedido->execute();
$detalles_pedido = $stmt_pedido->fetchAll(PDO::FETCH_ASSOC);

// Validar si hay detalles de pedido
if (!$detalles_pedido) {
    echo json_encode(['status' => 'error', 'message' => 'Pedido no encontrado']);
    exit;
}

// Generar contenido del ticket
$ticket = "RESTAURANTE CHAO GUO\n";
$ticket .= "--------------------------------\n";
$ticket .= "PEDIDO N°: " . $numero_pedido . "\n";
$ticket .= "--------------------------------\n";
$ticket .= "Producto         Cant  Precio  Subtotal\n";
$ticket .= "--------------------------------\n";

$total = 0;
foreach ($detalles_pedido as $detalle) {
    $nombre = str_pad(substr($detalle['nombre_producto'], 0, 14), 14);
    $cantidad = str_pad($detalle['cantidad'], 3, ' ', STR_PAD_LEFT);
    $precio = str_pad(number_format($detalle['precio_producto'], 0, '', ','), 7, ' ', STR_PAD_LEFT);
    $subtotal = str_pad(number_format($detalle['subtotal'], 0, '', ','), 7, ' ', STR_PAD_LEFT);
    
    $ticket .= "$nombre $cantidad $precio $subtotal\n";
    $total += $detalle['subtotal'];
}

$ticket .= "--------------------------------\n";
$ticket .= "TOTAL: $" . number_format($total, 0, '', ',') . "\n";
$ticket .= "================================\n";

// 2) Enviar ticket a la impresora POS (ESC/POS con PHP)
try {
    $printer = fopen("COM3", "w"); // Cambia "COM3" por el puerto de tu impresora
    if (!$printer) {
        throw new Exception("No se pudo abrir la impresora");
    }
    
    fwrite($printer, chr(27) . "@"); // Reset de la impresora
    fwrite($printer, chr(27) . "!" . chr(1)); // Tamaño de fuente normal
    fwrite($printer, $ticket); // Enviar contenido del ticket
    fwrite($printer, chr(27) . "d" . chr(3)); // Espacios antes de cortar
    fwrite($printer, chr(27) . "m"); // Cortar papel
    fclose($printer);

    echo json_encode(['status' => 'success', 'message' => 'Ticket impreso']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
