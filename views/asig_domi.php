<?php
ob_start(); // Inicia el buffer de salida

// Mostrar todos los errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php'; // Incluye la conexión a la base de datos
$database = new Database();
$conn = $database->getConnection();

// Verificar si se ha enviado el formulario de asignación de domiciliario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_domiciliario'])) {
    if (isset($_POST['numero_pedido'], $_POST['costo_domicilio'], $_POST['id_domiciliario'])) {
        $numero_pedido = $_POST['numero_pedido'];
        $costo_domicilio = $_POST['costo_domicilio'];
        $id_domiciliario = $_POST['id_domiciliario'];

        // Verificar si el id_pedido ya existe en la tabla domicilios
        $query_check = "SELECT COUNT(*) FROM domicilios WHERE id_pedido = :id_pedido";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bindParam(':id_pedido', $numero_pedido);
        $stmt_check->execute();
        $exists = $stmt_check->fetchColumn();

        if ($exists) {
            // Si existe, hacer un update
            $update_query = "UPDATE domicilios SET id_domi = :id_domi, precio = :precio WHERE id_pedido = :id_pedido";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bindParam(':id_pedido', $numero_pedido);
            $stmt_update->bindParam(':id_domi', $id_domiciliario);
            $stmt_update->bindParam(':precio', $costo_domicilio);

            if ($stmt_update->execute()) {
                // Redirigir después de la actualización exitosa
                echo '<script type="text/javascript">
            window.location.href = "../public/index.php?page=whatsapp.php";
          </script>';
            } else {
                echo "Error al actualizar el domiciliario en la base de datos.";
            }
        } else {
            // Si no existe, hacer un insert
            $insert_query = "INSERT INTO domicilios (id_pedido, id_domi, precio) VALUES (:id_pedido, :id_domi, :precio)";
            $stmt_insert = $conn->prepare($insert_query);
            $stmt_insert->bindParam(':id_pedido', $numero_pedido);
            $stmt_insert->bindParam(':id_domi', $id_domiciliario);
            $stmt_insert->bindParam(':precio', $costo_domicilio);

            if ($stmt_insert->execute()) {
                // Redirigir después de la inserción exitosa
                echo '<script type="text/javascript">
            window.location.href = "../public/index.php?page=whatsapp.php";
          </script>';
            } else {
                echo "Error al insertar el domiciliario en la base de datos.";
            }
        }
    } else {
        echo "Datos incompletos para procesar la solicitud.";
    }
} else {
    // Si no se ha enviado el formulario de asignación, mostrar los detalles del pedido
    if (isset($_POST['numero_pedido']) && isset($_POST['costo_domicilio'])) {
        $numero_pedido = $_POST['numero_pedido'];
        $costo_domicilio = $_POST['costo_domicilio'];

        // Obtener los detalles del pedido
        $query_pedido = "SELECT p.numero_pedido, p.detalle, p.fecha, c.cliente, c.celular, c.email, c.direccion
                         FROM pedidos p 
                         JOIN clientes c ON p.id_cliente = c.id
                         WHERE p.numero_pedido = :numero_pedido";
        $stmt_pedido = $conn->prepare($query_pedido);
        $stmt_pedido->bindParam(':numero_pedido', $numero_pedido);
        $stmt_pedido->execute();
        $pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

        // Obtener el domiciliario asignado si existe
        $query_asignado = "SELECT id_domi FROM domicilios WHERE id_pedido = :id_pedido";
        $stmt_asignado = $conn->prepare($query_asignado);
        $stmt_asignado->bindParam(':id_pedido', $numero_pedido);
        $stmt_asignado->execute();
        $domiciliario_asignado = $stmt_asignado->fetchColumn();

        // Obtener los domiciliarios
        $query_domiciliarios = "SELECT id_e, repartidor FROM domiciliarios";
        $stmt_domiciliarios = $conn->prepare($query_domiciliarios);
        $stmt_domiciliarios->execute();
        $domiciliarios = $stmt_domiciliarios->fetchAll(PDO::FETCH_ASSOC);
    } else {
        echo "Datos incompletos para procesar la solicitud.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Domiciliario</title>
</head>
<body>
    <h1>Asignar Domiciliario</h1>
    <?php if (isset($pedido) && $pedido): ?>
        <h2>Detalles del Pedido</h2>
        <p><strong>Pedido N°:</strong> <?php echo htmlspecialchars($pedido['numero_pedido']); ?></p>
        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['cliente']); ?></p>
        <p><strong>Celular:</strong> <?php echo htmlspecialchars($pedido['celular']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['email']); ?></p>
        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['direccion']); ?></p>
        <p><strong>Costo del Domicilio:</strong> <?php echo htmlspecialchars($costo_domicilio); ?></p>

        <form action="" method="POST">
            <input type="hidden" name="numero_pedido" value="<?php echo htmlspecialchars($pedido['numero_pedido']); ?>">
            <input type="hidden" name="costo_domicilio" value="<?php echo htmlspecialchars($costo_domicilio); ?>">

            <label for="id_domiciliario">Selecciona un Domiciliario:</label>
            <select name="id_domiciliario" id="id_domiciliario" required>
                <option value="">--Seleccionar--</option>
                <?php foreach ($domiciliarios as $domiciliario): ?>
                    <option value="<?php echo htmlspecialchars($domiciliario['id_e']); ?>" <?php echo (isset($domiciliario_asignado) && $domiciliario_asignado == $domiciliario['id_e']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($domiciliario['repartidor']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Asignar Domiciliario</button>
        </form>
    <?php else: ?>
        <p>No se encontraron detalles para el pedido.</p>
    <?php endif; ?>
</body>
</html>

<?php ob_end_flush(); // Fin del buffer de salida ?>
