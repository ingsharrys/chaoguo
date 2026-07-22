<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Comprobar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pro = $_POST['id_pro'];
    $nombre = $_POST['nombre'];
    $prefijo = $_POST['prefijo'];
    $cat = $_POST['cat'];
    $descript = $_POST['descript'];
    // Inicializar variables para los valores de imagen y precio
    $precios = $_POST['precios'];
    $tipo_prod = $_POST['tipos'];
    $ti_comida = $_POST['tcomida'];    
    
    // Verificar si se subió una nueva imagen
    if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {

        // Seleccionar la imagen
        $img = $_FILES['img'];

        // Sanear el nombre del archivo
        $filename = pathinfo($img['name'], PATHINFO_FILENAME);
        // Eliminar tildes y caracteres especiales
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
        // Aplicar la expresión regular para eliminar otros caracteres no deseados
        $filename = preg_replace("/[^a-zA-Z0-9\.\-\_]/", "", $filename);
        $fileExtension = pathinfo($img['name'], PATHINFO_EXTENSION);
        // Generar un nombre único para el archivo
        $imgName = 'producto-' . $filename . '-' . uniqid() . '.' . $fileExtension;
        // Ruta donde se guarda la imagen
        $imgPath = '../path/to/productos/' . $imgName;

        if (!move_uploaded_file($img['tmp_name'], $imgPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Error al mover la imagen.']);
            exit;
        }

    }
    
    try {

        // Crear una conexión a la base de datos
        $db = new Database();
        $conn = $db->getConnection();

        if (isset($imgName)) {
            // Obtener la ruta de la imagen del producto para eliminarla
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
        }

        // Preparar la consulta de actualización
        $query = "UPDATE productos SET nombre = :nombre, prefijo = :prefijo, cat = :cat, descript = :descript, tcomida = :tcomida";

        // Añadir la actualización de la imagen si hay una nueva
        if (isset($imgName)) {
            $query .= ", img = :img";
        }

        $query .= " WHERE id_pro = :id_pro";

        // Preparar la consulta
        $stmt = $conn->prepare($query);

        // Bind de parámetros
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':prefijo', $prefijo);
        $stmt->bindParam(':cat', $cat);
        $stmt->bindParam(':descript', $descript);
        $stmt->bindParam(':tcomida', $ti_comida);
        $stmt->bindParam(':id_pro', $id_pro);
        

        // Bind de la imagen si es necesario
        if (isset($imgName)) {
            $stmt->bindParam(':img', $imgName);
        }

        // Ejecutar la consulta de actualización
        $stmt->execute();

        
        // Obtener las variantes existentes en la base de datos
        $queryExisting = "SELECT tipo_prod FROM precios WHERE idproduc = :id_pro";
        $stmtExisting = $conn->prepare($queryExisting);
        $stmtExisting->bindParam(':id_pro', $id_pro);
        $stmtExisting->execute();
        $existingVariants = $stmtExisting->fetchAll(PDO::FETCH_COLUMN);

        // Identificar las variantes que deben eliminarse
        $newVariants = array_map('strval', $tipo_prod); // Convertir a string para asegurar la comparación
        $variantsToDelete = array_diff($existingVariants, $newVariants);

        // Eliminar las variantes no presentes en el formulario
        if (!empty($variantsToDelete)) {
            $deleteQuery = "DELETE FROM precios WHERE idproduc = :id_pro AND tipo_prod = :tipo_prod";
            $stmtDelete = $conn->prepare($deleteQuery);
            foreach ($variantsToDelete as $variantToDelete) {
                $stmtDelete->bindParam(':id_pro', $id_pro);
                $stmtDelete->bindParam(':tipo_prod', $variantToDelete);
                $stmtDelete->execute();
            }
        }

        // Actualizar o insertar las variantes enviadas en el formulario
        foreach ($tipo_prod as $index => $tipo) {
            $precio = $precios[$index];

            // Verificar si la variante ya existe
            if (in_array($tipo, $existingVariants)) {
                // Actualizar variante existente
                $queryPrecio = "UPDATE precios SET precio = :precio WHERE idproduc = :id_pro AND tipo_prod = :tipo_prod";
            } else {
                // Insertar nueva variante
                $queryPrecio = "INSERT INTO precios (idproduc, tipo_prod, precio) VALUES (:id_pro, :tipo_prod, :precio)";
            }

            $stmtPrecio = $conn->prepare($queryPrecio);
            $stmtPrecio->bindParam(':id_pro', $id_pro);
            $stmtPrecio->bindParam(':tipo_prod', $tipo);
            $stmtPrecio->bindParam(':precio', $precio);
            $stmtPrecio->execute();
        }

        // Enviar respuesta de éxito
        echo json_encode(['status' => 'success', 'message' => 'Producto editado con éxito.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al editar el producto: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de solicitud no permitido.']);
}
?>
