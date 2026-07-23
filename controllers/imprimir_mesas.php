<?php
// Mostrar todos los errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de configuración de la base de datos
require_once '../config/database.php';

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Verificar si el id_pedido se ha pasado correctamente
if (isset($_GET['idpedi'])) {
    $id_pedido = $_GET['idpedi'];

    // Consulta para obtener los datos del pedido, productos, mesa y mesero
    $query = "SELECT p.producto, p.cantidad, p.detalle, pr.precio, m.numero_mesa, me.nombre_mese
              FROM pedidos AS p
              JOIN precios AS pr ON p.id_pro = pr.idproduc AND p.tipo_producto = pr.tipo_prod
              LEFT JOIN mesas AS m ON p.mesa = m.idm
              LEFT JOIN meseros AS me ON p.mesero = me.id_mese
              WHERE p.numero_pedido = :id_pedido";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
    $stmt->execute();

    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$productos) {
        echo "No se encontraron productos para este pedido.";
        exit;
    }

    // Obtener el nombre del mesero y el número de la mesa (si existen)
    $numero_mesa = $productos[0]['numero_mesa'] ?? 'No asignada';
    $nombre_mesero = $productos[0]['nombre_mese'] ?? 'No asignado';
} else {
    echo "No se proporcionó un ID de pedido válido.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Pedido</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h3>Detalles del Pedido</h3>

    <p><strong>Mesa:</strong> <?php echo $numero_mesa; ?></p>
    <p><strong>Mesero:</strong> <?php echo $nombre_mesero; ?></p>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $total = 0;
        foreach ($productos as $producto) {
            $subtotal = $producto['cantidad'] * $producto['precio'];
            $total += $subtotal;
            echo "<tr>
                    <td>{$producto['producto']}</td>
                    <td>{$producto['cantidad']}</td>
                    <td>\${$producto['precio']}</td>
                    <td>\$" . number_format($subtotal, 2) . "</td>
                    <td>{$producto['detalle']}</td>
                </tr>";
        }
        ?>
        </tbody>
    </table>
    <p><strong>Total del Pedido: $<?php echo number_format($total, 2); ?></strong></p>

    <!-- Botón de imprimir -->
    <button class="btn btn-primary" onclick="printInvoice(<?php echo $id_pedido; ?>)">Imprimir Pedido</button>
</div>

<script type="text/javascript" src="/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>
<script>
// Función para imprimir la factura utilizando QZ Tray
function printInvoice(numeroPedido) {
    ensureConnection().then(() => {
        console.log("Conectado a QZ Tray");

        // Verificar que los elementos existen antes de acceder a ellos
        const clienteElement = document.querySelector('p:contains("Cliente:")');
        const cliente = clienteElement ? clienteElement.innerText.replace('Cliente: ', '').trim() : 'Desconocido';

        const celularElement = document.querySelector('p:contains("Celular:")');
        const celular = celularElement ? celularElement.innerText.replace('Celular: ', '').trim() : 'No disponible';

        const direccionElement = document.querySelector('p:contains("Dirección:")');
        const direccion = direccionElement ? direccionElement.innerText.replace('Dirección: ', '').trim() : 'No disponible';

        let mesa = '';
        const mesaElement = document.querySelector('p:contains("Mesa:")');
        if (mesaElement) {
            mesa = mesaElement.innerText.replace('Mesa: ', '').trim();
        }

        const meseroElement = document.querySelector('p:contains("Mesero:")');
        const mesero = meseroElement ? meseroElement.innerText.replace('Mesero: ', '').trim() : 'No asignado';

        const totalElement = document.querySelector('p:contains("Total:")');
        const total = totalElement ? totalElement.innerText.replace('Total: $', '').trim() : '0';

        // Obtén los datos de productos y prefijos de la tabla
        const productos = Array.from(document.querySelectorAll('tbody tr')).map(tr => {
            const nombreCompleto = tr.children[0].innerText; // Nombre del producto
            const cantidad = tr.children[1].innerText; // Cantidad
            const prefijos = tr.children[2].innerText; // Prefijo
            const precio = tr.children[3].innerText.replace('$', '').replace(',', '').trim(); // Precio unitario
            const detalle = tr.children[4].innerText; // Detalle del producto
            const tipoProducto = tr.children[5].innerText; // Tipo de producto

            return { nombreCompleto, prefijos, cantidad, precio, detalle, tipoProducto };
        });

        // Formato de impresión con diseño de factura
        let contenido = `
        \x1B\x61\x00  
        \x1B\x21\x10  
        Restaurante Chao Guo\n
        \x1B\x21\x00  
        
        ${mesa ? 'Mesa N°: ' + mesa : 'Domicilio'}  
        Pedido N°: ${numeroPedido}
        Cliente: ${cliente}
        Celular: ${celular}
        Dirección: ${direccion}
        Mesero: ${mesero}\n`;  // Añadir el nombre del mesero
        
        contenido += `----------------------------------------\n`;
        contenido += `Producto      Cant.   Precio  Subtotal\n`;
        contenido += `----------------------------------------\n`;

        let totalCalculado = 0;
        productos.forEach((producto) => {
            const subtotal = producto.cantidad * parseFloat(producto.precio);
            totalCalculado += subtotal;

            // Incluye el prefijo en la impresión
            contenido += `${producto.prefijos}     ${producto.cantidad.toString().padEnd(6)} $${parseFloat(producto.precio).toFixed(2).padEnd(7)} $${subtotal.toFixed(2)}\n`;
            contenido += `- ${producto.detalle}\n`;
            contenido += `- ${producto.tipoProducto}\n`;
            contenido += `----------------------------------------\n`; // Línea separadora entre productos
        });

        contenido += `TOTAL: $${totalCalculado.toFixed(2)}\n`;
        contenido += `========================================\n`;
        contenido += `\n\n\n\n`;

        // Comando para cortar papel al final de la impresión
        contenido += `\x1D\x56\x42\x00`;

        var config = qz.configs.create("POS-58");

        qz.print(config, [{
            type: 'raw',
            format: 'plain',
            data: contenido
        }]).then(() => console.log("Impresión completada"))
        .catch(err => console.error("Error al imprimir:", err));

    }).catch(err => console.error("Error al conectar a QZ Tray:", err));
}

</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
