<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$id_pro = isset($_GET['id_pro']) ? intval($_GET['id_pro']) : null;

// 📌 Log para depuración
error_log("🔍 ID de producto recibido: " . print_r($id_pro, true));

if (!$id_pro) {
    error_log("⚠ ID de producto no válido o no recibido.");
    echo json_encode(['status' => 'error', 'message' => 'ID de producto no válido']);
    exit;
}

// 📌 Consulta para obtener tipo de producto desde `precios` y `tcomida` desde `productos`
$query = "SELECT p.tipo_prod, pr.tcomida 
          FROM precios p
          JOIN productos pr ON p.idproduc = pr.id_pro
          WHERE p.idproduc = :id_pro";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id_pro', $id_pro, PDO::PARAM_INT);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 📌 Log de los resultados obtenidos
error_log("📥 Datos obtenidos de la BD: " . print_r($productos, true));

if (!$productos) {
    error_log("⚠ Producto no encontrado en la base de datos.");
    echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado']);
    exit;
}

// 📌 Extraer tipos de producto
$tiposProducto = array_column($productos, 'tipo_prod'); // Obtener solo los valores de `tipo_prod`

// 📌 Determinar detalles permitidos según `tcomida`
$detallesPermitidos = [];
$tcomida = $productos[0]['tcomida'] ?? null; // Obtener el valor de `tcomida`
switch ($tcomida) {
    case 1:
        $detallesPermitidos = ['amarillo', 'cafe'];
        break;
    case 2:
        $detallesPermitidos = ['papa', 'amarillo', 'cafe'];
        break;
    
    case 10:
        $detallesPermitidos = ['Sindetalle'];
        break;
   
    default:
        $detallesPermitidos = [];
        break;
}

// 📌 Mostrar en el log la respuesta final
error_log("✅ Respuesta enviada: " . print_r([
    'status' => 'success',
    'tipos' => array_values(array_unique($tiposProducto)), // Evitar duplicados
    'detalles' => $detallesPermitidos
], true));

echo json_encode([
    'status' => 'success',
    'tipos' => array_values(array_unique($tiposProducto)), // Evitar duplicados
    'detalles' => $detallesPermitidos
]);
?>
