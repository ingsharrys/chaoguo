<?php
require_once '../config/database.php';

// Establecer la zona horaria a Colombia
date_default_timezone_set('America/Bogota');

// Función para asignar el turno correspondiente
function assignTurno($conn, $orderNumber, $tipo_solicitud, $clientId) {
    $estadopedi = 'nuevo';

    // Siguiente turno del día para este tipo: contador atómico por día+tipo
    // (tabla turnos_contador). Sustituye al SELECT MAX(turno) que, sin
    // bloqueo, generaba turnos duplicados en hora pico y, con bloqueo,
    // serializaba todos los pedidos.
    $stmt = $conn->prepare("
        INSERT INTO turnos_contador (fecha, tipo, valor)
        VALUES (CURDATE(), :tipo_solicitud, LAST_INSERT_ID(1))
        ON DUPLICATE KEY UPDATE valor = LAST_INSERT_ID(valor + 1)
    ");
    $stmt->bindParam(':tipo_solicitud', $tipo_solicitud);
    $stmt->execute();
    $nuevo_turno = (int)$conn->lastInsertId();

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
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

    if (empty($products) || !is_array($products)) {
        echo json_encode(['status' => 'error', 'message' => 'El pedido no tiene productos.']);
        exit;
    }

    // TODO el pedido va en UNA transacción: si algo falla a mitad no quedan
    // pedidos a medias (turno sin productos, comentario huérfano, etc.).
    $conn->beginTransaction();

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

    // Generar número de pedido único con el contador 'consecutivos': una
    // sola fila bloqueada un instante. Reemplaza a generarNumeroPedido(),
    // que recorría números uno a uno (miles de consultas por pedido) y
    // REUTILIZABA números de pedidos borrados, chocando con los otros
    // canales (app de mesas y panel) y con pagos históricos en caja.
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
    // Se llenan también producto, prefijos, id_cliente y estado: el panel
    // (caja, dashboard) usa esas columnas y si quedan vacías el pedido se
    // muestra mal o se cruza con otro producto.
    $stmtProdCat = $conn->prepare("SELECT nombre, prefijo FROM productos WHERE id_pro = :id LIMIT 1");
    foreach ($products as $product) {
        $stmtProdCat->execute([':id' => $product['id']]);
        $prodInfo = $stmtProdCat->fetch(PDO::FETCH_ASSOC) ?: [];

        $query = "INSERT INTO pedidos (id_cliente, id_pro, producto, prefijos, cantidad, fecha, numero_pedido, tipo_solicitud, detalle, tipo_producto, estado, estado_boton)
                  VALUES (:id_cliente, :id_pro, :producto, :prefijos, :quantity, :fecha, :order_number, :tipo_solicitud, :detalle, :tipo_producto, 'nuevo', 'nuevo')";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_cliente', $clientId);
        $stmt->bindParam(':id_pro', $product['id']); // Guardar el id_pro del producto
        $producto_nombre  = $prodInfo['nombre']  ?? '';
        $producto_prefijo = $prodInfo['prefijo'] ?? '';
        $stmt->bindParam(':producto', $producto_nombre);
        $stmt->bindParam(':prefijos', $producto_prefijo);
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

    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Pedido guardado correctamente', 'order_number' => $orderNumber, 'turno' => $nuevo_turno]);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('[guardar_pedido] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar el pedido. Intenta de nuevo.']);
}
?>
