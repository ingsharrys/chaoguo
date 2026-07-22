<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la conexión a la base de datos
include_once '../config/database.php';

// Función para generar un número de pedido único y auto-incremental
function generarNumeroPedido($db) {
    // Empezar desde un número pequeño (por ejemplo, 1) y buscar el número más pequeño disponible
    $numero_pedido = 1;

    // Consulta para verificar si el número de pedido ya existe en la tabla
    $query = "SELECT numero_pedido FROM pedidos WHERE numero_pedido = :numero_pedido";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);

    // Bucle para encontrar el siguiente número de pedido disponible
    do {
        $stmt->execute();
        $pedidoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si ya existe ese número, incrementar el valor y volver a intentar
        if ($pedidoExistente) {
            $numero_pedido++;
        }
    } while ($pedidoExistente);

    // Cuando encontramos un número de pedido que no existe, lo retornamos
    return $numero_pedido;
}

// Crear la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Llamar a la función para generar el número de pedido único
$numero_pedido = generarNumeroPedido($db);


?>
