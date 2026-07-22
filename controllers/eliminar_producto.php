<?php

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();
header('Content-Type: application/json');

// Obtener el contenido JSON enviado por POST
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pro = isset($data['id_pro']) ? (int)$data['id_pro'] : 0;

    if (!$id_pro) {
        echo json_encode(['status' => 'error', 'message' => 'ID de producto inválido o no proporcionado.']);
        exit;
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Iniciar transacción
        $conn->beginTransaction();

        // Obtener la ruta de la imagen del producto
        $getImageQuery = "SELECT img FROM productos WHERE id_pro = :id_pro";
        $stmtImage = $conn->prepare($getImageQuery);
        $stmtImage->bindParam(':id_pro', $id_pro, PDO::PARAM_INT);
        $stmtImage->execute();
        $product = $stmtImage->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['status' => 'error', 'message' => 'El producto no existe.']);
            $conn->rollBack();
            exit;
        }

        $imagePath = '../path/to/productos/' . $product['img'];

        // Verificar si la imagen existe y eliminarla
        if (file_exists($imagePath) && is_file($imagePath)) {
            unlink($imagePath);
        }

        // Eliminar de precios
        $deletePricesQuery = "DELETE FROM precios WHERE idproduc = :id_pro";
        $stmtPrices = $conn->prepare($deletePricesQuery);
        $stmtPrices->bindParam(':id_pro', $id_pro, PDO::PARAM_INT);
        $stmtPrices->execute();

        // Eliminar de productos
        $deleteProductQuery = "DELETE FROM productos WHERE id_pro = :id_pro";
        $stmtProduct = $conn->prepare($deleteProductQuery);
        $stmtProduct->bindParam(':id_pro', $id_pro, PDO::PARAM_INT);
        $stmtProduct->execute();

        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => 'Producto e imagen eliminados exitosamente.']);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Error de PDO: ' . $e->getMessage()]);
    }
}