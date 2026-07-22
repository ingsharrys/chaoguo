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
$productsPerPage = 10;
$offset = ($page - 1) * $productsPerPage;

$totalQuery = "SELECT COUNT(DISTINCT meseros.id_mese) FROM meseros";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->execute();
$totalRecords = $totalStmt->fetchColumn();

$totalPages = ceil($totalRecords / $productsPerPage);

// Consulta para obtener las estadísticas de los meseros y lo que venden
$queryMeseros = "
    SELECT 
        meseros.id_mese, 
        meseros.nombre_mese,
        SUM(pedidos.cantidad * precios.precio) AS total_vendido
    FROM meseros
    LEFT JOIN pedidos ON meseros.id_mese = pedidos.mesero
    LEFT JOIN precios ON pedidos.id_pro = precios.idpre
    GROUP BY meseros.id_mese
    ORDER BY total_vendido DESC
    LIMIT :limit OFFSET :offset;
";

$stmt = $conn->prepare($queryMeseros);
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$meseros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h2 class="text-center text-success mb-4">Estadísticas de Meseros - Ventas Totales</h2>
    
    <?php if (!empty($meseros)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>N°</th>
                        <th>Nombre del Mesero</th>
                        <th>Total Vendido</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meseros as $mesero): ?>
                        <tr>
                            <td><?= htmlspecialchars($mesero['id_mese']); ?></td>
                            <td><?= htmlspecialchars($mesero['nombre_mese']); ?></td>
                            <td>$ <?= number_format($mesero['total_vendido'] !== null ? $mesero['total_vendido'] : 0, 2, '.', ','); ?></td>
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
