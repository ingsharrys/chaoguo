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
$pedidosPorPagina = 10;
$offset = ($page - 1) * $pedidosPorPagina;

$totalQuery = "SELECT COUNT(*) FROM pedidos";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->execute();
$totalPedidos = $totalStmt->fetchColumn();

$totalPages = ceil($totalPedidos / $pedidosPorPagina);

$query = "
    SELECT 
        DATE(pedidos.fecha) AS fecha, 
        HOUR(pedidos.fecha) AS hora,
        COUNT(pedidos.id_pedido) AS total_pedidos,
        COALESCE(SUM(precios.precio * pedidos.cantidad), 0) AS capital
    FROM 
        pedidos
    LEFT JOIN 
        precios ON pedidos.id_pro = precios.idproduc
    GROUP BY 
        fecha, hora
    ORDER BY 
        total_pedidos DESC
    LIMIT :limit OFFSET :offset;
";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $pedidosPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$ventasPorHora = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1 class="text-center text-primary mb-4">Ventas por Fecha y Hora</h1>
    
    <?php if (!empty($ventasPorHora)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Total de Pedidos</th>
                        <th>Capital Generado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventasPorHora as $venta): ?>
                        <tr>
                            <td><?= htmlspecialchars($venta['fecha']); ?></td>
                            <td><?= htmlspecialchars($venta['hora']); ?>:00</td>
                            <td><?= htmlspecialchars($venta['total_pedidos']); ?></td>
                            <td>$<?= htmlspecialchars(number_format($venta['capital'], 2)); ?></td>
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
</script>
