<?php
require_once '../config/database.php';

// Obtener el teléfono desde la solicitud AJAX
$celular = isset($_GET['celular']) ? $_GET['celular'] : null;

if ($celular) {
    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // Consulta SQL para obtener los pedidos pendientes del cliente
    $queryPedidos = "SELECT p.id_pedido, p.producto, p.cantidad, p.fecha, t.estado, t.turno
                     FROM pedidos p
                     INNER JOIN turnero t ON p.numero_pedido = t.id_pedido
                     WHERE p.id_cliente = (SELECT id FROM clientes WHERE celular = ?) AND DATE(p.fecha) = CURDATE()";
    
    $stmtPedidos = $conn->prepare($queryPedidos);
    $stmtPedidos->execute([$celular]);
    $pedidosPendientes = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($pedidosPendientes);
} else {
    echo json_encode([]);
}
?>
