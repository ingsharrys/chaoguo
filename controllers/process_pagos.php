<?php
require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Establecer la zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Obtener la fecha y hora actual de Colombia
$fecha_actual = date('Y-m-d H:i:s');

// Verificar si se recibieron datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Verificar si todos los datos necesarios están presentes
    if (isset($data['pedidos']) && is_array($data['pedidos'])) {
        $errores = [];
        
        // Iterar sobre cada pedido recibido
        foreach ($data['pedidos'] as $pedido) {
            if (isset($pedido['id_pedidoc']) && isset($pedido['costo']) && isset($pedido['m_pago']) && isset($pedido['cajero'])) {
                $query = "INSERT INTO caja (id_pedidoc, costo, m_pago, efectivo, banco, referencia, cajero, fecha_caja, id_cajero) 
                          VALUES (:id_pedidoc, :costo, :m_pago, :efectivo, :banco, :referencia, :cajero, :fecha_caja, :id_cajero)";

                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id_pedidoc', $pedido['id_pedidoc']);
                $stmt->bindParam(':costo', $pedido['costo']);
                $stmt->bindParam(':m_pago', $pedido['m_pago']);
                $stmt->bindParam(':efectivo', $pedido['efectivo']);
                $stmt->bindParam(':banco', $pedido['banco']);
                $stmt->bindParam(':referencia', $pedido['referencia']);
                $stmt->bindParam(':cajero', $pedido['cajero']);  // Almacenar el cajero aquí
                $stmt->bindParam(':fecha_caja', $fecha_actual);  // Guardar la fecha y hora actual de Colombia
                $stmt->bindParam(':id_cajero', $pedido['id_cajero']);

                if (!$stmt->execute()) {
                    $errores[] = "Error al insertar el pago del pedido " . $pedido['id_pedidoc'] . ".";
                }
            } else {
                $errores[] = "Datos incompletos para el pedido " . $pedido['id_pedidoc'] . ".";
            }
        }

        if (empty($errores)) {
            echo json_encode(['success' => true, 'message' => 'Pagos insertados correctamente.']);
        } else {
            echo json_encode(['success' => false, 'errors' => $errores]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
