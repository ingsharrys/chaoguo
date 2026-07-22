<?php
require_once '../config/database.php';

// Validar que se haya recibido un ID válido
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['status' => 'error', 'message' => 'ID de producto no proporcionado o no válido.']);
    exit();
}

$id = $_GET['id'];

$db = new Database();
$conn = $db->getConnection();

// Consulta para obtener los datos del producto y concatenar las variantes y precios
$query = "
    SELECT 
        p.id_pro, 
        p.nombre, 
        p.prefijo, 
        p.cat, 
        p.descript, 
        p.img, 
        p.tcomida,
        GROUP_CONCAT(pr.tipo_prod) AS tipos_producto,
        GROUP_CONCAT(pr.precio) AS precios
    FROM productos p 
    LEFT JOIN precios pr ON p.id_pro = pr.idproduc 
    WHERE p.id_pro = :id 
    GROUP BY p.id_pro
";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);

if ($stmt->execute()) {
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($producto) {
        // Si no existen variantes o precios, asegurar que sean arrays vacíos
        $tiposProductoStr = isset($producto['tipos_producto']) ? $producto['tipos_producto'] : '';
        $preciosStr = isset($producto['precios']) ? $producto['precios'] : '';

        // Convertir las cadenas en arrays; si están vacías, se obtendrán arrays vacíos
        $tipos = $tiposProductoStr !== '' ? explode(',', $tiposProductoStr) : [];
        $precios = $preciosStr !== '' ? explode(',', $preciosStr) : [];

        // Preparar un array que combine cada tipo con su precio (se usará el mínimo de ambos recuentos)
        $producto['tipos_precios'] = [];
        $count = min(count($tipos), count($precios));
        for ($i = 0; $i < $count; $i++) {
            $producto['tipos_precios'][] = [
                'tipo_prod' => $tipos[$i],
                'precio' => $precios[$i]
            ];
        }
        // También puedes agregar los arrays originales, si los necesitas en el front
        $producto['tipos_producto'] = $tipos;
        $producto['precios'] = $precios;

        echo json_encode(['status' => 'success', 'producto' => $producto]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la consulta.']);
}
?>
