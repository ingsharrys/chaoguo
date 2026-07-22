<?php
require_once 'config/database.php';

// Crear instancia de la clase Database y obtener la conexión
$database = new Database();
$conn = $database->getConnection();

// Obtener los parámetros GET de la URL
$numero_pedido = $_GET['numero_pedido'];
$client = $_GET['client'];
$addres = $_GET['addres'];
$name = $_GET['name'];
$comenta = $_GET['comenta'] ?? null;

// Verificar si el cliente ya existe en la tabla `clientes`
$query = "SELECT id FROM clientes WHERE celular = :client";
$stmt = $conn->prepare($query);
$stmt->bindParam(':client', $client);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cliente) {
    // Si el cliente existe, actualizar su información
    $id_cliente = $cliente['id'];
    $updateQuery = "UPDATE clientes SET cliente = :name, direccion = :addres WHERE id = :id_cliente";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':addres', $addres);
    $stmt->bindParam(':id_cliente', $id_cliente);
    $stmt->execute();
} else {
    // Si el cliente no existe, insertarlo
    $insertQuery = "INSERT INTO clientes (cliente, celular, direccion) VALUES (:name, :client, :addres)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':client', $client);
    $stmt->bindParam(':addres', $addres);
    $stmt->execute();
    $id_cliente = $conn->lastInsertId(); // Obtener el ID del cliente recién insertado
}

// Insertar productos en la tabla `pedidos`
$i = 1;
while (isset($_GET['producto' . $i])) {
    $producto_id = $_GET['producto' . $i];
    $cantidad = $_GET['cantidad' . $i];
    $precio = $_GET['precio' . $i];
    $color = $_GET['color' . $i] ?? null;

    // Obtener el nombre del producto desde la tabla `productos`
    $queryProducto = "SELECT nombre FROM productos WHERE id_pro = :producto_id";
    $stmt = $conn->prepare($queryProducto);
    $stmt->bindParam(':producto_id', $producto_id);
    $stmt->execute();
    $producto = $stmt->fetch(PDO::FETCH_ASSOC)['nombre'];

    // Obtener el tipo de producto desde la tabla `precios`
    $queryPrecio = "SELECT tipo_prod FROM precios WHERE idproduc = :producto_id AND precio = :precio";
    $stmt = $conn->prepare($queryPrecio);
    $stmt->bindParam(':producto_id', $producto_id);
    $stmt->bindParam(':precio', $precio);
    $stmt->execute();
    $tipo_producto = $stmt->fetch(PDO::FETCH_ASSOC)['tipo_prod'];

    // Insertar en la tabla `pedidos`
    $insertPedido = "INSERT INTO pedidos (id_cliente, id_pro, producto, cantidad, numero_pedido, estado, tipo_producto, detalle, tipo_solicitud) VALUES (:id_cliente, :producto_id, :producto, :cantidad, :numero_pedido, 'pendiente', :tipo_producto, :color, 50)";
    $stmt = $conn->prepare($insertPedido);
    $stmt->bindParam(':id_cliente', $id_cliente);
    $stmt->bindParam(':producto_id', $producto_id);
    $stmt->bindParam(':producto', $producto);
    $stmt->bindParam(':cantidad', $cantidad);
    $stmt->bindParam(':numero_pedido', $numero_pedido);
    $stmt->bindParam(':tipo_producto', $tipo_producto);
    $stmt->bindParam(':color', $color);
    $stmt->execute();

    $i++; // Pasar al siguiente producto
}

echo "Pedido procesado exitosamente.";
?>
