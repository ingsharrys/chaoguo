<?php
include '../config/database.php'; // Asegúrate de incluir tu archivo de conexión a la base de datos

$input = file_get_contents("php://input");
$data = json_decode($input, true);

$numero_pedido = $data['numero_pedido'];

$query = "UPDATE pedidos SET estado_boton = 'confirmado' WHERE numero_pedido = :numero_pedido";
$stmt = $conn->prepare($query);
$stmt->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_STR);

$response = array();

if ($stmt->execute()) {
    $response['status'] = 'success';
} else {
    $response['status'] = 'error';
}

echo json_encode($response);
?>
