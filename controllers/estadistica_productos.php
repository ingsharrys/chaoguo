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
$meserosPerPage = 10;
$offset = ($page - 1) * $meserosPerPage;

$totalQuery = "SELECT COUNT(*) FROM meseros";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->execute();
$totalMeseros = $totalStmt->fetchColumn();

$totalPages = ceil($totalMeseros / $meserosPerPage);

$query = "
    SELECT 
        id_mese,
        nombre_mese,
        phon_mese,
        cedula_mese,
        cargo,
        cod_mese
    FROM 
        meseros
    LIMIT :limit OFFSET :offset;
";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $meserosPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$meseros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1 class="text-center text-primary mb-4">Tabla de Meseros</h1>
    
    <?php if (!empty($meseros)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Cédula</th>
                        <th>Cargo</th>
                        <th>Código</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meseros as $mesero): ?>
                        <tr>
                            <td><?= htmlspecialchars($mesero['id_mese']); ?></td>
                            <td><?= htmlspecialchars($mesero['nombre_mese']); ?></td>
                            <td><?= htmlspecialchars($mesero['phon_mese']); ?></td>
                            <td><?= htmlspecialchars($mesero['cedula_mese']); ?></td>
                            <td><?= htmlspecialchars($mesero['cargo']); ?></td>
                            <td><?= htmlspecialchars($mesero['cod_mese']); ?></td>
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
