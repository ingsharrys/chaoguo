<?php
require_once '../config/database.php';

// Verificamos si se ha enviado el ID del mesero por POST
$id_mese = isset($_POST['idmeser']) ? $_POST['idmeser'] : null;
$fecha_seleccionada = isset($_POST['fecha']) ? $_POST['fecha'] : null;

// Si no se ha proporcionado el ID del mesero, redirigimos o mostramos un mensaje de error
if (!$id_mese) {
    echo "Error: ID del mesero no proporcionado.";
    exit();
}

// Conectamos a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Obtenemos el nombre del mesero
$query_nombre_mesero = "SELECT nombre_mese FROM meseros WHERE id_mese = :id_mese";
$stmt_nombre_mesero = $conn->prepare($query_nombre_mesero);
$stmt_nombre_mesero->bindParam(':id_mese', $id_mese, PDO::PARAM_INT);
$stmt_nombre_mesero->execute();
$mesero = $stmt_nombre_mesero->fetch(PDO::FETCH_ASSOC);

// Si no se encuentra el mesero, mostramos un mensaje de error
if (!$mesero) {
    echo "Mesero no encontrado.";
    exit();
}

// Consulta para obtener los pedidos asignados al mesero, con opción de filtrar por fecha
$query_pedidos = "SELECT p.numero_pedido, p.fecha, c.costo 
                  FROM pedidos p
                  LEFT JOIN caja c ON p.numero_pedido = c.id_pedidoc
                  WHERE p.mesero = :id_mese";

if ($fecha_seleccionada) {
    $query_pedidos .= " AND DATE(p.fecha) = :fecha_seleccionada"; // Filtra por la fecha seleccionada
}

$query_pedidos .= " GROUP BY p.numero_pedido"; // Agrupamos por número de pedido

$stmt_pedidos = $conn->prepare($query_pedidos);
$stmt_pedidos->bindParam(':id_mese', $id_mese, PDO::PARAM_INT);

if ($fecha_seleccionada) {
    $stmt_pedidos->bindParam(':fecha_seleccionada', $fecha_seleccionada);
}

$stmt_pedidos->execute();
$pedidos = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);

// Función para obtener los productos de un pedido
function obtenerProductos($conn, $numero_pedido) {
    $query_productos = "SELECT prod.nombre, p.cantidad, pr.precio 
                        FROM pedidos p
                        JOIN productos prod ON p.id_pro = prod.id_pro
                        JOIN precios pr ON p.id_pro = pr.idproduc
                        WHERE p.numero_pedido = :numero_pedido";
    $stmt_productos = $conn->prepare($query_productos);
    $stmt_productos->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
    $stmt_productos->execute();
    return $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener los abonos del cajero
function obtenerAbonos($conn, $id_pedido) {
    $query_abonos = "SELECT 
                        g.id AS id_gasto, 
                        g.fecha AS fecha_gasto, 
                        g.monto AS monto_gasto, 
                        g.estado, 
                        a.id AS id_abono, 
                        a.cantidad AS cantidad_abono, 
                        a.fecha AS fecha_abono
                      FROM gastos g
                      LEFT JOIN abono_nomina a ON g.id = a.id_gasto
                      WHERE g.estado = 0 AND g.id_mesero = :id_mesero
                      ORDER BY g.fecha ASC, a.fecha ASC";
    $stmt_abonos = $conn->prepare($query_abonos);
    $stmt_abonos->bindParam(':id_mesero', $id_pedido, PDO::PARAM_INT);
    $stmt_abonos->execute();
    return $stmt_abonos->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerGastosConFaltante($conn, $id_gasto, $id_mesero) {
    $query = "SELECT 
                g.id AS id_gasto, 
                g.fecha AS fecha_gasto, 
                g.monto AS monto_gasto, 
                g.estado, 
                COALESCE(SUM(a.cantidad), 0) AS total_abonado,
                (g.monto - COALESCE(SUM(a.cantidad), 0)) AS faltante
              FROM gastos g
              LEFT JOIN abono_nomina a ON g.id = a.id_gasto
              WHERE g.estado = 0 AND g.id_mesero = :id_mesero AND g.id = :id_gasto
              GROUP BY g.id
              ORDER BY g.fecha ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_mesero', $id_mesero, PDO::PARAM_INT);
    $stmt->bindParam(':id_gasto', $id_gasto, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container mt-5">
    <h1>Pedidos atendidos por <?php echo htmlspecialchars($mesero['nombre_mese']); ?></h1>
    
    <!-- Formulario para filtrar pedidos por fecha -->
    <form method="POST" action="">
        <input type="hidden" name="idmeser" value="<?php echo htmlspecialchars($id_mese); ?>">
        <div class="form-row">
            <div class="col-md-3">
                <label for="fecha">Seleccionar Fecha:</label>
                <input type="date" class="form-control" name="fecha" value="<?php echo htmlspecialchars($fecha_seleccionada); ?>">
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label> <!-- Para alinear el botón correctamente -->
                <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
            </div>
        </div>
    </form>
    
    <!-- Botón que abrirá el modal con los detalles del abono -->
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-abonos-<?php echo $id_mese; ?>">
        Ver Vales Pendientes
    </button>

    <table class="table table-striped mt-3">
        <thead>
            <tr>
                <th>Número de Pedido</th>
                <th>Fecha</th>
                <th>Costo del Pedido</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
            <tr>
                <td><?php echo htmlspecialchars($pedido['numero_pedido']); ?></td>
                <td><?php echo htmlspecialchars($pedido['fecha']); ?></td>
                <td><?php echo "$" . number_format($pedido['costo'], 0, '', ','); ?></td>
                <td>
                    <!-- Botón que abrirá el modal con los detalles del pedido -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-<?php echo $pedido['numero_pedido']; ?>">
                        Ver Productos
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal para mostrar los detalles de los productos de cada pedido -->
<?php foreach ($pedidos as $pedido): ?>
<div class="modal fade" id="modal-<?php echo $pedido['numero_pedido']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel-<?php echo $pedido['numero_pedido']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel-<?php echo $pedido['numero_pedido']; ?>">Productos del Pedido <?php echo htmlspecialchars($pedido['numero_pedido']); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Obtenemos los productos de este pedido
                        $productos = obtenerProductos($conn, $pedido['numero_pedido']);
                        foreach ($productos as $producto): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($producto['cantidad']); ?></td>
                            <td><?php echo "$" . number_format($producto['precio'], 0, '', ','); ?></td>
                            <td><?php echo "$" . number_format($producto['precio'] * $producto['cantidad'], 0, '', ','); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><strong>Total Productos:</strong> 
                    <?php 
                    $total = array_reduce($productos, function($carry, $item) {
                        return $carry + ($item['precio'] * $item['cantidad']);
                    }, 0);
                    echo "$" . number_format($total, 0, '', ','); 
                    ?>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>


<!-- Modal para mostrar los abonos -->

<div class="modal fade" id="modal-abonos-<?php echo $id_mese; ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel-<?php echo $id_mese; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel-<?php echo $id_mese; ?>">Vales para <?php echo htmlspecialchars($mesero['nombre_mese']); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Abonado</th>
                            <th>Ingresar Abono</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Obtenemos los gastos y sus abonos
                        $abonados = obtenerAbonos($conn, $id_mese);
                        
                        if (count($abonados) > 0) {
                
                        // Variable para agrupar los abonos por gasto
                        $gastos_agrupados = [];
                
                        // Agrupar los abonos bajo cada gasto
                        foreach ($abonados as $abono) {
                            $gasto_id = $abono['id_gasto'];
                            if (!isset($gastos_agrupados[$gasto_id])) {
                                $gastos_agrupados[$gasto_id] = [
                                    'fecha_gasto' => $abono['fecha_gasto'],
                                    'monto_gasto' => $abono['monto_gasto'],
                                    'id_gasto' => $abono['id_gasto'],
                                    'abonos' => []
                                ];
                            }
                            if ($abono['id_abono'] !== null) {
                                $gastos_agrupados[$gasto_id]['abonos'][] = [
                                    'cantidad' => $abono['cantidad_abono'],
                                    'fecha' => $abono['fecha_abono']
                                ];
                            }
                        }
                
                        // Mostrar los gastos y sus abonos en la tabla
                        foreach ($gastos_agrupados as $gasto): ?>
                            <tr>
                                <td><?= htmlspecialchars($gasto['fecha_gasto']); ?></td>
                                <td><?= "$" . number_format($gasto['monto_gasto'], 0, '', ','); ?></td>
                                <td>
                                    <?php if (!empty($gasto['abonos'])): ?>
                                        <ul>
                                            <?php foreach ($gasto['abonos'] as $abono): ?>
                                                <li><?= htmlspecialchars($abono['fecha']) . ": $" . number_format($abono['cantidad'], 0, '', ','); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted">Sin abonos</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <? $faltante = obtenerGastosConFaltante($conn, $gasto['id_gasto'], $id_mese); ?>
                                    <? if ($faltante[0]['faltante'] > 0 ): ?>
                                    <form method="post" action="../controllers/vales_controller.php">
                                        <label for="abono">Cantidad a Abonar:</label>
                                        <input type="hidden" name="id_gasto" value="<?= htmlspecialchars($gasto['id_gasto']); ?>">
                                        <input type="hidden" name="id_mesero" value="<?= htmlspecialchars($id_mese); ?>">
                                        <input class="input_abono" type="number" id="abono" name="abono" max="<?= $faltante[0]['faltante']; ?>" min="1" required>
                                        <label for="abono">Faltante: <?= htmlspecialchars($faltante[0]['faltante']); ?></label>
                                        <button type="submit" class="btn btn-primary btn-sm">Abonar</button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted">Abono Pagado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; } else { ?>

                            <tr>
                                <th colspan="6">No se encontraron vales pendientes.</th>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

