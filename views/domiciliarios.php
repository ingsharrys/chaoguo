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

// Obtener el número de página actual desde la URL, o establecerla en 1 por defecto
$page = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productsPerPage = 10; // Número de filas por página
$offset = ($page - 1) * $productsPerPage;

// Determinar si estamos viendo eliminados o activos
$verEliminados = (isset($_GET['ver']) && $_GET['ver'] === 'eliminados');

// Según el modo, definimos el texto y la consulta
if ($verEliminados) {
    // Eliminados
    $totalQuery = "SELECT COUNT(*) FROM domiciliarios WHERE elimina = 0"; // Obtener total de filas
    $query = "SELECT * FROM domiciliarios WHERE elimina = 0 LIMIT :limit OFFSET :offset"; // Obtener datos de las filas
    $btnText = "Domiciliarios Activos"; 
    $btnLink = "index.php?page=domiciliarios.php"; 
} else {
    // Activos
    $totalQuery = "SELECT COUNT(*) FROM domiciliarios WHERE elimina = 1"; // Obtener total de filas
    $query = "SELECT * FROM domiciliarios WHERE elimina = 1 LIMIT :limit OFFSET :offset"; // Obtener datos de las filas
    $btnText = "Domiciliarios Eliminados";
    $btnLink = "index.php?page=domiciliarios.php&ver=eliminados";
}

// Contar el total de filas segun el modo
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->execute();
$totalProducts = $totalStmt->fetchColumn();

// Calcular el número total de páginas
$totalPages = ceil($totalProducts / $productsPerPage);

// Obtener las filas
$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$domiciliarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1>Domiciliarios</h1>
    <button id="agregar-domiciliario-btn" class="btn btn-primary mb-3" data-toggle="modal" data-target="#agregarDomiciliarioModal">Agregar Domiciliario</button>
    <button 
      id="toggle-domiciliarios-btn" 
      class="btn btn-secondary mb-3" 
      onclick="window.location.href='<?php echo $btnLink; ?>'">
      <?php echo $btnText; ?>
    </button>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>N°</th>
                <th>Repartidor</th>
                <th>Celular</th>
                <th>Calificación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($domiciliarios as $domiciliario): ?>
            <tr>
                <td><?php echo htmlspecialchars($domiciliario['id_e']); ?></td>
                <td> <form action="../public/index.php?page=domicilios.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id_e" value="<?php echo htmlspecialchars($domiciliario['id_e']); ?>">
                        <button type="submit" class="btn btn-link p-0"><?php echo htmlspecialchars($domiciliario['repartidor']); ?></button>
                    </form></td>
                <td><?php echo htmlspecialchars($domiciliario['celu_reparti']); ?></td>
                <td><?php echo htmlspecialchars($domiciliario['calificacion']); ?></td>
                <td>
                    <?php if (!$verEliminados): ?>
                        <button class="btn btn-warning btn-edit" data-id="<?php echo htmlspecialchars($domiciliario['id_e']); ?>" data-toggle="modal" data-target="#editarDomiciliarioModal" title="Editar">
                            <svg class="inputIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#000" viewBox="0 0 512 512">
                                <path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160L0 416c0 53 43 96 96 96l256 0c53 0 96-43 96-96l0-96c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 96c0 17.7-14.3 32-32 32L96 448c-17.7 0-32-14.3-32-32l0-256c0-17.7 14.3-32 32-32l96 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L96 64z"/>
                            </svg>
                        </button>
                        
                        <button class="btn btn-danger btn-delete" data-id="<?php echo $domiciliario['id_e']; ?>" title="Eliminar">
                            <svg class="inputIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#fff" viewBox="0 0 448 512">
                                <path d="M135.2 17.7L128 32 32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0-7.2-14.3C307.4 6.8 296.3 0 284.2 0L163.8 0c-12.1 0-23.2 6.8-28.6 17.7zM416 128L32 128 53.2 467c1.6 25.3 22.6 45 47.9 45l245.8 0c25.3 0 46.3-19.7 47.9-45L416 128z"/>                        
                            </svg>
                        </button>
                    <?php else : ?>
                        <button class="btn btn-success btn-delete" data-id="<?php echo $domiciliario['id_e']; ?>" title="Restaurar">
                            <svg class="inputIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#fff" viewBox="0 0 512 512">
                            <path d="M48.5 224L40 224c-13.3 0-24-10.7-24-24L16 72c0-9.7 5.8-18.5 14.8-22.2s19.3-1.7 26.2 5.2L98.6 96.6c87.6-86.5 228.7-86.2 315.8 1c87.5 87.5 87.5 229.3 0 316.8s-229.3 87.5-316.8 0c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0c62.5 62.5 163.8 62.5 226.3 0s62.5-163.8 0-226.3c-62.2-62.2-162.7-62.5-225.3-1L185 183c6.9 6.9 8.9 17.2 5.2 26.2s-12.5 14.8-22.2 14.8L48.5 224z"/>
                            </svg>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ============================================== -->
    <!-- Navegación de páginas -->
    <!-- ============================================== -->
        
    <nav>
        <ul class="pagination">
            <?php 
            // Parámetro base para los enlaces
            $baseUrl = 'index.php?page=domiciliarios.php';
            // Verificar la vista actual
            if ($verEliminados) {
                $baseUrl .= '&ver=eliminados';
            }

            // Rango de páginas visible
            $startPage = max(1, $page - 5); // Comienza hasta 5 páginas antes de la actual, sin ir por debajo de 1
            $endPage = min($totalPages, $page + 4); // Hasta 4 páginas después de la actual, sin exceder el total

            // Botón para la página anterior
            if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $page - 1; ?>">Anterior</a>
                </li>
            <?php endif; ?>

            <!-- Botones de número de página -->
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <!-- Botón para la página siguiente -->
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $page + 1; ?>">Siguiente</a>
                </li>
            <?php endif; ?>
            
        </ul>
    </nav>

</div>

<!-- Modal para agregar domiciliario -->
<div class="modal fade" id="agregarDomiciliarioModal" tabindex="-1" role="dialog" aria-labelledby="agregarDomiciliarioModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agregarDomiciliarioModalLabel">Agregar Domiciliario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="agregar-domiciliario-form">
                    <div class="form-group">
                        <label for="repartidor">Repartidor:</label>
                        <input type="text" class="form-control" id="repartidor" name="repartidor" required>
                    </div>
                    <div class="form-group">
                        <label for="celu_reparti">Celular:</label>
                        <input type="text" class="form-control" id="celu_reparti" name="celu_reparti" required>
                    </div>
                    <div class="form-group">
                        <label for="calificacion">Calificación:</label>
                        <input type="text" class="form-control" id="calificacion" name="calificacion">
                    </div>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar domiciliario -->
<div class="modal fade" id="editarDomiciliarioModal" tabindex="-1" role="dialog" aria-labelledby="editarDomiciliarioModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarDomiciliarioModalLabel">Editar Domiciliario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editar-domiciliario-form" method="post">
                    <input type="hidden" id="edit-id_e" name="id_e">
                    <div class="form-group">
                        <label for="edit-repartidor">Repartidor:</label>
                        <input type="text" class="form-control" id="edit-repartidor" name="repartidor" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-celu_reparti">Celular:</label>
                        <input type="text" class="form-control" id="edit-celu_reparti" name="celu_reparti" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-calificacion">Calificación:</label>
                        <input type="text" class="form-control" id="edit-calificacion" name="calificacion">
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Agregar domiciliario
    document.getElementById('agregar-domiciliario-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('../controllers/agregar_domiciliario.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Domiciliario agregado con éxito.');
                location.reload();
            } else {
                alert('Error al agregar el domiciliario: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud: ' + error.message);
        });
    });

    // Editar domiciliario
    document.querySelectorAll('.btn-edit').forEach(function(button) {
        button.addEventListener('click', function() {
            const id_e = this.getAttribute('data-id');

            fetch('../controllers/obtener_domiciliario.php?id=' + id_e)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('edit-id_e').value = data.domiciliarios.id_e;
                    document.getElementById('edit-repartidor').value = data.domiciliarios.repartidor;
                    document.getElementById('edit-celu_reparti').value = data.domiciliarios.celu_reparti;
                    document.getElementById('edit-calificacion').value = data.domiciliarios.calificacion;
                } else {
                    alert('Error al obtener los datos del domiciliario: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud: ' + error.message);
            });
        });
    });

    document.getElementById('editar-domiciliario-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('../controllers/editar_domiciliario.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Domiciliario actualizado con éxito.');
                location.reload();
            } else {
                alert('Error al actualizar el domiciliario: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud: ' + error.message);
        });
    });

    // Eliminar domiciliario
    document.querySelectorAll('.btn-danger').forEach(button => {
        button.addEventListener('click', function() {
            const id_e = this.getAttribute('data-id');

            if (confirm('¿Estás seguro de que deseas eliminar este domiciliario?')) {
                fetch(`../controllers/eliminar_domiciliario.php?id_e=${id_e}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Domiciliario eliminado con éxito.');
                        location.reload();
                    } else {
                        alert('Error al eliminar el domiciliario: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud: ' + error.message);
                });
            }
        });
    });

    // Restarurar domiciliario
    document.querySelectorAll('.btn-success').forEach(button => {
        button.addEventListener('click', function() {
            const id_e = this.getAttribute('data-id');

            if (confirm('¿Estás seguro de que deseas restaurar este domiciliario?')) {
                fetch(`../controllers/eliminar_domiciliario.php?id_e=${id_e}`, {
                    method: 'PATCH'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Domiciliario restaurado con éxito.');
                        location.reload();
                    } else {
                        alert('Error al restaurar el domiciliario: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud: ' + error.message);
                });
            }
        });
    });
});
</script>
