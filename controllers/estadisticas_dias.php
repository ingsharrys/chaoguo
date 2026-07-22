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

$page = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pedidosPorPagina = 10; // Número de pedidos por página
$offset = ($page - 1) * $pedidosPorPagina;

$totalQuery = "SELECT COUNT(DISTINCT DATE(pedidos.fecha)) FROM pedidos";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->execute();
$totalPedidos = $totalStmt->fetchColumn();

$totalPages = ceil($totalPedidos / $pedidosPorPagina);

$query = "
    SELECT 
        YEAR(pedidos.fecha) AS anio,
        MONTH(pedidos.fecha) AS mes,
        DAY(pedidos.fecha) AS numero_dia,
        DATE_FORMAT(pedidos.fecha, '%W') AS nombre_dia,
        MONTHNAME(pedidos.fecha) AS nombre_mes,
        SUM(precios.precio * pedidos.cantidad) AS total_ventas
    FROM 
        pedidos
    LEFT JOIN 
        precios ON pedidos.id_pro = precios.idproduc
    GROUP BY 
        anio, mes, numero_dia, nombre_dia, nombre_mes
    ORDER BY 
        total_ventas DESC
    LIMIT :limit OFFSET :offset;
";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $pedidosPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$ventasPorDia = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1 class="text-center text-primary mb-4">Ventas por Día</h1>
    
    <?php if (!empty($ventasPorDia)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Año</th>
                        <th>Mes</th>
                        <th>Número de Día</th>
                        <th>Nombre del Día</th>
                        <th>Total de Ventas (en $)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventasPorDia as $venta): ?>
                        <tr>
                            <td><?= htmlspecialchars($venta['anio']); ?></td>
                            <td><?= htmlspecialchars($venta['nombre_mes']); ?></td>
                            <td><?= htmlspecialchars($venta['numero_dia']); ?></td>
                            <td><?= htmlspecialchars($venta['nombre_dia']); ?></td>
                            <td>$<?= htmlspecialchars(number_format($venta['total_ventas'], 2)); ?></td>
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

<script>
    // Puedes agregar interactividad adicional aquí si lo deseas.
</script>
