<?php

require_once '../config/database.php';

// Verificar si el parámetro 'code' está presente en la URL
if (isset($_GET['code'])) {
    $shortCode = $_GET['code'];

    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // Preparar y ejecutar la consulta
    $sql = "SELECT long_url FROM urls WHERE short_code = :shortCode";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':shortCode', $shortCode);
    $stmt->execute();

    // Obtener el resultado
    $longUrl = $stmt->fetchColumn();

    // Redirigir a la URL larga si se encuentra
    if ($longUrl) {
        header("Location: " . $longUrl);
        exit();
    } else {
        echo "URL corta no encontrada.";
    }

    $conn = null;
} else {
    echo "No se proporcionó ningún código.";
}
?>
