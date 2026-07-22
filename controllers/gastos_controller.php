<?php
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $conn = $database->getConnection();

    $fecha = $_POST["fecha"];
    $concepto = $_POST["concepto"];
    $categoria = $_POST["categoria"];
    $monto = $_POST["monto"];
    $cajero = $_POST["cajero"];
    $id_mesero = isset($_POST["mesero"]) && !empty($_POST["mesero"]) ? $_POST["mesero"] : null;

    try {
        if ($id_mesero) {

            // Insertar una nueva fila en 'gastos'
            $sql = "INSERT INTO gastos (fecha, concepto, categoria, monto, cajero, id_mesero) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$fecha, $concepto, $categoria, $monto, $cajero, $id_mesero]);

        } else {
            // Si no hay $id_mesero, insertar la fila en 'gastos'
            $sql = "INSERT INTO gastos (fecha, concepto, categoria, monto, cajero) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$fecha, $concepto, $categoria, $monto]);
        }

        // Redirigir o mostrar mensaje de éxito
        header("Location: ../public/index.php?page=gastos.php");
        exit();
    } catch (PDOException $e) {
        echo "Error al guardar el gasto: " . $e->getMessage();
    }
}

?>