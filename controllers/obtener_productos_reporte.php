<?php
require_once '../config/database.php';

date_default_timezone_set('America/Bogota');

$fechaSeleccionada = $_GET['fecha'] ?? date('Y-m-d');

$db = new Database();
$conn = $db->getConnection();

// Seleccionar solo los pedidos cuyo id_pedido esté en caja.id_pedidoc
$query = "
SELECT 
    c.id_pedidoc AS id_pedido,  
    DATE_FORMAT(c.fecha_caja, '%Y-%m-%d') AS fecha, 
    pr.prefijo AS nombre_producto, 
    p.tipo_producto,  
    SUM(p.cantidad) AS cantidad_total, 
    pre.precio AS precio_unitario,
    (SUM(p.cantidad) * pre.precio) AS total_producto,
    c.costo AS costo_registrado
FROM caja c
LEFT JOIN pedidos p ON c.id_pedidoc = p.numero_pedido  -- ✅ Relacionamos correctamente el pedido
LEFT JOIN productos pr ON p.id_pro = pr.id_pro  -- ✅ Relacionamos el producto
LEFT JOIN precios pre ON pr.id_pro = pre.idproduc AND p.tipo_producto = pre.tipo_prod  -- ✅ Relacionamos el precio por tipo de producto
WHERE DATE(c.fecha_caja) = ?  -- ✅ Filtramos por la fecha seleccionada (tomamos la fecha de caja)
GROUP BY c.id_pedidoc, pr.prefijo, p.tipo_producto, pre.precio, c.costo  
ORDER BY c.fecha_caja ASC;

";

$stmt = $conn->prepare($query);
$stmt->execute([$fechaSeleccionada]);
$productosVendidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($productosVendidos)) {
    echo json_encode(["error" => "No se encontraron productos pagados para la fecha seleccionada: $fechaSeleccionada"]);
    exit;
}

echo json_encode($productosVendidos);
?>
