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

// Asegurar que $busqueda SIEMPRE se define
$busqueda = '';
if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
    $busqueda = trim($_GET['busqueda']);
}

// Obtener el número de página actual desde la URL, o establecerla en 1 por defecto
$page = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productsPerPage = 10; // Número de productos por página
$offset = ($page - 1) * $productsPerPage;

// Contar el total de productos
$totalQuery = "SELECT COUNT(*) FROM productos p";
if ($busqueda) {
    $totalQuery .= " WHERE p.nombre LIKE :busqueda";
}
$totalStmt = $conn->prepare($totalQuery);
if ($busqueda) {
    $totalStmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
}
$totalStmt->execute();
$totalProducts = $totalStmt->fetchColumn();

// Calcular el número total de páginas
$totalPages = ceil($totalProducts / $productsPerPage);

// Modificar la consulta SQL para incluir paginación
$query = "
    SELECT 
        p.id_pro, 
        p.nombre, 
        p.prefijo, 
        p.cat, 
        p.descript, 
        p.img,
        p.tcomida,
        GROUP_CONCAT(pr.tipo_prod SEPARATOR ', ') AS tipos_producto,
        GROUP_CONCAT(pr.precio SEPARATOR ', ') AS precios
    FROM productos p 
    LEFT JOIN precios pr ON p.id_pro = pr.idproduc
";

if ($busqueda) {
    $query .= " WHERE p.nombre LIKE :busqueda"; 
}

$query .= " GROUP BY p.id_pro
            ORDER BY p.cat ASC
            LIMIT :limit OFFSET :offset"; 

$stmt = $conn->prepare($query);
if ($busqueda) {
    $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-5">
    
    <div class="row mt-4">
        <div class="col-sm-12 col-md-4">
            <h2>Productos</h2>
        </div>
        <div class="col-sm-12 col-md-4">
            <form method="GET" action="/public/index.php" class="form-inline" style="justify-content: center;">
                <!-- Parámetro 'page' obligatorio para mantener la navegación -->
                <input type="hidden" name="page" value="productos.php">
                <input 
                    type="text" 
                    name="busqueda" 
                    class="form-control mr-2" 
                    placeholder="Buscar producto..." 
                    value="<?php echo htmlspecialchars($busqueda); ?>"
                >
                <button type="submit" class="btn btn-primary">Buscar</button>
            </form>
        </div>
        <div class="col-sm-12 col-md-4" style="text-align: right;">
         <!--   <button id="agregar-producto-btn" class="btn btn-primary mb-3" data-toggle="modal" data-target="#agregarProductoModal">Agregar Producto</button> -->
        </div>
        
    </div>

    <table class="table table-striped" id="tabla_productos">
        <thead style="text-align: center;">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Prefijo</th>
                <th>Precio</th>
                <th>Tipo de Producto</th>
                <th>Categoría</th>
                <th>Descripción</th>
                <th>Imagen</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody style="text-align: center;">
            <?php if (count($productos) > 0): ?>
                <?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?php echo htmlspecialchars($producto['id_pro']); ?></td>
                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($producto['prefijo']); ?></td>
                    <td><?php echo htmlspecialchars(isset($producto['precios']) ? $producto['precios'] : 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars(isset($producto['tipos_producto']) ? $producto['tipos_producto'] : 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($producto['cat']); ?></td>
                    <td><?php echo htmlspecialchars($producto['descript']); ?></td>
                    <td><img src="../path/to/productos/<?php echo htmlspecialchars($producto['img']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" width="50"></td>
                    <td>
            
                        <!-- Botón para editar producto
            
                        <button class="btn_edit btn-warning btn-sm edit-product-btn" data-id="<?php echo htmlspecialchars($producto['id_pro']); ?>" data-toggle="modal" data-target="#editarProductoModal" title="Editar">
                            <svg class="inputIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#000" viewBox="0 0 512 512">
                                <path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160L0 416c0 53 43 96 96 96l256 0c53 0 96-43 96-96l0-96c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 96c0 17.7-14.3 32-32 32L96 448c-17.7 0-32-14.3-32-32l0-256c0-17.7 14.3-32 32-32l96 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L96 64z"/>
                            </svg>
                        </button>
            
                        <!-- Botón para eliminar producto 
                        
                        <form class="form-eliminar-producto" method="post">
                            <input type="hidden" class="form-control" id="producto_id" name="id_pro" value="<?php echo htmlspecialchars($producto['id_pro']); ?>">
                            <button type="submit" class="btn_delete btn-danger" title="Eliminar">
                                <svg class="inputIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#fff" viewBox="0 0 448 512">
                                    <path d="M135.2 17.7L128 32 32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0-7.2-14.3C307.4 6.8 296.3 0 284.2 0L163.8 0c-12.1 0-23.2 6.8-28.6 17.7zM416 128L32 128 53.2 467c1.6 25.3 22.6 45 47.9 45l245.8 0c25.3 0 46.3-19.7 47.9-45L416 128z"/>                        
                                </svg>
                            </button>
                        </form>-->
            
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <td colspan="9" class="alert alert-info">No se encontraron productos con ese nombre.</td>
            <?php endif; ?>
        </tbody>

</table>

<!-- ============================================== -->
<!-- Navegación de páginas -->
<!-- ============================================== -->
    
<nav>
    <ul class="pagination">
        <?php 
        // Parámetro base para los enlaces
        $baseUrl = 'index.php?page=productos.php';
        // Parámetro incluyendo la búsqueda si existe
        if (!empty($busqueda)) {
            $baseUrl .= '&busqueda=' . urlencode($busqueda);
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

<!-- ============================================== -->
<!-- Modal para agregar producto -->
<!-- ============================================== -->

<div class="modal fade" id="agregarProductoModal" tabindex="-1" role="dialog" aria-labelledby="agregarProductoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agregarProductoModalLabel">Agregar Un Nuevo Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="agregar-producto-form" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto:</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="prefijo">Prefijo:</label>
                        <input type="text" class="form-control" id="prefijo" name="prefijo" required>
                    </div>
                    <div class="form-group">
                        <label for="cat">Categoría:</label>
                        <input type="number" class="form-control" id="cat" name="cat" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-descript">tipo de producto:</label>
                        <input type="number" class="form-control" id="tcomida" name="tcomida" required>
                    </div>
                    <div class="form-group">
                        <label for="descript">Descripción:</label>
                        <textarea class="form-control" id="descript" name="descript" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="img">Imagen del Producto:</label>
                        <input type="file" class="form-control" id="img" name="img" required>
                    </div>
                    
                    <label>Variantes:</label>
                    <div id="variantes-container">
                        <div class="form-row variante-group">
                            <div class="form-group col-md-5">
                                <label for="tipo_producto">Tipo de Producto:</label>
                                <input type="text" class="form-control" name="tipo_producto[]" required>
                            </div>
                            <div class="form-group col-md-5">
                                <label for="precio_producto">Precio:</label>
                                <input type="number" class="form-control" name="precio_producto[]" required>
                            </div>
                            <div class="col-md-2" style="align-content: center;">
                             <!--   <button type="button" class="btn btn-danger btn-remove-variante" disabled>Eliminar</button> -->
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-secondary" id="add-variante-btn">Nueva Variante</button>


                    <button type="submit" class="btn btn-primary">Agregar</button>
                </form>
                
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- Modal para editar producto -->
<!-- ============================================== -->

<div class="modal fade" id="editarProductoModal" tabindex="-1" role="dialog" aria-labelledby="editarProductoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarProductoModalLabel">Editar Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editar-producto-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" id="edit-id_pro" name="id_pro">
                    <div class="form-group">
                        <label for="edit-nombre">Nombre:</label>
                        <input type="text" class="form-control" id="edit-nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-prefijo">Prefijo:</label>
                        <input type="text" class="form-control" id="edit-prefijo" name="prefijo" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-cat">Categoría:</label>
                        <input type="text" class="form-control" id="edit-cat" name="cat" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-descript">Descripción:</label>
                        <textarea class="form-control" id="edit-descript" name="descript" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit-tcomida">Tipo de Producto (tcomida):</label>
                        <input type="number" class="form-control" id="edit-tcomida" name="tcomida" required>
                    </div>


                    <div class="form-group">
                        <label for="edit-img">Imagen:</label>
                        <input type="file" class="form-control" id="edit-img" name="img">
                    </div>

                    <div class="form-group">
                        <label>Tipos de Producto y Precios:</label>
                        <div id="tipo-precio-container"></div>
                        <button type="button" id="agregar-tipo-precio" class="btn btn-secondary">Nueva Variante</button>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- Agregar un nuevo producto -->
<!-- ============================================== -->

<script>
document.getElementById('agregar-producto-form').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevenir el envío estándar del formulario

    const formData = new FormData(this);

    fetch('../controllers/agregar_producto.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {

        if (data.status === 'success') {
            alertaExito(data.message).then(() => {
                location.reload(); // Recargar la página después de cerrar el SweetAlert
            });
        } else {
            alertaError(data.message);
        }

        // Limpiar el formulario si fue exitoso
        if (data.status === 'success') {
            document.getElementById('agregar-producto-form').reset();
        }
    })
    .catch(error => {
        const mensajeError = 'Error al procesar la solicitud. Intenta nuevamente.';
        alertaError(mensajeError);
    });
});
</script>

<!-- ============================================== -->
<!-- opción de variante del producto -->
<!-- ============================================== -->

<script>
    // Función para habilitar o deshabilitar el botón de eliminar
    function toggleEliminarBoton() {
        // Obtener todos los contenedores de variantes
        const bloques = ['#variantes-container', '#tipo-precio-container'];

        bloques.forEach(bloqueId => {
            const varianteGroups = document.querySelectorAll(`${bloqueId} .variante-group`);
            varianteGroups.forEach((group, index) => {
                const btnEliminar = group.querySelector('.btn-remove-variante');
                
                // Habilitar o deshabilitar el botón dependiendo de la cantidad de variantes en este bloque
                if (varianteGroups.length > 1) {
                    btnEliminar.disabled = false; // Habilitar si hay más de 1 variante en este bloque
                } else {
                    btnEliminar.disabled = true;  // Deshabilitar si es la única variante en este bloque
                }
            });
        });
    }

    // Añadir nueva variante
    document.getElementById('add-variante-btn').addEventListener('click', function() {
        var newVariante = document.querySelector('.variante-group').cloneNode(true);

        // Limpiar los campos de la variante clonada
        newVariante.querySelectorAll('input').forEach(input => input.value = '');

        // Añadir el nuevo grupo de variantes al contenedor
        document.getElementById('variantes-container').appendChild(newVariante);

        // Llamar a la función para actualizar los botones de eliminar
        toggleEliminarBoton();
    });

    // Eliminar una variante
    document.getElementById('variantes-container').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-remove-variante')) {
            var varianteGroup = e.target.closest('.variante-group');
            varianteGroup.remove();

            // Llamar a la función para actualizar los botones de eliminar
            toggleEliminarBoton();
        }
    });

    // Inicializar el estado de los botones al cargar la página
    toggleEliminarBoton();
</script>

<!-- ============================================== -->
<!-- Editar un producto existente -->
<!-- ============================================== -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editarForm = document.getElementById('editar-producto-form');
    let isSubmitting = false;

    // Manejar el evento de clic del botón de edición
    document.querySelectorAll('.edit-product-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');

            // Obtener detalles del producto utilizando AJAX
            fetch('../controllers/obtener_producto.php?id=' + id)
  .then(response => response.json())
  .then(data => {
      console.log("Respuesta JSON:", data); // <-- Agrega esto
      if (data.status === 'success') {
          const producto = data.producto;
          // Asigna los valores a los inputs:
          document.getElementById('edit-id_pro').value = producto.id_pro;
          document.getElementById('edit-nombre').value = producto.nombre;
          document.getElementById('edit-prefijo').value = producto.prefijo;
          document.getElementById('edit-cat').value = producto.cat;
          document.getElementById('edit-descript').value = producto.descript;
          document.getElementById('edit-tcomida').value = producto.tcomida;

          
          const tipoPrecioContainer = document.getElementById('tipo-precio-container');
          tipoPrecioContainer.innerHTML = ''; // Limpiar el contenedor
          
          console.log("Tipos y precios:", producto.tipos_precios); // <-- Revisa esto
          producto.tipos_precios.forEach(function(tp) {
              agregarTipoPrecio(tp.tipo_prod, tp.precio);
          });
      } else {
          alertaError(data.message);
      }
  })
  .catch(error => {
      alertaError(error.message);
  });

        });
    });

    function agregarTipoPrecio(tipo = '', precio = '') {

        // Crear el div contenedor principal
        const container = document.createElement('div');
        container.classList.add('form-row', 'variante-group');

        // Crear el div para el tipo Producto
        const divProducto = document.createElement('div');
        divProducto.classList.add('form-group', 'col-md-5');

        // Crear el div para el precio
        const divPrecio = document.createElement('div');
        divPrecio.classList.add('form-group', 'col-md-5');

        // Crear el div para el boton eliminar
        const divBoton = document.createElement('div');
        divBoton.classList.add('form-group', 'col-md-2');

        const tipoInput = document.createElement('input');
        tipoInput.type = 'text';
        tipoInput.className = 'form-control mb-2';
        tipoInput.name = 'tipos[]';
        tipoInput.value = tipo;
        tipoInput.placeholder = 'Tipo de Producto';

        const precioInput = document.createElement('input');
        precioInput.type = 'number';
        precioInput.className = 'form-control mb-2';
        precioInput.name = 'precios[]';
        precioInput.value = precio;
        precioInput.placeholder = 'Precio';

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn-danger btn-remove-variante';
        removeButton.textContent = 'Eliminar';
        removeButton.disabled = true; // Agregar el atributo disabled al botón
        removeButton.onclick = function() {
            container.remove(); // Eliminar el contenedor de la variante
            toggleEliminarBoton();
        };

        container.appendChild(divProducto);
        container.appendChild(divPrecio);
        container.appendChild(divBoton);
        divProducto.appendChild(tipoInput);
        divPrecio.appendChild(precioInput);
        divBoton.appendChild(removeButton);
        document.getElementById('tipo-precio-container').appendChild(container);
    }

    document.getElementById('agregar-tipo-precio').addEventListener('click', function() {
        agregarTipoPrecio();
        toggleEliminarBoton();
    });

    // Código para manejar el envío del formulario de editar producto
    editarForm.addEventListener('submit', function(event) {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        isSubmitting = true;
        const submitButton = editarForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        const formData = new FormData(this);

        fetch('../controllers/editar_producto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alertaExito(data.message).then(() => {
                    location.reload(); // Recargar la página después de cerrar el SweetAlert
                });
            } else {
                alertaError(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertaError(error.message);
            //alert('Error al procesar la solicitud: ' + error.message);
        })
        .finally(() => {
            isSubmitting = false;
            submitButton.disabled = false;
        });
    });
});

</script>

<!-- ============================================== -->
<!-- Eliminar un nuevo producto -->
<!-- ============================================== -->

<script>
document.querySelectorAll('.form-eliminar-producto').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevenir envío estándar del formulario

        const formData = new FormData(this);

        fetch('../controllers/eliminar_producto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {

            if (data.status === 'success') {
                alertaExito(data.message).then(() => {
                    // Eliminar fila del producto eliminado
                    this.closest('tr').remove();
                    location.reload(); // Recargar la página después de cerrar el SweetAlert
                });
            } else {
                alertaError(data.message);
            }

        })
        .catch(error => {
            const mensajeError = 'Error al procesar la solicitud. Intenta nuevamente.';
            alertaError(mensajeError);
            //console.error('Error:', error);
        });
    });
});

</script>

<!-- ============================================== -->
<!-- Mensajes de alertas -->
<!-- ============================================== -->

<script>
    // Alerta de éxito
    function alertaExito(mensaje) {
        return Swal.fire({
            title: '¡Éxito!',
            text: mensaje,
            icon: 'success',
            confirmButtonText: 'Aceptar'
        });
    }

    // Alerta de error
    function alertaError(mensaje) {
        Swal.fire({
            title: '¡Error!',
            text: mensaje,
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    }
</script>
