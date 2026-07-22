<?php
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? null;
    $prefijo = $_POST['prefijo'] ?? null;
    $cat = $_POST['cat'] ?? null;
    $descript = $_POST['descript'] ?? null;
    // Leer el campo tcomida
    $tcomida = $_POST['tcomida'] ?? null;
    
    $img = $_FILES['img'] ?? null;
    $tipo_productos = $_POST['tipo_producto'];  // Array de tipos de producto
    $precios = $_POST['precio_producto'];         // Array de precios

    if (!$nombre || !$prefijo || !$cat || !$descript || !$tcomida || !$img || !$tipo_productos || !$precios) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    if ($img['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Error al subir la imagen: ' . $img['error']]);
        exit;
    }

    // Sanear el nombre del archivo
    $filename = pathinfo($img['name'], PATHINFO_FILENAME);
    $filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
    $filename = preg_replace("/[^a-zA-Z0-9\.\-\_]/", "", $filename);
    $fileExtension = pathinfo($img['name'], PATHINFO_EXTENSION);
    $imgName = 'producto-' . $filename . '-' . uniqid() . '.' . $fileExtension;
    $imgPath = '../path/to/productos/' . $imgName;

    if (!move_uploaded_file($img['tmp_name'], $imgPath)) {
        echo json_encode(['status' => 'error', 'message' => 'Error al mover la imagen.']);
        exit;
    }

    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Verificar si el producto ya existe
        $checkQuery = "SELECT COUNT(*) FROM productos WHERE nombre = :nombre";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':nombre', $nombre);
        $checkStmt->execute();
        $productExists = $checkStmt->fetchColumn();

        if ($productExists) {
            echo json_encode(['status' => 'error', 'message' => 'El producto ya existe.']);
            exit;
        }

        // Insertar el producto, incluyendo el campo tcomida
        $query = "INSERT INTO productos (nombre, prefijo, descript, cat, tcomida, img) VALUES (:nombre, :prefijo, :descript, :cat, :tcomida, :img)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':prefijo', $prefijo);
        $stmt->bindParam(':descript', $descript);
        $stmt->bindParam(':cat', $cat);
        $stmt->bindParam(':tcomida', $tcomida);
        $stmt->bindParam(':img', $imgName);

        if ($stmt->execute()) {
            $idproduc = $conn->lastInsertId();

            // Insertar en la tabla precios
            foreach ($tipo_productos as $index => $tipo) {
                $precio = $precios[$index];
                $queryPrecios = "INSERT INTO precios (idproduc, tipo_prod, precio) VALUES (:idproduc, :tipo_prod, :precio)";
                $stmtPrecios = $conn->prepare($queryPrecios);
                $stmtPrecios->bindParam(':idproduc', $idproduc);
                $stmtPrecios->bindParam(':tipo_prod', $tipo);
                $stmtPrecios->bindParam(':precio', $precio);
                $stmtPrecios->execute();
            }

            echo json_encode(['status' => 'success', 'message' => 'Producto y precios agregados exitosamente.']);
        } else {
            $errorInfo = $stmt->errorInfo();
            echo json_encode(['status' => 'error', 'message' => 'Error al insertar producto: ' . $errorInfo[2]]);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de PDO: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>
