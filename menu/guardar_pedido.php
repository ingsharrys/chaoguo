<?php 
require_once '../config/database.php';
require_once '../controllers/generar_numero_pedido.php'; // Incluimos el archivo de generar número de pedido

// Establecer la zona horaria a Colombia
date_default_timezone_set('America/Bogota');

// Función para asignar el turno correspondiente
function assignTurno($conn, $orderNumber, $tipo_solicitud, $clientId) {
    // Obtener la fecha actual en formato Y-m-d (sin la hora)
    $fecha_actual = date('Y-m-d');
    $estadopedi = 'nuevo';

    // Consultar el último turno asignado para el mismo tipo_solicitud en la fecha actual
    $query = "SELECT MAX(turno) AS max_turno FROM turnero WHERE DATE(fecha) = :fecha_actual AND tipo_solicitud = :tipo_solicitud";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':fecha_actual', $fecha_actual); // Usar solo la fecha (Y-m-d)
    $stmt->bindParam(':tipo_solicitud', $tipo_solicitud);
    $stmt->execute();
    $max_turno = $stmt->fetchColumn();

    // Si no hay turnos para hoy, comenzamos desde 1
    $nuevo_turno = $max_turno ? $max_turno + 1 : 1;

    // Insertar el nuevo turno en la tabla turnero
    $query = "INSERT INTO turnero (id_pedido, turno, fecha, tipo_solicitud, estado, id_cliente) 
              VALUES (:id_pedido, :turno, :fecha, :tipo_solicitud, :estado, :id_cliente)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_pedido', $orderNumber);
    $stmt->bindParam(':turno', $nuevo_turno);
    
    // Guardar la fecha actual con la hora en el formato Y-m-d H:i:s
    $fecha_completa = date('Y-m-d H:i:s');
    $stmt->bindParam(':fecha', $fecha_completa);
    $stmt->bindParam(':tipo_solicitud', $tipo_solicitud);
    $stmt->bindParam(':estado', $estadopedi);
    $stmt->bindParam(':id_cliente', $clientId);
    $stmt->execute();

    return $nuevo_turno;
}

try {
    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener datos del formulario
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = isset($_POST['address']) ? urldecode($_POST['address']) : ''; 
    $barrio = $_POST['barrio'] ?? '';
    $email = $_POST['email'] ?? 'sincorreo';
    $id = $_POST['id'] ?? '0';
    $products = $_POST['products'] ?? [];
    $tipo_solicitud = $_POST['tipo_solicitud'] ?? 1; // Obtener el valor de tipo_solicitud
    $comments = $_POST['comments'] ?? ''; // Obtener comentario

    // Verificar si el cliente ya existe
    $query = "SELECT id FROM clientes WHERE celular = :phone";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($client) {
        // Si el cliente existe, actualizar los datos
        $query = "UPDATE clientes SET cliente = :name, email = :email, direccion = :address, barrio = :barrio, cedula = :id WHERE celular = :phone";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':barrio', $barrio);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        $clientId = $client['id'];
    } else {
        // Si el cliente no existe, insertar nuevo registro
        $query = "INSERT INTO clientes (cliente, celular, email, direccion, cedula, barrio) VALUES (:name, :phone, :email, :address, :id, :barrio)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':barrio', $barrio);
        $stmt->execute();
        $clientId = $conn->lastInsertId();
    }

    // Generar número de pedido único desde generar_numero_pedido.php
    $orderNumber = generarNumeroPedido($conn); // Llamamos a la función de generar número de pedido
    
     // Asignar el turno correspondiente para este pedido (después de que el pedido se ha creado)
    $nuevo_turno = assignTurno($conn, $orderNumber, $tipo_solicitud, $clientId);

    // Insertar comentario en la tabla comentarios (solo una vez por pedido)
    if (!empty($comments)) {
        $query = "INSERT INTO comentarios (id_pedido, comentario) VALUES (:id_pedido, :comentario)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_pedido', $orderNumber);
        $stmt->bindParam(':comentario', $comments);
        $stmt->execute();
    }

    // Insertar pedido en la tabla pedidos (esto asegura que el pedido exista antes de asignar un turno)
    foreach ($products as $product) {
        $query = "INSERT INTO pedidos (id_pro, cantidad, fecha, numero_pedido, tipo_solicitud, detalle, tipo_producto) 
                  VALUES (:id_pro, :quantity, :fecha, :order_number, :tipo_solicitud, :detalle, :tipo_producto)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_pro', $product['id']); // Guardar el id_pro del producto
        $stmt->bindParam(':quantity', $product['quantity']);
        $fecha = date('Y-m-d H:i:s'); // Obtener la fecha y hora actuales en la zona horaria establecida
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':order_number', $orderNumber);
        $stmt->bindParam(':tipo_solicitud', $tipo_solicitud);
        $detalle = ($product['option'] ?? '') . (isset($product['suboption']) ? ' ' . $product['suboption'] : '');
        $stmt->bindParam(':detalle', $detalle);
        $stmt->bindParam(':tipo_producto', $product['type']); // Guardar el tipo de producto

        $stmt->execute();
    }

   

    echo json_encode(['status' => 'success', 'message' => 'Pedido guardado correctamente', 'order_number' => $orderNumber, 'turno' => $nuevo_turno]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>