<?php
// Mostrar errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php'; // Incluye la conexión a la base de datos
$database = new Database();
$conn = $database->getConnection();

// Verificar si se recibieron los datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos enviados por POST
    $data = json_decode(file_get_contents('php://input'), true);
    $numero_pedido = isset($data['numero_pedido']) ? $data['numero_pedido'] : null;
    $codigo_seguridad = isset($data['codigo_seguridad']) ? $data['codigo_seguridad'] : null;

    if ($numero_pedido && $codigo_seguridad) {
        // Verificar el código de seguridad en la base de datos
        $querySeguridad = "SELECT codigo_seguridad FROM seguridad WHERE codigo_seguridad = :codigo_seguridad";
        $stmtSeguridad = $conn->prepare($querySeguridad);
        $stmtSeguridad->bindParam(':codigo_seguridad', $codigo_seguridad, PDO::PARAM_STR);
        $stmtSeguridad->execute();
        $codigoValido = $stmtSeguridad->fetch(PDO::FETCH_ASSOC);

        if (!$codigoValido) {
            echo json_encode(['success' => false, 'message' => 'Código de seguridad incorrecto.']);
            exit;
        }

        // Si el código es válido, proceder a eliminar el registro de la tabla 'caja'
        $query = "DELETE FROM caja WHERE id_pedidoc = :id_pedidoc";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_pedidoc', $numero_pedido, PDO::PARAM_INT);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Registro de caja eliminado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el registro de caja.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Número de pedido o código de seguridad no proporcionado.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
