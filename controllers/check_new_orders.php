<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Consultar nuevos pedidos no confirmados, ordenados por fecha en orden descendente
    $query = "SELECT DISTINCT p.numero_pedido, p.fecha, c.cliente, c.celular, c.email, c.direccion 
              FROM pedidos p 
              JOIN clientes c ON p.id_cliente = c.id 
              WHERE p.estado = 'nuevo'
              ORDER BY p.fecha DESC";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($result) {
        $orders = [];
        foreach ($result as $order) {
            $numero_pedido = $order['numero_pedido'];
            $detalles_query = "SELECT pr.nombre, p.cantidad, pr.precio 
                               FROM pedidos p 
                               JOIN productos pr ON p.producto = pr.nombre 
                               WHERE p.numero_pedido = ?";
            $detalles_stmt = $conn->prepare($detalles_query);
            $detalles_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
            $detalles_stmt->execute();
            $detalles = $detalles_stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = 0;
            foreach ($detalles as &$detalle) {
                $detalle['subtotal'] = $detalle['precio'] * $detalle['cantidad'];
                $total += $detalle['subtotal'];
            }

            // Obtener el costo del domicilio
            $domicilio_query = "SELECT precio FROM domicilios WHERE id_pedido = ?";
            $domicilio_stmt = $conn->prepare($domicilio_query);
            $domicilio_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
            $domicilio_stmt->execute();
            $domicilio = $domicilio_stmt->fetch(PDO::FETCH_ASSOC);
            $costo_domicilio = $domicilio ? floatval($domicilio['precio']) : 0;

            // Calcular el total con el costo del domicilio
            $total_con_domicilio = $total + $costo_domicilio;

            $order['detalles'] = $detalles;
            $order['total'] = $total;
            $order['costo_domicilio'] = $costo_domicilio;
            $order['total_con_domicilio'] = $total_con_domicilio;
            $orders[] = $order;
        }

        echo json_encode(['status' => 'new_order', 'orders' => $orders]);
    } else {
        echo json_encode(['status' => 'no_new_order']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
