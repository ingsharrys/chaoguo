<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Responder preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "
        SELECT 
            m.numero_mesa AS mesa,
            m.estado,
            m.id_pedido,
            EXISTS (
                SELECT 1 FROM caja c WHERE c.id_pedidoc = m.id_pedido LIMIT 1
            ) AS pagado
        FROM mesas m
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir 'pagado' a booleano real
    foreach ($mesas as &$mesa) {
        $mesa['pagado'] = (bool) $mesa['pagado'];
    }

    echo json_encode($mesas, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al consultar la base de datos',
        'detalle' => $e->getMessage()
    ]);
}
