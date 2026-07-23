<?php
require_once '../helpers/Session.php';
Session::start();
require_once '../config/database.php';

if (!Session::get('user_id')) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
date_default_timezone_set('America/Bogota');

// Obtener el ID del domiciliario seleccionado
$id_e = isset($_POST['id_e']) ? $_POST['id_e'] : null;

// Si se envi�� una fecha desde el formulario, usarla, de lo contrario usar la fecha actual
$fecha_actual = isset($_POST['fecha_filtro']) ? $_POST['fecha_filtro'] : date('Y-m-d');

// Obtener el nombre del domiciliario
$domiciliario_nombre = '';
if ($id_e) {
    $query_nombre = "SELECT repartidor FROM domiciliarios WHERE id_e = :id_e";
    $stmt_nombre = $conn->prepare($query_nombre);
    $stmt_nombre->bindParam(':id_e', $id_e, PDO::PARAM_INT);
    $stmt_nombre->execute();
    $resultado_nombre = $stmt_nombre->fetch(PDO::FETCH_ASSOC);
    if ($resultado_nombre) {
        $domiciliario_nombre = $resultado_nombre['repartidor'];
    }
}

// Consulta para obtener los domicilios realizados por el domiciliario, filtrados por fecha
$query = "SELECT d.id_pedido, t.turno
          FROM domicilios d
          JOIN pedidos p ON d.id_pedido = p.numero_pedido
          LEFT JOIN turnero t ON t.id_pedido = p.numero_pedido
          WHERE d.id_domi = :id_domi
          AND DATE(p.fecha) = :fecha_actual";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id_domi', $id_e, PDO::PARAM_INT);
$stmt->bindParam(':fecha_actual', $fecha_actual);  
$stmt->execute();
$domicilios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Funciones auxiliares para obtener detalles del pedido, comentarios y si est�� pagado
function obtenerDetallesPedido($conn, $numero_pedido) {
    $detalle_query = "SELECT pr.nombre AS nombre_producto, p.cantidad, prp.precio AS precio_producto, p.detalle, p.mesa, p.tipo_producto
                      FROM pedidos p 
                      JOIN productos pr ON p.id_pro = pr.id_pro
                      JOIN precios prp ON pr.id_pro = prp.idproduc 
                      WHERE p.numero_pedido = ? AND prp.tipo_prod = p.tipo_producto";
    $detalle_stmt = $conn->prepare($detalle_query);
    $detalle_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $detalle_stmt->execute();
    return $detalle_stmt->fetchAll(PDO::FETCH_ASSOC);
}

function esPagado($conn, $numero_pedido) {
    $query = "SELECT COUNT(*) as count FROM caja WHERE id_pedidoc = :numero_pedido";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_STR);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado['count'] > 0;
}

function obtenerComentarios($conn, $numero_pedido) {
    $comentarios_query = "SELECT comentario FROM comentarios WHERE id_pedido = (SELECT numero_pedido FROM pedidos WHERE numero_pedido = ? LIMIT 1)";
    $comentarios_stmt = $conn->prepare($comentarios_query);
    $comentarios_stmt->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $comentarios_stmt->execute();
    return $comentarios_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Agrupar los pedidos por numero_pedido
$pedidosAgrupados = [];
foreach ($domicilios as $domicilio) {
    $numero_pedido = $domicilio['id_pedido'];
    $turno = $domicilio['turno'];

    $query_pedido = "SELECT p.numero_pedido, p.detalle, p.fecha, c.cliente, c.celular, c.email, c.direccion, p.tipo_solicitud, p.mesa, d.precio as costo_domicilio
                        FROM pedidos p 
                        LEFT JOIN turnero t ON p.numero_pedido = t.id_pedido
                        LEFT JOIN clientes c ON t.id_cliente = c.id
                        LEFT JOIN domicilios d ON p.numero_pedido = d.id_pedido
                        WHERE p.numero_pedido = :numero_pedido";

    $stmt_pedido = $conn->prepare($query_pedido);
    $stmt_pedido->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_STR);
    $stmt_pedido->execute();
    $pedido_info = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

    if ($pedido_info) {
        $pagado = esPagado($conn, $numero_pedido);
        $pedidosAgrupados[$numero_pedido] = [
            'info' => $pedido_info,
            'detalles' => obtenerDetallesPedido($conn, $numero_pedido),
            'comentarios' => obtenerComentarios($conn, $numero_pedido),
            'pagado' => $pagado,
            'turno' => $turno
        ];
    }
}
?>

<!-- C��digo HTML para mostrar la tabla y el filtro por fecha -->
<div class="container mt-5">
    <h1>Listado de Domicilios de <?php echo htmlspecialchars($domiciliario_nombre); ?></h1>

    <!-- Formulario para filtrar por fecha -->
    <form method="POST">
        <input type="hidden" name="id_e" value="<?php echo htmlspecialchars($id_e); ?>">
        <div class="form-group row">
            <label for="fecha_filtro" class="col-sm-1 col-form-label">Fecha:</label>
            <div class="col-sm-3">
                <input type="date" class="form-control" name="fecha_filtro" id="fecha_filtro" value="<?php echo htmlspecialchars($fecha_actual); ?>">
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </div>
    </form>

    <form action="index.php?page=procesar_caja.php" method="POST" id="formCaja">
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>Seleccionar</th>
                    <th>Turno</th>
                    <th>Cliente</th>
                    <th>Celular</th>
                    <th>Direción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pedidosAgrupados as $numero_pedido => $pedido): ?>
            <tr>
                <td>
                    <input type="checkbox" name="pedidos[]" value="<?php echo htmlspecialchars($numero_pedido); ?>" class="pedido-checkbox">
                </td>
                <td><?php echo htmlspecialchars($pedido['turno']); ?></td>
                <td><?php echo htmlspecialchars($pedido['info']['cliente']); ?></td>
                <td><?php echo htmlspecialchars($pedido['info']['celular']); ?></td>
                <td><?php echo htmlspecialchars($pedido['info']['direccion']); ?></td>
                <td>
                    <?php if ($pedido['pagado']): ?>
                        <button type="button" class="btn btn-success" disabled>Pagado</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#detallesModal-<?php echo $numero_pedido; ?>">Ver Detalle</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn-primary" id="enviarCajaBtn" style="display:none;">Enviar a Caja</button>
    </form>
</div>

<!-- Modal para mostrar detalles del pedido -->
<?php foreach ($pedidosAgrupados as $numero_pedido => $pedido): ?>
<div class="modal fade" id="detallesModal-<?php echo $numero_pedido; ?>" tabindex="-1" role="dialog" aria-labelledby="detallesModalLabel-<?php echo $numero_pedido; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detallesModalLabel-<?php echo $numero_pedido; ?>">Detalles del Pedido <?php echo $numero_pedido; ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['info']['cliente']); ?></p>
                <p><strong>Celular:</strong> <?php echo htmlspecialchars($pedido['info']['celular']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['info']['email']); ?></p>
                <p><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['info']['direccion']); ?></p>
                <p><strong>Fecha:</strong> <?php echo htmlspecialchars($pedido['info']['fecha']); ?></p>
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
                        <?php foreach ($pedido['detalles'] as $detalle): ?>
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
                <p><strong>Total Productos:</strong> <?php 
                    $totalProductos = array_reduce($pedido['detalles'], function($carry, $item) {
                        return $carry + ($item['precio_producto'] * $item['cantidad']);
                    }, 0);
                    echo "$" . number_format($totalProductos, 0, '', ',');
                ?></p>

                <p><strong>Costo Domicilio:</strong> <?php echo "$" . number_format($pedido['info']['costo_domicilio'] ?? 0, 0, '', ','); ?></p>

                <p><strong>Total General (incluyendo domicilio):</strong> 
                    <?php 
                        $totalGeneral = $totalProductos + ($pedido['info']['costo_domicilio'] ?? 0);
                        echo "$" . number_format($totalGeneral, 0, '', ',');
                    ?>
                </p>

                <p><strong>Comentarios:</strong></p>
                <ul>
                    <?php foreach ($pedido['comentarios'] as $comentario): ?>
                        <li><?php echo htmlspecialchars($comentario['comentario']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                    <input type="hidden" name="numero_pedido" value="<?php echo $numero_pedido; ?>">
                    <button class="btn btn-info">Caja</button>
                </form>
                <button type="button" class="btn btn-primary" onclick="printInvoice(<?php echo htmlspecialchars($pedido['info']['numero_pedido']); ?>)">Imprimir</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function printInvoice(numeroPedido) {
    window.open('print_invoice.php?numero_pedido=' + numeroPedido, '_blank');
}
</script>

<script>
    // Obtener todos los checkboxes de pedidos
    const checkboxes = document.querySelectorAll('.pedido-checkbox');
    const enviarCajaBtn = document.getElementById('enviarCajaBtn');

    // Funci��n para verificar si hay alg��n checkbox seleccionado
    function verificarSeleccion() {
        let alMenosUnoSeleccionado = false;
        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                alMenosUnoSeleccionado = true;
            }
        });

        // Mostrar el bot��n si al menos un checkbox est�� seleccionado
        if (alMenosUnoSeleccionado) {
            enviarCajaBtn.style.display = 'block';
        } else {
            enviarCajaBtn.style.display = 'none';
        }
    }

    // A�0�9adir el evento 'change' a todos los checkboxes
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', verificarSeleccion);
    });

    // Verificar la selecci��n al cargar la p��gina
    verificarSeleccion();
</script>
<script src="../public/js/impresion.js?cache=v3"></script>
