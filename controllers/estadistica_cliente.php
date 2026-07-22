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

$page = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productsPerPage = 10;
$offset = ($page - 1) * $productsPerPage;

// Obtener las fechas de inicio y fin del GET
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Contar el total de clientes con los filtros de fechas
$totalQuery = "SELECT COUNT(*) FROM clientes";
if ($startDate) {
    $totalQuery .= " WHERE clientes.fecha >= :start_date";
}
if ($endDate) {
    $totalQuery .= " AND clientes.fecha <= :end_date";
}

$totalStmt = $conn->prepare($totalQuery);
if ($startDate) {
    $totalStmt->bindValue(':start_date', $startDate);
}
if ($endDate) {
    $totalStmt->bindValue(':end_date', $endDate);
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
        pedidos ON clientes.id = pedidos.id_cliente
    LEFT JOIN 
        productos ON pedidos.id_pro = productos.id_pro
    LEFT JOIN 
        precios ON productos.id_pro = precios.idproduc
";

if ($startDate || $endDate) {
    $query .= " WHERE 1 ";
    if ($startDate) {
        $query .= " AND clientes.fecha >= :start_date ";
    }
    if ($endDate) {
        $query .= " AND clientes.fecha <= :end_date ";
    }
}

$query .= "
    GROUP BY 
        clientes.id, clientes.cliente, clientes.celular
    ORDER BY 
        total_compras DESC
    LIMIT :limit OFFSET :offset;
";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
if ($startDate) {
    $stmt->bindValue(':start_date', $startDate);
}
if ($endDate) {
    $stmt->bindValue(':end_date', $endDate);
}
$stmt->execute();

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1 class="text-center text-primary mb-4">Estadísticas de Clientes</h1>

    <!-- Formulario para filtrar por fechas -->
    <form action="estadistica_cliente.php" method="get" class="mb-4">
        <div class="row justify-content-center">
            <div class="col-md-3">
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($startDate); ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($endDate); ?>">
            </div>
            <div class="col-md-2">
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
            $baseUrl = 'index.php?page=estadistica.php';

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

<style>
    .table-hover tbody tr:hover {
        background-color: #f1f1f1;
    }

    .pagination .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
    }

    .pagination .page-item .page-link {
        color: #007bff;
    }

    .pagination .page-item .page-link:hover {
        background-color: #f1f1f1;
    }
</style>
