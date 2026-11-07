<?php
require_once '../config/database.php';

// Establecer la zona horaria a Colombia
date_default_timezone_set('America/Bogota');

try {
    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener datos del formulario
    $client = $_POST['client'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $comments = $_POST['comments'] ?? '';

    if (empty($_POST['producto']) || !is_array($_POST['producto'])) {
        echo json_encode(['status' => 'error', 'message' => 'El pedido no tiene productos.']);
        exit;
    }

    // Todo el pedido en UNA transacción: sin pedidos a medias si algo falla.
    $conn->beginTransaction();

    // Verificar si el cliente ya existe
    $query = "SELECT id FROM clientes WHERE celular = :phone";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    $clientData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($clientData) {
        // Si el cliente existe, actualizar los datos
        $query = "UPDATE clientes SET cliente = :client, direccion = :address WHERE celular = :phone";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':client', $client);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        $clientId = $clientData['id'];
    } else {
        // Si el cliente no existe, insertar un nuevo registro
        $query = "INSERT INTO clientes (cliente, celular, direccion) VALUES (:client, :phone, :address)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':client', $client);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->execute();
        $clientId = $conn->lastInsertId();
    }

    // Número de pedido único con el contador 'consecutivos' (igual que la
    // app de mesas y el resto de canales). Antes era mt_rand(10000,99999):
    // números aleatorios que chocaban con pedidos reales de otros canales.
    $stmt = $conn->prepare("
        UPDATE consecutivos
        SET valor = LAST_INSERT_ID(valor + 1)
        WHERE nombre = 'num_pedido'
    ");
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        throw new Exception("Falta la fila 'num_pedido' en la tabla consecutivos.");
    }
    $orderNumber = (int)$conn->lastInsertId();

    // Recorrer los productos y almacenarlos en la tabla pedidos.
    // Se llenan también producto/prefijos (del catálogo, por id) y los
    // estados que usa el panel.
    $stmtCat = $conn->prepare("SELECT nombre, prefijo FROM productos WHERE id_pro = :id LIMIT 1");
    $stmtIns = $conn->prepare("
        INSERT INTO pedidos (id_cliente, id_pro, producto, prefijos, cantidad, fecha,
                             numero_pedido, estado, estado_boton, tipo_producto, detalle, tipo_solicitud)
        VALUES (:clientId, :productoId, :producto, :prefijos, :cantidad, :fecha,
                :orderNumber, 'nuevo', 'nuevo', :tipoProducto, :detalle, 50)
    ");

    foreach ($_POST['producto'] as $index => $productoId) {
        $cantidad = $_POST['cantidad'][$index] ?? 0;
        $tipoProducto = $_POST['tipo'][$index] ?? '';
        $color = $_POST['color'][$index] ?? '';

        // Verificar que el id_pro no sea NULL
        if (empty($productoId) || (int)$cantidad <= 0) {
            throw new Exception('Producto o cantidad inválidos en el pedido.');
        }

        $stmtCat->execute([':id' => $productoId]);
        $catInfo = $stmtCat->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmtIns->execute([
            ':clientId'     => $clientId,
            ':productoId'   => $productoId,
            ':producto'     => $catInfo['nombre']  ?? '',
            ':prefijos'     => $catInfo['prefijo'] ?? '',
            ':cantidad'     => $cantidad,
            ':fecha'        => date('Y-m-d H:i:s'),
            ':orderNumber'  => $orderNumber,
            ':tipoProducto' => $tipoProducto,
            ':detalle'      => "Color: " . $color,
        ]);
    }

    // Guardar los comentarios si existen
    if (!empty($comments)) {
        $query = "INSERT INTO comentarios (id_pedido, comentario) VALUES (:orderNumber, :comments)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':orderNumber', $orderNumber);
        $stmt->bindParam(':comments', $comments);
        $stmt->execute();
    }

    $conn->commit();

    // Devolver una respuesta exitosa
    echo json_encode(['status' => 'success', 'message' => 'Pedido guardado correctamente', 'order_number' => $orderNumber]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('[procesar_pedido] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar el pedido. Intenta de nuevo.']);
}
?>
