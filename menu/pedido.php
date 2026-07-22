<?php
require_once '../config/database.php';

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Obtener los valores de los parámetros GET
$client = isset($_GET['client']) ? $_GET['client'] : '';
$address = isset($_GET['addres']) ? $_GET['addres'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';
$comments = isset($_GET['comenta']) ? $_GET['comenta'] : '';

// Crear un array vacío para almacenar los productos
$productos = [];

// Obtener los productos recibidos en la URL y consultar la base de datos para obtener los detalles
for ($i = 1; isset($_GET["producto$i"]); $i++) {
    $productoId = $_GET["producto$i"];
    $cantidad = $_GET["cantidad$i"];
    $precio = $_GET["precio$i"];
    $color = isset($_GET["color$i"]) ? $_GET["color$i"] : '';

    // Consultar la tabla 'productos' y 'precios' para obtener los detalles del producto
    $query = "SELECT p.nombre, pr.tipo_prod 
              FROM productos p 
              LEFT JOIN precios pr ON p.id_pro = pr.idproduc 
              WHERE p.id_pro = :id_pro AND pr.precio = :precio";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_pro', $productoId);
    $stmt->bindParam(':precio', $precio);
    $stmt->execute();
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si se encuentra el producto, agregarlo al array
    if ($producto) {
        $productos[] = [
            'nombre' => $producto['nombre'],
            'cantidad' => $cantidad,
            'precio' => $precio,
            'tipo' => $producto['tipo_prod'],
            'color' => $color
        ];
    } else {
        // Si no se encuentra el producto, agregarlo con valores predeterminados
        $productos[] = [
            'nombre' => 'Producto no encontrado',
            'cantidad' => $cantidad,
            'precio' => $precio,
            'tipo' => 'Tipo no encontrado',
            'color' => $color
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario Pedido</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .table th, .table td {
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .quantity-controls {
                display: flex;
                justify-content: center;
            }
            .quantity-controls input {
                width: 50px;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .table th, .table td {
                font-size: 12px;
                padding: 10px;
            }

            .table th:first-child, .table td:first-child {
                width: 100%;
                text-align: left;
            }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2>Detalles del Pedido</h2>
    <form action="procesar_pedido.php" method="POST">
        <div class="form-group">
            <label for="client">Cliente</label>
            <input type="text" class="form-control" id="client" name="client" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div class="form-group">
            <label for="address">Dirección</label>
            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
        </div>
        <div class="form-group">
            <label for="phone">Teléfono</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($client); ?>" required>
        </div>
        <div class="form-group">
            <label for="comments">Comentarios</label>
            <textarea class="form-control" id="comments" name="comments" rows="3"><?php echo htmlspecialchars($comments); ?></textarea>
        </div>

        <h3>Productos Recibidos</h3>
        <table class="table table-bordered table-responsive">
            <thead class="thead-light">
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Detalles</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $index => $producto): ?>
                <tr>
                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                    <input type="hidden" name="producto[]" value="<?php echo htmlspecialchars($productoId); ?>">
                    <td class="quantity-controls">
                        <button type="button" class="btn btn-secondary btn-minus" data-index="<?php echo $index; ?>">-</button>
                        <input type="number" class="form-control d-inline-block text-center quantity-input" data-index="<?php echo $index; ?>" name="cantidad[]" value="<?php echo htmlspecialchars($producto['cantidad']); ?>" min="1" style="width: 60px;">
                        <button type="button" class="btn btn-secondary btn-plus" data-index="<?php echo $index; ?>">+</button>
                    </td>
                    <td><?php echo htmlspecialchars($producto['precio']); ?></td>
                    <td>
                        <strong>Tipo:</strong> <?php echo htmlspecialchars($producto['tipo']); ?><br>
                        <strong>Color:</strong> <?php echo htmlspecialchars($producto['color']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn-primary mt-3">Enviar Pedido</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
$(document).ready(function () {
    // Aumentar o disminuir la cantidad de productos recibidos
    $('.btn-plus').on('click', function () {
        const index = $(this).data('index');
        const input = $(`input.quantity-input[data-index="${index}"]`);
        input.val(parseInt(input.val()) + 1);
    });

    $('.btn-minus').on('click', function () {
        const index = $(this).data('index');
        const input = $(`input.quantity-input[data-index="${index}"]`);
        const currentValue = parseInt(input.val());
        if (currentValue > 1) {
            input.val(currentValue - 1);
        }
    });
});
</script>
</body>
</html>
