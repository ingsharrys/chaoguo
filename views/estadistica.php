<?php

require_once '../helpers/Session.php';
Session::start();
require_once '../config/database.php';

// Verificar si el usuario está logueado
if (!Session::get('user_id')) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// establecer los meses en español
setlocale(LC_TIME, 'es_ES.UTF-8', 'spanish');

$page = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productsPerPage = 10;
$offset = ($page - 1) * $productsPerPage;

// Obtener las fechas de inicio y fin del GET
$startDate = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$endDate = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Contar el total de clientes con los filtros de fechas
$totalQuery = "SELECT COUNT(DISTINCT clientes.id) 
               FROM clientes
               LEFT JOIN pedidos ON clientes.id = pedidos.cantidad
               ";
if ($startDate) {
    $totalQuery .= " AND pedidos.fecha >= :fecha_inicio";
}
if ($endDate) {
    $totalQuery .= " AND pedidos.fecha <= :fecha_fin";
}
if (!$startDate || !$endDate) {
    $totalQuery .= " WHERE pedidos.fecha >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
}

$totalStmt = $conn->prepare($totalQuery);
if ($startDate) {
    $totalStmt->bindValue(':fecha_inicio', $startDate);
}
if ($endDate) {
    $totalStmt->bindValue(':fecha_fin', $endDate);
}
$totalStmt->execute();
$totalProducts = $totalStmt->fetchColumn();

// Calcular el número total de páginas
$totalPages = ceil($totalProducts / $productsPerPage);

// Obtener los datos de clientes con los filtros de fechas
$query = "
    SELECT 
        ROW_NUMBER() OVER (ORDER BY COUNT(pedidos.cantidad) DESC) AS numero_fila,
        clientes.cliente AS nombre_cliente,
        clientes.celular AS numero_celular,
        COUNT(pedidos.cantidad) AS total_compras,
        COALESCE(SUM(precios.precio), 0) AS capital
    FROM 
        clientes
    LEFT JOIN 
        pedidos ON clientes.id = pedidos.cantidad
    LEFT JOIN 
        productos ON pedidos.id_pro = productos.id_pro
    LEFT JOIN 
        precios ON productos.id_pro = precios.idproduc
    WHERE 
        pedidos.fecha >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
";

if ($startDate) {
    $query .= " AND pedidos.fecha >= :fecha_inicio";
}
if ($endDate) {
    $query .= " AND pedidos.fecha <= :fecha_fin";
}

$query .= "
    GROUP BY 
        clientes.id, clientes.cliente, clientes.celular
    HAVING 
        COALESCE(SUM(precios.precio), 0) > 0
    ORDER BY 
        total_compras DESC
    LIMIT :limit OFFSET :offset;
";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
if ($startDate) {
    $stmt->bindValue(':fecha_inicio', $startDate);
}
if ($endDate) {
    $stmt->bindValue(':fecha_fin', $endDate);
}
$stmt->execute();

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================================
// Obtener los productos mas vendidos
// ===============================================

$query_mpv_producto = "
        SELECT 
            ROW_NUMBER() OVER (ORDER BY SUM(p.cantidad) DESC) AS ranking,
            pr.nombre,
            p.tipo_producto,
            SUM(p.cantidad) AS total_vendido
        FROM pedidos p
        JOIN productos pr ON p.id_pro = pr.id_pro
        WHERE MONTH(p.fecha) = MONTH(CURDATE())  -- Filtra por el mes actual
        AND YEAR(p.fecha) = YEAR(CURDATE())      -- Filtra por el año actual
        GROUP BY pr.nombre, p.tipo_producto
        ORDER BY total_vendido DESC
        LIMIT 10";

$stmt_producto = $conn->prepare($query_mpv_producto);
$stmt_producto->execute();

$producto_mpv = $stmt_producto->fetchAll(PDO::FETCH_ASSOC);

// ===============================================
// Obtener los productos mas vendidos
// ===============================================

$query_dias = "SELECT 
                    CASE 
                        WHEN DAYOFWEEK(fecha) = 1 THEN 'Domingo'
                        WHEN DAYOFWEEK(fecha) = 2 THEN 'Lunes'
                        WHEN DAYOFWEEK(fecha) = 3 THEN 'Martes'
                        WHEN DAYOFWEEK(fecha) = 4 THEN 'Miércoles'
                        WHEN DAYOFWEEK(fecha) = 5 THEN 'Jueves'
                        WHEN DAYOFWEEK(fecha) = 6 THEN 'Viernes'
                        WHEN DAYOFWEEK(fecha) = 7 THEN 'Sábado'
                    END AS dia_semana,
                    COUNT(*) AS total_pedidos,
                    SUM(cantidad) AS total_vendido
                FROM pedidos
                GROUP BY dia_semana
                ORDER BY total_vendido DESC";

$stmt_dias = $conn->prepare($query_dias);
$stmt_dias->execute();
$dias_mas_vendidos = $stmt_dias->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <section class="ts-map-direction pt-4">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="ts-map-tabs">
                                <ul class="nav" role="tablist">
                                    <!-- opciones -->
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#mejorCliente" role="tab" data-toggle="tab">Cliente</a>
                                    </li>
                                  
                                    <li class="nav-item">
                                        <a class="nav-link" href="#mejorProducto" role="tab" data-toggle="tab">Producto</a>
                                    </li>
                                    
                                    <li class="nav-item">
                                        <a class="nav-link" href="#mejorDia" role="tab" data-toggle="tab">Día</a>
                                    </li>
                                    
                                </ul>
                
                                <!-- contenido de las opciones -->
                               
                                <div class="tab-content direction-tabs">
    
                                    <div role="tabpanel" class="tab-pane active" id="mejorCliente">
                                        <div class="direction-tabs-content">
                                            <h2 class="text-center text-primary mb-4">Clientes Que Más Compran</h2>
                                            <!-- Formulario para filtrar por fechas -->
                                            <form method="GET" action="index.php">
                                                <input type="hidden" name="page" value="estadistica.php">
                                                <div class="form-row">
                                                    <div class="form-group col-md-4">
                                                        <label for="fecha_inicio">Fecha Inicio:</label>
                                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                                            value="<?= htmlspecialchars(isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '') ?>">
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label for="fecha_fin">Fecha Fin:</label>
                                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                                                            value="<?= htmlspecialchars(isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '') ?>">
                                                    </div>
                                                    <div class="form-group col-md-4 align-self-end">
                                                        <button type="submit" class="btn btn-primary">Filtrar</button>
                                                    </div>
                                                </div>
                                            </form>
                                        
                                            <?php if (!empty($clientes)): ?>
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>N°</th>
                                                                <th>Nombre del Cliente</th>
                                                                <th>N° Celular</th>
                                                                <th>Total de Compras</th>
                                                                <th>Capital</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($clientes as $cliente): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($cliente['numero_fila']); ?></td>
                                                                    <td><?= htmlspecialchars($cliente['nombre_cliente']); ?></td>
                                                                    <td><?= htmlspecialchars($cliente['numero_celular']); ?></td>
                                                                    <td><?= htmlspecialchars($cliente['total_compras']); ?></td>
                                                                    <td>$<?= htmlspecialchars(number_format($cliente['capital'], 2)); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-center text-warning">No hay datos disponibles.</p>
                                            <?php endif; ?>
                                        
                                            <nav>
                                                <ul class="pagination">
                                                    <?php 
                                        
                                                    // URL general
                                                    $baseUrl = 'index.php?page=estadistica.php';
                                                    // Verificar hay fecha inicial y final
                                                    if (!empty($startDate)) {
                                                        $baseUrl .= '&fecha_inicio=' . urlencode($startDate);
                                                    }
                                                    if (!empty($endDate)) {
                                                        $baseUrl .= '&fecha_fin=' . urlencode($endDate);
                                                    }
                                        
                                                    $startPage = max(1, $page - 5);
                                                    $endPage = min($totalPages, $page + 4);
                                        
                                                    if ($page > 1): ?>
                                                        <li class="page-item">
                                                            <a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $page - 1; ?>">Anterior</a>
                                                        </li>
                                                    <?php endif; ?>
                                        
                                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                                            <a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                        </li>
                                                    <?php endfor; ?>
                                        
                                                    <?php if ($page < $totalPages): ?>
                                                        <li class="page-item">
                                                            <a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $page + 1; ?>">Siguiente</a>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </nav>
                                            
                                        </div>
                                    </div>
                                 
                                    <div role="tabpanel" class="tab-pane" id="mejorProducto">
                                        <div class="direction-tabs-content">
                                            <h2 class="text-center text-primary mb-4">Producto Más Vendido - <?= strftime("%B", strtotime(date('Y-m-01'))); ?></h2>
                                        
                                            <?php if (!empty($producto_mpv)): ?>
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>N°</th>
                                                                <th>Nombre del Producto</th>
                                                                <th>Tipo de Producto</th>
                                                                <th>Total de Compras</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($producto_mpv as $producto): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($producto['ranking']); ?></td>
                                                                    <td><?= htmlspecialchars($producto['nombre']); ?></td>
                                                                    <td><?= htmlspecialchars($producto['tipo_producto']); ?></td>
                                                                    <td><?= htmlspecialchars($producto['total_vendido']); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-center text-warning">No hay datos disponibles.</p>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                    
                                    <div role="tabpanel" class="tab-pane" id="mejorDia">
                                        <div class="direction-tabs-content">
                                            <h2 class="text-center text-primary mb-4">Días de la Semana con Más Ventas</h2>

                                            <?php if (!empty($dias_mas_vendidos)): ?>
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>N°</th>
                                                                <th>Día de la Semana</th>
                                                                <th>Total de Pedidos</th>
                                                                <th>Total Vendido</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php $rank = 1; ?>
                                                            <?php foreach ($dias_mas_vendidos as $dia): ?>
                                                                <tr>
                                                                    <td><?= $rank++; ?></td>
                                                                    <td><?= htmlspecialchars($dia['dia_semana']); ?></td>
                                                                    <td><?= htmlspecialchars($dia['total_pedidos']); ?></td>
                                                                    <td><?= htmlspecialchars($dia['total_vendido']); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-center text-warning">No hay datos disponibles.</p>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> 
            </section>
        </div> 
    </div>
</div>

<style>
    .table-hover tbody tr:hover {
        background-color:rgb(214, 213, 213);
    }

</style>