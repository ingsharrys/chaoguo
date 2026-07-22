<?php
require_once '../config/database.php'; // Asegúrate de que la ruta al archivo de configuración es correcta
$db = new Database();
$conn = $db->getConnection();

$page = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productsPerPage = 10; // Número de productos por página
$offset = ($page - 1) * $productsPerPage;

// Obtener las fechas de inicio y fin de la solicitud AJAX, si existen
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Contar el total de clientes únicos con el filtro de fechas
$totalQuery = "SELECT COUNT(DISTINCT id_cliente) FROM pedidos WHERE 1";

if ($startDate) {
    $totalQuery .= " AND fecha >= :start_date";
}
if ($endDate) {
    $totalQuery .= " AND fecha <= :end_date";
}

$totalStmt = $conn->prepare($totalQuery);

if ($startDate) {
    $totalStmt->bindValue(':start_date', $startDate);
}
if ($endDate) {
    $totalStmt->bindValue(':end_date', $endDate);
}

$totalStmt->execute();
$totalProducts = $totalStmt->fetchColumn();

// Calcular el número total de páginas
$totalPages = ceil($totalProducts / $productsPerPage);

// Obtener los datos de clientes y su frecuencia con el filtro de fechas
$query = "SELECT clientes.cliente AS nombre, COUNT(pedidos.id_cliente) AS frecuencia 
          FROM pedidos 
          JOIN clientes ON pedidos.id_cliente = clientes.id 
          WHERE 1";

if ($startDate) {
    $query .= " AND pedidos.fecha >= :start_date";
}
if ($endDate) {
    $query .= " AND pedidos.fecha <= :end_date";
}

$query .= " GROUP BY pedidos.id_cliente 
            ORDER BY frecuencia DESC 
            LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

if ($startDate) {
    $stmt->bindValue(':start_date', $startDate);
}
if ($endDate) {
    $stmt->bindValue(':end_date', $endDate);
}

$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Responder con los resultados en formato JSON
echo json_encode([
    'clientes' => $clientes,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
?>