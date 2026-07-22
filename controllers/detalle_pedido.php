<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once '../config/database.php';

if (!empty($_GET['id_pedido'])) {

    $numeroPedido = $_GET['id_pedido'];
    $db = (new Database())->getConnection();

    $sql = "
        SELECT p.numero_pedido, p.tipo_producto, p.detalle, p.cantidad,
               pr.precio, c.cliente, c.direccion, c.celular,
               prod.nombre AS producto
        FROM turnero t
        JOIN pedidos   p  ON t.id_pedido  = p.numero_pedido
        JOIN clientes  c  ON t.id_cliente = c.id
        JOIN precios   pr ON p.id_pro     = pr.idproduc
                         AND p.tipo_producto = pr.tipo_prod
        JOIN productos prod ON p.id_pro   = prod.id_pro
        WHERE p.numero_pedido = :num
    ";

    $st = $db->prepare($sql);
    $st->bindParam(':num', $numeroPedido);   // sin forzar tipo
    $st->execute();
    $pedido = $st->fetchAll(PDO::FETCH_ASSOC);

    if (!$pedido) {
        echo '<p>No se encontraron detalles para este pedido.</p>';
        exit;
    }

    /* ——— HTML ——— */
    echo "<h5>Detalle del Pedido</h5>";
    echo "<p><strong>Pedido N°:</strong> ".htmlspecialchars($pedido[0]['numero_pedido'])."</p>";
    echo "<p><strong>Cliente:</strong> ".htmlspecialchars($pedido[0]['cliente'])."</p>";
    echo "<p><strong>Dirección:</strong> ".htmlspecialchars($pedido[0]['direccion'])."</p>";
    echo "<p><strong>Teléfono:</strong> ".htmlspecialchars($pedido[0]['celular'])."</p>";

    echo '<div class="table-responsive"><table class="table table-bordered"><thead>
            <tr><th>Producto</th><th>Tamaño</th><th>Detalle</th><th>Cantidad</th>
                <th>Precio Unitario</th><th>Subtotal</th></tr></thead><tbody>';

    $total = 0;
    foreach ($pedido as $row) {
        $sub = $row['cantidad'] * $row['precio'];  $total += $sub;
        echo '<tr><td>'.htmlspecialchars($row['producto']).'</td>
                 <td>'.htmlspecialchars($row['tipo_producto']).'</td>
                 <td>'.htmlspecialchars($row['detalle']).'</td>
                 <td>'.htmlspecialchars($row['cantidad']).'</td>
                 <td>$'.number_format($row['precio'],2,',','.').'</td>
                 <td>$'.number_format($sub,2,',','.').'</td></tr>';
    }
    echo '</tbody></table></div>';
    echo '<p><strong>Total:</strong> $'.number_format($total,2,',','.').'</p>';

    /* domicilio opcional */
    $stDom = $db->prepare("
        SELECT d.precio, dom.repartidor
        FROM domicilios d
        LEFT JOIN domiciliarios dom ON d.id_domi = dom.id_e
        WHERE d.id_pedido = :num
    ");
    $stDom->bindParam(':num', $numeroPedido);
    $stDom->execute();
    if ($dom = $stDom->fetch(PDO::FETCH_ASSOC)) {
        echo '<h5>Detalles del Domicilio</h5>';
        echo '<p><strong>Repartidor:</strong> '.htmlspecialchars($dom['repartidor']).'</p>';
        echo '<p><strong>Precio:</strong> $'.number_format($dom['precio'],2,',','.').'</p>';
    } else {
        echo '<p><strong>Este pedido no es un domicilio.</strong></p>';
    }

} else {
    echo '<p>No se proporcionó un ID de pedido válido.</p>';
}
