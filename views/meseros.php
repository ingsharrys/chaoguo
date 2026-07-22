<?php
require_once '../helpers/Session.php';
Session::start();
require_once '../config/database.php';

if (!Session::get('user_id')) {
    header("Location: login.php");
    exit();
}

// Obtener el número de página actual desde la URL, o establecerla en 1 por defecto
$page = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productsPerPage = 10; // Número de meseros por página
$offset = ($page - 1) * $productsPerPage;

// Contar el total de meseros
$totalQuery = "SELECT COUNT(*) FROM meseros";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->execute();
$totalProducts = $totalStmt->fetchColumn();

// Calcular el número total de páginas
$totalPages = ceil($totalProducts / $productsPerPage);

// Modificar la consulta SQL para incluir paginación
$query = "
    SELECT *
    FROM meseros
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$meseros = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-5">
    <h1>Meseros</h1>
    <button id="agregar-mesero-btn" class="btn btn-primary mb-3" data-toggle="modal" data-target="#agregarMeseroModal">Agregar Mesero</button>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Cédula</th>
                <th>Código</th> <!-- Nueva columna para código -->
                <th>Cargo</th>  <!-- Nueva columna para cargo -->
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($meseros as $mesero): ?>
            <tr>
            <td><?php echo htmlspecialchars($mesero['id_mese'] ?? ''); ?></td>
<td>
    <form id="form-<?php echo htmlspecialchars($mesero['id_mese'] ?? ''); ?>" action="index.php?page=repor_mese.php" method="POST" style="display: none;">
        <input type="hidden" name="idmeser" value="<?php echo htmlspecialchars($mesero['id_mese'] ?? ''); ?>">
    </form>
    <a href="#" onclick="document.getElementById('form-<?php echo htmlspecialchars($mesero['id_mese'] ?? ''); ?>').submit();">
        <?php echo htmlspecialchars($mesero['nombre_mese'] ?? ''); ?>
    </a>
</td>
<td><?php echo htmlspecialchars($mesero['phon_mese'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($mesero['cedula_mese'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($mesero['cod_mese'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($mesero['cargo'] ?? ''); ?></td>

                <td>
                  <!--  <button class="btn btn-warning btn-edit" data-id="<?php echo $mesero['id_mese']; ?>" data-toggle="modal" data-target="#editarMeseroModal" title="Editar">
                    <svg class="inputIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#000" viewBox="0 0 512 512">
                        <path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160L0 416c0 53 43 96 96 96l256 0c53 0 96-43 96-96l0-96c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 96c0 17.7-14.3 32-32 32L96 448c-17.7 0-32-14.3-32-32l0-256c0-17.7 14.3-32 32-32l96 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L96 64z"/>
                    </svg>
                    </button>
                    <button class="btn btn-danger btn-delete" data-id="<?php echo $mesero['id_mese']; ?>" title="Eliminar">
                        <svg class="inputIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#fff" viewBox="0 0 448 512">
                            <path d="M135.2 17.7L128 32 32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0-7.2-14.3C307.4 6.8 296.3 0 284.2 0L163.8 0c-12.1 0-23.2 6.8-28.6 17.7zM416 128L32 128 53.2 467c1.6 25.3 22.6 45 47.9 45l245.8 0c25.3 0 46.3-19.7 47.9-45L416 128z"/>                        
                        </svg>
                    </button> -->
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
        $baseUrl = 'index.php?page=meseros.php';

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


<!-- Modal para agregar mesero -->
<!-- Modal para agregar mesero -->
<div class="modal fade" id="agregarMeseroModal" tabindex="-1" role="dialog" aria-labelledby="agregarMeseroModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agregarMeseroModalLabel">Agregar Mesero</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="agregar-mesero-form">
                    <div class="form-group">
                        <label for="nombre_mese">Nombre:</label>
                        <input type="text" class="form-control" id="nombre_mese" name="nombre_mese" required>
                    </div>
                    <div class="form-group">
                        <label for="phon_mese">Teléfono:</label>
                        <input type="text" class="form-control" id="phon_mese" name="phon_mese" required>
                    </div>
                    <div class="form-group">
                        <label for="cedula_mese">Cédula:</label>
                        <input type="text" class="form-control" id="cedula_mese" name="cedula_mese" required>
                    </div>

                    <div class="form-group">
                        <label for="cargo_mese">Cargo:</label>
                        <select class="form-control" id="cargo_mese" name="cargo_mese" required>
                            <option value="cajero">Cajero</option>
                            <option value="domi">Domiciliario</option>
                            <option value="turno">Turnos</option>
                            <option value="mesero">Mesero</option>
                            <option value="super">Supervisor</option>
                            <option value="subadmin">Administrador</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cod_mese">Código:</label>
                        <input type="number" class="form-control" id="cod_mese" name="cod_mese" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Agregar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar mesero -->
<div class="modal fade" id="editarMeseroModal" tabindex="-1" role="dialog" aria-labelledby="editarMeseroModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarMeseroModalLabel">Editar Mesero</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editar-mesero-form">
                    <input type="hidden" id="edit-id_mese" name="id_mese">
                    <div class="form-group">
                        <label for="edit-nombre_mese">Nombre:</label>
                        <input type="text" class="form-control" id="edit-nombre_mese" name="nombre_mese" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-phon_mese">Teléfono:</label>
                        <input type="text" class="form-control" id="edit-phon_mese" name="phon_mese" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-cedula_mese">Cédula:</label>
                        <input type="text" class="form-control" id="edit-cedula_mese" name="cedula_mese" required>
                    </div>

                    <div class="form-group">
                        <label for="edit-cargo_mese">Cargo:</label>
                        <select class="form-control" id="edit-cargo_mese" name="cargo_mese" required>
                            <option value="cajero">Cajero</option>
                            <option value="domi">Domiciliario</option>
                            <option value="turno">Turnos</option>
                            <option value="mesero">Mesero</option>
                            <option value="super">Supervisor</option>
                            <option value="subadmin">Administrador</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit-cod_mese">Código:</label>
                        <input type="number" class="form-control" id="edit-cod_mese" name="cod_mese" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Agregar mesero
    document.getElementById('agregar-mesero-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('../controllers/agregar_mesero.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Mesero agregado con éxito.');
                location.reload();
            } else {
                alert('Error al agregar el mesero: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud: ' + error.message);
        });
    });

// Editar mesero
document.querySelectorAll('.btn-edit').forEach(button => {
    button.addEventListener('click', function() {
        const id_mese = this.getAttribute('data-id');

        fetch(`../controllers/obtener_mesero.php?id_mese=${id_mese}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('edit-id_mese').value = data.mesero.id_mese;
                document.getElementById('edit-nombre_mese').value = data.mesero.nombre_mese;
                document.getElementById('edit-phon_mese').value = data.mesero.phon_mese;
                document.getElementById('edit-cedula_mese').value = data.mesero.cedula_mese;

                // Aquí asegúrate de que el campo 'cargo' existe y se asigna correctamente
                document.getElementById('edit-cargo_mese').value = data.mesero.cargo; 

                document.getElementById('edit-cod_mese').value = data.mesero.cod_mese;
            } else {
                alert('Error al obtener los datos del mesero: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud: ' + error.message);
        });
    });
});


    // Actualizar mesero
    document.getElementById('editar-mesero-form').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('../controllers/editar_mesero.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Mesero actualizado con éxito.');
                location.reload();
            } else {
                alert('Error al actualizar el mesero: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud: ' + error.message);
        });
    });

    // Eliminar mesero
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const id_mese = this.getAttribute('data-id');

            if (confirm('¿Estás seguro de que deseas eliminar este mesero?')) {
                fetch(`../controllers/eliminar_mesero.php?id_mese=${id_mese}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Mesero eliminado con éxito.');
                        location.reload();
                    } else {
                        alert('Error al eliminar el mesero: ' + data.message);
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
