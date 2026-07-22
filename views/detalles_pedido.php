<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!isset($data['numero_pedido'])) {
            throw new Exception('N¨²mero de pedido no especificado');
        }

        $numero_pedido = $data['numero_pedido'];

        $db = new Database();
        $conn = $db->getConnection();

        // Obtener informaci¨®n del pedido, cliente y numero_pedido
        $query = "SELECT p.numero_pedido, p.fecha, c.cliente, c.celular, c.email, c.direccion
                  FROM pedidos p
                  JOIN clientes c ON p.id_cliente = c.id
                  WHERE p.numero_pedido = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        $stmt->bind_param("s", $numero_pedido);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $pedido = $result->fetch_assoc();

        if ($pedido) {
            // Obtener los productos del pedido usando el numero_pedido
            $query = "SELECT pr.nombre, p.cantidad, pr.precio
                      FROM pedidos p
                      JOIN productos pr ON p.producto = pr.nombre
                      WHERE p.numero_pedido = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta de productos: " . $conn->error);
            }
            $stmt->bind_param("s", $numero_pedido);
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta de productos: " . $stmt->error);
            }
            $result = $stmt->get_result();
            $productos = $result->fetch_all(MYSQLI_ASSOC);

            // Calcular el total
            $total = 0;
            foreach ($productos as &$producto) {
                $producto['subtotal'] = $producto['precio'] * $producto['cantidad'];
                $total += $producto['subtotal'];
            }

            $pedido['productos'] = $productos;
            $pedido['total'] = $total;

            echo json_encode(['status' => 'success', 'data' => $pedido]);
        } else {
            throw new Exception('Pedido no encontrado');
        }
    } else {
        throw new Exception('M¨¦todo no permitido');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
