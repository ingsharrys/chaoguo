<?php
// Mostrar errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// 📌 Registrar en log los datos recibidos
error_log("📥 Datos recibidos en procesar_edicion_pedido.php: " . print_r($_POST, true));

// Obtener número de pedido
$numero_pedido = isset($_POST['numero_pedido']) ? $_POST['numero_pedido'] : null;

if (!$numero_pedido) {
    error_log("⚠ Error: número de pedido no proporcionado.");
    die('Error: número de pedido no proporcionado.');
}

// 📌 **Actualizar productos existentes**
if (isset($_POST['productos_existentes'])) {
    foreach ($_POST['productos_existentes'] as $id_pedido => $producto) { // Ahora usamos id_pedido en lugar de id_pro
        if (!empty($producto['cantidad']) && !empty($producto['tipo_producto']) && !empty($producto['id_pro'])) {
            $nuevo_id_pro = (int)$producto['id_pro'];
            $cantidad = (int)$producto['cantidad'];
            $tipo_producto = $producto['tipo_producto'];
            $detalle = isset($producto['detalle']) ? $producto['detalle'] : 'NULL';

            error_log("🔄 Actualizando producto - ID Pedido: $id_pedido, ID Producto: $nuevo_id_pro, Cantidad: $cantidad, Tipo: $tipo_producto, Detalle: $detalle");

            // ✅ **Corregimos la consulta de actualización**
            $query = "UPDATE pedidos 
                      SET id_pro = :nuevo_id_pro, cantidad = :cantidad, tipo_producto = :tipo_producto, detalle = :detalle 
                      WHERE id_pedido = :id_pedido"; // Ahora usamos id_pedido para actualizar un producto específico

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nuevo_id_pro', $nuevo_id_pro, PDO::PARAM_INT);
            $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_producto', $tipo_producto, PDO::PARAM_STR);
            $stmt->bindParam(':detalle', $detalle, PDO::PARAM_STR);
            $stmt->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT); // ✅ Usamos id_pedido en lugar de numero_pedido
            $stmt->execute();
        }
    }
}

// 📌 **Insertar nuevos productos**
if (isset($_POST['productos_nuevos']) && is_array($_POST['productos_nuevos'])) {

    /* Contexto del pedido existente: mesa, mesero, cliente, tipo de
       solicitud y estados, para que la fila nueva quede completa y
       visible igual que las demás del pedido. */
    $stmtCtx = $conn->prepare("
        SELECT tipo_solicitud, mesa, mesero, id_cliente, estado, estado_boton
        FROM pedidos
        WHERE numero_pedido = :np
        ORDER BY id_pedido DESC
        LIMIT 1
    ");
    $stmtCtx->execute([':np' => $numero_pedido]);
    $ctx = $stmtCtx->fetch(PDO::FETCH_ASSOC) ?: [];

    foreach ($_POST['productos_nuevos'] as $producto) {
        if (!empty($producto['id_pro']) && !empty($producto['cantidad']) && !empty($producto['tipo_producto'])) {
            $id_pro = (int)$producto['id_pro'];
            $cantidad = (int)$producto['cantidad'];
            $tipo_producto = $producto['tipo_producto'];
            $detalle = isset($producto['detalle']) ? $producto['detalle'] : 'NULL';

            // Obtener nombre y prefijo del nuevo producto
            $queryNombreProducto = "SELECT nombre, prefijo FROM productos WHERE id_pro = :id_pro";
            $stmtNombre = $conn->prepare($queryNombreProducto);
            $stmtNombre->bindParam(':id_pro', $id_pro, PDO::PARAM_INT);
            $stmtNombre->execute();
            $productoDatos = $stmtNombre->fetch(PDO::FETCH_ASSOC);
            $nombre_producto = $productoDatos['nombre'] ?? 'Desconocido';
            $prefijo_producto = $productoDatos['prefijo'] ?? '';

            error_log("🆕 Insertando nuevo producto - ID: $id_pro, Nombre: $nombre_producto, Cantidad: $cantidad, Tipo: $tipo_producto, Detalle: $detalle");

            // Insertar con todas las columnas que el panel necesita para
            // mostrar el pedido (producto, cliente, estados, mesa, mesero)
            $query = "INSERT INTO pedidos
                      (numero_pedido, id_pro, producto, prefijos, cantidad, tipo_producto,
                       detalle, fecha, tipo_solicitud, mesa, mesero, id_cliente, estado, estado_boton)
                      VALUES
                      (:numero_pedido, :id_pro, :producto, :prefijos, :cantidad, :tipo_producto,
                       :detalle, NOW(), :tipo_solicitud, :mesa, :mesero, :id_cliente, :estado, :estado_boton)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':numero_pedido'  => $numero_pedido,
                ':id_pro'         => $id_pro,
                ':producto'       => $nombre_producto,
                ':prefijos'       => $prefijo_producto,
                ':cantidad'       => $cantidad,
                ':tipo_producto'  => $tipo_producto,
                ':detalle'        => $detalle,
                ':tipo_solicitud' => $ctx['tipo_solicitud'] ?? 1,
                ':mesa'           => $ctx['mesa'] ?? null,
                ':mesero'         => $ctx['mesero'] ?? null,
                ':id_cliente'     => $ctx['id_cliente'] ?? 1,
                ':estado'         => $ctx['estado'] ?? 'nuevo',
                ':estado_boton'   => $ctx['estado_boton'] ?? 'nuevo'
            ]);
        } else {
            error_log("⚠ Producto nuevo con datos incompletos: " . print_r($producto, true));
        }
    }
}

error_log("✅ Operación completada con éxito para el pedido: $numero_pedido");

echo "<script>
        console.log('✅ Pedido actualizado con éxito.');
        alert('Operación exitosa. Redirigiendo...');
        window.location.href = '../public/index.php?page=dashboard.php';
      </script>";
exit;
?>
