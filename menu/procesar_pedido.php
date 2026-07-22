<?php
require_once '../config/database.php';

// Establecer la zona horaria a Colombia
date_default_timezone_set('America/Bogota');

try {
    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener datos del formulario
    $client = $_POST['client'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $comments = $_POST['comments'] ?? '';
    $productos = $_POST['cantidad'] ?? [];

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

    // Generar número de pedido único
    $orderNumber = mt_rand(10000, 99999); // Generar un número de pedido aleatorio

    // Recorrer los productos y almacenarlos en la tabla pedidos
    foreach ($_POST['producto'] as $index => $productoId) {
    $cantidad = $_POST['cantidad'][$index];
    $tipoProducto = $_POST['tipo'][$index];
    $color = $_POST['color'][$index];
    
    // Verificar que el id_pro no sea NULL
    if (empty($productoId)) {
        echo json_encode(['status' => 'error', 'message' => 'El ID del producto no puede ser nulo']);
        exit;
    }

    $detalle = "Color: " . $color;

    $query = "INSERT INTO pedidos (id_cliente, id_pro, cantidad, fecha, numero_pedido, estado, tipo_producto, detalle) 
              VALUES (:clientId, :productoId, :productoId, :cantidad, :fecha, :orderNumber, 'nuevo', :tipoProducto, :detalle)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':clientId', $clientId);
    $stmt->bindParam(':productoId', $productoId);
    $stmt->bindParam(':cantidad', $cantidad);
    $fecha = date('Y-m-d H:i:s');
    $stmt->bindParam(':fecha', $fecha);
    $stmt->bindParam(':orderNumber', $orderNumber);
    $stmt->bindParam(':tipoProducto', $tipoProducto);
    $stmt->bindParam(':detalle', $detalle);
    $stmt->execute();
}


    // Guardar los comentarios si existen
    if (!empty($comments)) {
        $query = "INSERT INTO comentarios (id_pedido, comentario) VALUES (:orderNumber, :comments)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':orderNumber', $orderNumber);
        $stmt->bindParam(':comments', $comments);
        $stmt->execute();
    }

    // Devolver una respuesta exitosa
    echo json_encode(['status' => 'success', 'message' => 'Pedido guardado correctamente', 'order_number' => $orderNumber]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
