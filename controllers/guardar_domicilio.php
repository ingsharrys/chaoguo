<?php
header('Content-Type: application/json');

try {
    include_once '../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();

    // Decodificar los datos enviados en el cuerpo de la solicitud
    $data = json_decode(file_get_contents('php://input'), true);

    // Verificar que se recibieron los datos necesarios
    if (isset($data['id_pedido'], $data['precio'])) {
        // Obtener el precio actual del domicilio
        $stmtSelect = $conn->prepare("SELECT precio FROM domicilios WHERE id_pedido = :id_pedido");
        $stmtSelect->bindParam(':id_pedido', $data['id_pedido']);
        $stmtSelect->execute();
        $result = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        // Si el pedido existe, verificar si el precio es diferente
        if ($result) {
            $precioActual = $result['precio'];

            // Solo actualizar si el precio es diferente
            if ($data['precio'] != $precioActual) {
                $stmtUpdate = $conn->prepare("UPDATE domicilios SET precio = :precio WHERE id_pedido = :id_pedido");
                $stmtUpdate->bindParam(':precio', $data['precio']);
                $stmtUpdate->bindParam(':id_pedido', $data['id_pedido']);

                if ($stmtUpdate->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Costo de domicilio actualizado correctamente']);
                } else {
                    $errorInfo = $stmtUpdate->errorInfo();
                    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el domicilio: ' . $errorInfo[2]]);
                }
            } else {
                // Si el precio es el mismo, no hacer nada
                echo json_encode(['status' => 'no-change', 'message' => 'El costo de domicilio no ha cambiado']);
            }
        } else {
            // Si no existe el pedido, insertar un nuevo registro
            $stmtInsert = $conn->prepare("INSERT INTO domicilios (id_pedido, precio) VALUES (:id_pedido, :precio)");
            $stmtInsert->bindParam(':id_pedido', $data['id_pedido']);
            $stmtInsert->bindParam(':precio', $data['precio']);

            if ($stmtInsert->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Costo de domicilio guardado correctamente']);
            } else {
                $errorInfo = $stmtInsert->errorInfo();
                echo json_encode(['status' => 'error', 'message' => 'Error al guardar el domicilio: ' . $errorInfo[2]]);
            }
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para procesar la solicitud']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}

?>
