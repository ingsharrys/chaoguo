<?php
// Mostrar errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php'; // Incluye la conexión a la base de datos
$database = new Database();
$conn = $database->getConnection();

// Verificar si se recibieron los datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_pedido'], $_POST['costo_domicilio'])) {
    $numero_pedido = $_POST['numero_pedido'];
    $costo_domicilio = $_POST['costo_domicilio'];

    // Obtener los detalles del pedido y datos del cliente
    $query_pedido = "SELECT pr.nombre AS nombre_producto, p.cantidad, prp.precio AS precio_producto, p.detalle, p.tipo_producto, c.cliente, c.celular, c.direccion 
                     FROM pedidos p 
                     JOIN productos pr ON p.producto = pr.nombre 
                     JOIN precios prp ON pr.id_pro = prp.idproduc 
                     JOIN clientes c ON p.id_cliente = c.id
                     WHERE p.numero_pedido = ? AND prp.tipo_prod = p.tipo_producto";
    $stmt_pedido = $conn->prepare($query_pedido);
    $stmt_pedido->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $stmt_pedido->execute();
    $detalles_pedido = $stmt_pedido->fetchAll(PDO::FETCH_ASSOC);

    // Extraer los datos del cliente desde el resultado de la consulta
    if (count($detalles_pedido) > 0) {
        $cliente = $detalles_pedido[0]['cliente'];
        $celular = $detalles_pedido[0]['celular'];
        $direccion = $detalles_pedido[0]['direccion'];
    } else {
        $cliente = '';
        $celular = '';
        $direccion = '';
    }

    // Calcular el total de productos
    $total_productos = array_reduce($detalles_pedido, function($carry, $item) {
        return $carry + ($item['precio_producto'] * $item['cantidad']);
    }, 0);

    // Convertir costo_domicilio a flotante
    $costo_domicilio = (float)$costo_domicilio;

    // Calcular el total a pagar incluyendo el costo del domicilio
    $total_a_pagar = $total_productos + $costo_domicilio;

    // Verificar si ya existe un registro de pago para este pedido
    $query_check_pago = "SELECT COUNT(*) as count FROM caja WHERE id_pedidoc = :id_pedidoc";
    $stmt_check_pago = $conn->prepare($query_check_pago);
    $stmt_check_pago->bindParam(':id_pedidoc', $numero_pedido);
    $stmt_check_pago->execute();
    $pago_existente = $stmt_check_pago->fetch(PDO::FETCH_ASSOC)['count'] > 0;

    // Verificar si se ha enviado el método de pago y la cantidad pagada
    if (isset($_POST['m_pago']) && !$pago_existente) {
        $metodo_pago = $_POST['m_pago'];
        $efectivo = isset($_POST['pago']) && $_POST['pago'] !== '' ? (float)$_POST['pago'] : null;

        // Convertir total_a_pagar a flotante para asegurar la correcta operación matemática
        $total_a_pagar = (float)$total_a_pagar;

        // Procesar el pago dependiendo del método
        if ($metodo_pago === 'efectivo' && $efectivo !== null) {
            $cambio = $efectivo - $total_a_pagar;

            // Verificar que el efectivo sea suficiente antes de procesar el pago
            if ($efectivo < $total_a_pagar) {
                $mensaje_error = "Falta dinero para completar el pago.";
            } else {
                // Insertar en la tabla caja
                $query_caja = "INSERT INTO caja (id_pedidoc, costo, m_pago, efectivo) VALUES (:id_pedidoc, :costo, :m_pago, :efectivo)";
                $stmt_caja = $conn->prepare($query_caja);
                $stmt_caja->bindParam(':id_pedidoc', $numero_pedido);
                $stmt_caja->bindParam(':costo', $total_a_pagar);
                $stmt_caja->bindParam(':m_pago', $metodo_pago);
                $stmt_caja->bindParam(':efectivo', $efectivo);
                $stmt_caja->execute();

                // En lugar de redirigir con PHP, usamos JavaScript para recargar la página
                echo "<script>location.reload();</script>";
                exit;
            }
        } else {
            // Procesar los otros métodos de pago sin calcular el cambio
            $efectivo = $total_a_pagar; // Asignar efectivo al total a pagar para métodos distintos de efectivo

            // Insertar en la tabla caja para otros métodos de pago
            $query_caja = "INSERT INTO caja (id_pedidoc, costo, m_pago, efectivo) VALUES (:id_pedidoc, :costo, :m_pago, :efectivo)";
            $stmt_caja = $conn->prepare($query_caja);
            $stmt_caja->bindParam(':id_pedidoc', $numero_pedido);
            $stmt_caja->bindParam(':costo', $total_a_pagar);
            $stmt_caja->bindParam(':m_pago', $metodo_pago);
            $stmt_caja->bindValue(':efectivo', $efectivo, PDO::PARAM_STR); // Asegurarse de que el valor sea un número válido
            $stmt_caja->execute();

            // Recargar la página después de procesar el pago
            echo "<script>location.reload();</script>";
            exit;
        }
    }
} else {
    echo "Datos incompletos para procesar la solicitud.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago del Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script type="text/javascript" src="/qz-tray.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>
</head>
<body>
    <div class="container">
        <h1>Pago del Pedido <?php echo htmlspecialchars($numero_pedido); ?></h1>

        <h2>Detalles del Pedido</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                    <th>Detalle</th>
                    <th>Tipo de Producto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles_pedido as $detalle): ?>
                <tr>
                    <td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td>
                    <td><?php echo htmlspecialchars($detalle['cantidad']); ?></td>
                    <td><?php echo "$" . number_format($detalle['precio_producto'], 0, '', ','); ?></td>
                    <td><?php echo "$" . number_format($detalle['precio_producto'] * $detalle['cantidad'], 0, '', ','); ?></td>
                    <td><?php echo htmlspecialchars($detalle['detalle']); ?></td>
                    <td><?php echo htmlspecialchars($detalle['tipo_producto']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p><strong>Total de Productos:</strong> <?php echo "$" . number_format($total_productos, 0, '', ','); ?></p>
        <p><strong>Costo del Domicilio:</strong> <?php echo "$" . number_format($costo_domicilio, 0, '', ','); ?></p>
        <p><strong>Total a Pagar:</strong> <?php echo "$" . number_format($total_a_pagar, 0, '', ','); ?></p>

        <!-- Datos del Cliente -->
        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($cliente); ?></p>
        <p><strong>Celular:</strong> <?php echo htmlspecialchars($celular); ?></p>
        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($direccion); ?></p>

        <h2>Pago</h2>
        <?php if ($pago_existente): ?>
            <button href="" class="btn btn-success">PAGADO</button>
            <a href="../public/index.php?page=whatsapp.php" class="btn btn-warning">Volver</a>
            <button type="button" class="btn btn-primary" onclick="abrirCajon()">Abrir Caja</button>
        <?php else: ?>
            <form action="" method="POST">
                <input type="hidden" name="numero_pedido" value="<?php echo htmlspecialchars($numero_pedido); ?>">
                <input type="hidden" name="costo_domicilio" value="<?php echo htmlspecialchars($costo_domicilio); ?>">
                <input type="hidden" id="total" value="<?php echo $total_a_pagar; ?>">

                <label for="m_pago">Método de Pago:</label>
                <select name="m_pago" id="m_pago" onchange="toggleEfectivoInput()" required>
                    <option value="">Seleccionar</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>

                <div id="efectivoInput" style="display: none;">
                    <label for="pago">Cantidad con la que el cliente va a pagar:</label>
                    <input type="number" id="pago" name="pago" step="0.01" oninput="calcularCambio()">
                    <p id="resultado"></p>
                </div>

                <?php if (isset($mensaje_error)): ?>
                    <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($mensaje_error); ?></div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">Procesar Pago</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function calcularCambio() {
            var total = parseFloat(document.getElementById('total').value);
            var pago = parseFloat(document.getElementById('pago').value);
            var cambio = pago - total;

            if (cambio < 0) {
                document.getElementById('resultado').textContent = "Falta dinero.";
            } else if (cambio === 0) {
                document.getElementById('resultado').textContent = "Pagado.";
            } else {
                document.getElementById('resultado').textContent = "Cambio a devolver: $" + cambio.toFixed(2);
            }
        }

        function toggleEfectivoInput() {
            var metodoPago = document.getElementById('m_pago').value;
            var efectivoInput = document.getElementById('efectivoInput');
            if (metodoPago === 'efectivo') {
                efectivoInput.style.display = 'block';
            } else {
                efectivoInput.style.display = 'none';
                document.getElementById('pago').value = ''; // Resetear valor del input
                document.getElementById('resultado').textContent = ''; // Limpiar resultado
            }
        }

        // Cargar el certificado digital
        qz.security.setCertificatePromise(function(resolve, reject) {
            fetch('../digital-certificate.txt')
                .then(response => response.text())
                .then(data => resolve(data))
                .catch(err => reject(err));
        });

        // Configurar las firmas digitales
        qz.security.setSignaturePromise(function(toSign) {
            return function(resolve, reject) {
                fetch('../private-key.pem')
                    .then(response => response.text())
                    .then(pk => {
                        if (!pk.includes('-----BEGIN PRIVATE KEY-----') || !pk.includes('-----END PRIVATE KEY-----')) {
                            reject('Formato de clave PEM incorrecto o no encontrado.');
                            return;
                        }

                        var sig = new KJUR.crypto.Signature({ "alg": "SHA1withRSA" });
                        sig.init(pk);
                        sig.updateString(toSign);
                        var sign = sig.sign();
                        resolve(sign);
                    })
                    .catch(err => reject('Error al cargar la clave privada: ' + err));
            };
        });

        function ensureConnection() {
            if (!qz.websocket.isActive()) {
                return qz.websocket.connect();
            } else {
                return Promise.resolve();
            }
        }

        function findElementWithText(tag, text) {
            const elements = document.querySelectorAll(tag);
            for (let element of elements) {
                if (element.innerText.includes(text)) {
                    return element;
                }
            }
            return null;
        }

        function printInvoice(numeroPedido) {
            ensureConnection().then(() => {
                console.log("Conectado a QZ Tray");

                const clienteElement = findElementWithText('p', 'Cliente:');
                const cliente = clienteElement ? clienteElement.innerText.replace('Cliente: ', '').trim() : '';

                const celularElement = findElementWithText('p', 'Celular:');
                const celular = celularElement ? celularElement.innerText.replace('Celular: ', '').trim() : '';

                const direccionElement = findElementWithText('p', 'Dirección:');
                const direccion = direccionElement ? direccionElement.innerText.replace('Dirección: ', '').trim() : '';

                let mesa = ''; 
                const totalElement = findElementWithText('p', 'Total a Pagar:');
                const total = totalElement ? totalElement.innerText.replace('Total a Pagar: $', '').trim() : '';

                const productos = Array.from(document.querySelectorAll('table tbody tr')).map(tr => {
                    const nombreProducto = tr.children[0] ? tr.children[0].innerText.slice(0, 15) : ''; // Truncar a 10 caracteres
                    const cantidad = tr.children[1] ? parseInt(tr.children[1].innerText.trim(), 10) : 0; 
                    const precioUnitario = tr.children[2] ? parseFloat(tr.children[2].innerText.replace('$', '').replace(',', '').trim()) : 0; 
                    const subtotal = tr.children[3] ? tr.children[3].innerText : ''; 
                    const detalle = tr.children[4] ? tr.children[4].innerText : ''; 
                    const tipoProducto = tr.children[5] ? tr.children[5].innerText : ''; 

                    return { nombreProducto, cantidad, precioUnitario, detalle, tipoProducto, subtotal };
                });

                let contenido = `
                \x1B\x61\x00  
                \x1B\x21\x10  
                Restaurante Chao Guo\n
                \x1B\x21\x00  
                
                Pedido N°: ${numeroPedido}
                Cliente: ${cliente}
                Celular: ${celular}
                Dirección: ${direccion}
                ${mesa ? 'Mesa: ' + mesa : 'Domicilio'}`;
                contenido += `\n----------------------------------------\n`;
                contenido += `Producto      Cant.   Precio  Subtotal\n`;
                contenido += `----------------------------------------\n`;

                let totalCalculado = 0;
                productos.forEach((producto) => {
                    const subtotal = producto.cantidad * producto.precioUnitario;
                    totalCalculado += subtotal;

                    contenido += `${producto.nombreProducto.padEnd(10)} ${producto.cantidad.toString().padEnd(6)} $${producto.precioUnitario.toFixed(0).padEnd(7)} $${subtotal.toFixed(0)}\n`;

                    contenido += `-  ${producto.detalle}\n`;
                    contenido += `-  ${producto.tipoProducto}\n`;
                    contenido += `----------------------------------------\n`; 
                });

                contenido += `TOTAL: $${totalCalculado.toFixed(2)}\n`;
                contenido += `========================================\n`;
                contenido += `\n\n\n\n`;

                contenido += `\x1D\x56\x42\x00`;

                var config = qz.configs.create("POS-80");

               // Enviar comando para abrir la caja registradora
            qz.print(config, [{
                type: 'raw',
                format: 'plain',
                data: openCashDrawerCommand
            }]).then(() => console.log("Caja registradora abierta")).catch(err => console.error("Error al abrir la caja registradora:", err));

        }).catch(err => console.error("Error al imprimir:", err));
    }).catch(err => console.error("Error al conectar a QZ Tray:", err));
}

    </script>
</body>
</html>
<script src="../public/js/impresion.js?cache=v2"></script>
