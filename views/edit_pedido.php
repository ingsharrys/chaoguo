<?php
// Mostrar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$numero_pedido = isset($_POST['numero_pedido']) ? $_POST['numero_pedido'] : null;

if (!$numero_pedido) {
    die(json_encode(['status' => 'error', 'message' => 'Número de pedido no proporcionado.']));
}

// Obtener productos disponibles
$queryProductos = "SELECT id_pro, nombre, tcomida FROM productos";
$stmtProductos = $conn->prepare($queryProductos);
$stmtProductos->execute();
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos en el pedido con `tcomida`
$queryPedido = "SELECT p.*, pr.nombre AS nombre_producto, pr.tcomida 
                FROM pedidos p
                JOIN productos pr ON p.id_pro = pr.id_pro
                WHERE p.numero_pedido = :numero_pedido";

$stmtPedido = $conn->prepare($queryPedido);
$stmtPedido->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
$stmtPedido->execute();
$productosPedido = $stmtPedido->fetchAll(PDO::FETCH_ASSOC);

if (empty($productosPedido)) {
    die(json_encode(['status' => 'error', 'message' => 'No se encontraron productos en este pedido.']));
}

// Función para obtener los tipos de producto permitidos según `tcomida`
function obtenerTiposProducto($conn, $id_pro)
{
    $tipos = [];

    // Consulta para obtener los tipos de producto asociados al `id_pro` en la tabla `precios`
    $query = "SELECT tipo_prod FROM precios WHERE idproduc = :id_pro";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_pro', $id_pro, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_COLUMN); // Obtener solo la columna de `tipo_prod`

    if ($resultados) {
        $tipos = $resultados;
    }

    return $tipos;
}


// Función para obtener los detalles permitidos según `tcomida`
function obtenerDetallesPermitidos($tcomida)
{
    $detalles = [];
    if ($tcomida == 1) {
        $detalles = ['amarillo', 'cafe'];
    } elseif ($tcomida == 2) {
        $detalles = ['papa', 'amarillo', 'cafe'];
    } elseif ($tcomida == 10) {
        $detalles = ['Sindetalle'];
    }

    return $detalles;
}
?>


<div class="container mt-5">
    <h1>Editar Pedido #<?php echo htmlspecialchars($numero_pedido); ?></h1>
    <form action="../controllers/procesar_edicion_pedido.php" method="POST" id="form-editar-pedido">
        <input type="hidden" name="numero_pedido" value="<?php echo htmlspecialchars($numero_pedido); ?>">

        <div id="productos-container">
            <?php foreach ($productosPedido as $index => $producto): ?>
                <div class="producto-item" data-tcomida="<?php echo htmlspecialchars($producto['tcomida']); ?>">
                    <input type="hidden" name="productos_existentes[<?php echo $producto['id_pedido']; ?>][id_pedido]"
                        value="<?php echo $producto['id_pedido']; ?>">


                    <!-- Select para el producto -->
                    <div class="form-group">
                        <label>Producto</label>
                        <select name="productos_existentes[<?php echo $producto['id_pedido']; ?>][id_pro]"
                            class="form-control producto-select" required
                            onchange="actualizarProductoNombre(this, <?php echo $index; ?>)">
                            <option value="">Seleccionar</option>
                            <?php
                            // Mostrar el listado de productos
                            foreach ($productos as $prod) {
                                $selected = ($prod['id_pro'] == $producto['id_pro']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($prod['id_pro']) . '" ' . $selected . '>' . htmlspecialchars($prod['nombre']) . '</option>';
                            }
                            ?>
                        </select>
                        <!-- Input oculto para el nombre del producto -->
                        <input type="hidden"
                            name="productos_existentes[<?php echo $producto['id_pedido']; ?>][nombre_producto]"
                            id="nombre_producto_<?php echo $index; ?>"
                            value="<?php echo htmlspecialchars($producto['nombre_producto']); ?>">
                    </div>

                    <div class="form-row">
                        <!-- Cantidad -->
                        <div class="form-group col-md-2">
                            <label>Cantidad</label>
                            <input type="number"
                                name="productos_existentes[<?php echo $producto['id_pedido']; ?>][cantidad]"
                                class="form-control input-number" value="<?php echo $producto['cantidad']; ?>" min="1"
                                required>
                        </div>

                        <!-- Tipo de Producto -->
                        <div class="form-group">
                            <label>Tipo de Producto</label>
                            <select name="productos_existentes[<?php echo $producto['id_pedido']; ?>][tipo_producto]"
                                class="form-control tipo-producto-select" required>
                                <option value="">Seleccionar</option>
                                <!-- Mostrar la opción seleccionada de la base de datos primero -->

                                <option value="<?php echo htmlspecialchars($producto['tipo_producto']); ?>" selected>
                                    <?php echo htmlspecialchars($producto['tipo_producto']); ?></option>

                                <?php
                                $tiposPermitidos = obtenerTiposProducto($conn, $producto['id_pro']);
                                foreach ($tiposPermitidos as $tipo) {
                                    echo '<option value="' . htmlspecialchars($tipo) . '" ' .
                                        (($tipo == $producto['tipo_producto']) ? 'selected' : '') . '>' .
                                        htmlspecialchars($tipo) . '</option>';
                                }
                                ?>
                            </select>
                        </div>





                        <!-- Detalle -->
                        <div class="form-group col-md-5">
                            <label>Detalle: <?php echo htmlspecialchars($producto['detalle'] ?: 'Sindetalle'); ?></label>
                            <select name="productos_existentes[<?php echo $producto['id_pedido']; ?>][detalle]"
                                class="form-control detalle-select" required>
                                <?php
                                // Obtener los detalles permitidos para el producto actual usando tcomida
                                $detallesPermitidos = obtenerDetallesPermitidos($producto['tcomida']);
                                // Si el detalle actual está vacío, se asigna 'Sindetalle'
                                $detalleActual = !empty($producto['detalle']) ? $producto['detalle'] : 'Sindetalle';
                                foreach ($detallesPermitidos as $detalle) {
                                    $selected = ($detalle == $detalleActual) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($detalle) . '" ' . $selected . '>' . htmlspecialchars($detalle) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                    </div>



                    <!-- Botón Eliminar -->
                    <!-- Botón Eliminar -->
                    <button type="button" class="btn btn-danger btn-eliminar-producto"
                        data-id-pedido="<?php echo $producto['id_pedido']; ?>" onclick="eliminarProducto(this)" <?php echo ($index === 0) ? 'disabled' : ''; ?>>
                        Eliminar
                    </button>

                </div>
                <hr>
            <?php endforeach; ?>
        </div>

        <div class="justify-content-between">
    <!-- Botón para agregar un nuevo producto -->
    <button type="button" id="btn-agregar-producto" class="btn btn-primary">Agregar Producto</button>
    <button type="submit" class="btn btn-success" onclick="verificarEnvio()">Guardar Cambios</button>
    <button type="button" class="btn btn-danger" onclick="eliminarPedido(<?php echo htmlspecialchars($numero_pedido); ?>)">Eliminar Pedido</button>
</div>
    </form>
</div>


<!-- Modal para confirmar eliminación -->
<div class="modal fade" id="modalEliminarPedido" tabindex="-1" role="dialog" aria-labelledby="modalLabelEliminar"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabelEliminar">Eliminar Pedido</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Por favor, ingresa el código de seguridad para eliminar el pedido:</p>
                <input type="password" id="codigoSeguridad" class="form-control" placeholder="Código de seguridad">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarEliminarPedido">Eliminar</button>
            </div>
        </div>
    </div>
</div>
<script>
    function eliminarProducto(button) {
        // Obtener el id_pedido del producto (no el id_pro)
        const idPedido = button.closest('.producto-item').querySelector('input[name^="productos_existentes"][name$="[id_pedido]"]') ?
            button.closest('.producto-item').querySelector('input[name^="productos_existentes"][name$="[id_pedido]"]').value :
            button.getAttribute('data-id-pedido');

        if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
            fetch('../controllers/eliminar_producto_pedido.php', {  // Cambiado a eliminar_producto_pedido.php
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_pedido: idPedido })  // Solo necesitamos enviar id_pedido
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        button.closest('.producto-item').remove();
                        alert('Producto eliminado correctamente del pedido.');
                    } else {
                        alert('Error al eliminar el producto del pedido: ' + data.message);
                    }
                })
                .catch(error => console.error('Error al eliminar el producto del pedido:', error));
        }
    }


    function eliminarPedido(numero_pedido) {
    // Mostrar el modal de confirmación de eliminación
    $('#modalEliminarPedido').modal('show');

    // Asignar la función de confirmación al botón dentro del modal
    document.getElementById('confirmarEliminarPedido').onclick = function () {
        const codigoSeguridad = document.getElementById('codigoSeguridad').value;
console.log(numero_pedido);
console.log(codigoSeguridad);
        if (codigoSeguridad) {
            // Enviar solicitud para eliminar el pedido
            fetch('../controllers/eliminar_pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    numero_pedido: numero_pedido, 
                    codigo_seguridad: codigoSeguridad 
                })
            })
            .then(response => {
                // 1) Imprimir status HTTP
                console.log("Eliminar pedido -> HTTP status:", response.status);

                // 2) Si quieres ver si hay algún body en texto
                // return response.text(); // (Úsalo en lugar de .json() si quieres ver el texto crudo)

                return response.json();
            })
            .then(data => {
                // 3) Mostrar en consola la respuesta JSON
                console.log("Eliminar pedido -> Respuesta JSON:", data);

                if (data.success) {
                    alert('Pedido eliminado correctamente.');
                    // Redirigir después de la eliminación
                    window.location.href = '/public/';
                } else {
                    // Mostrar el error devuelto por el servidor
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                // 4) Manejo de errores de red u otro
                console.error('Error al eliminar el pedido (fetch/catch):', error);
                alert("No se pudo conectar con el servidor para eliminar el pedido.");
            });

            // Cerrar el modal después de enviar la solicitud
            $('#modalEliminarPedido').modal('hide');
        } else {
            alert("El código de seguridad es obligatorio para eliminar el pedido.");
        }
    };
}


    // Función para mostrar los datos que se están enviando
    function actualizarProductoNombre(selectElement, index) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const nombreProducto = selectedOption.text;

        // Actualizar el input hidden con el nombre del producto
        document.getElementById('nombre_producto_' + index).value = nombreProducto;

        // Actualiza también los tipos de producto y detalles si es necesario
        const productoId = selectElement.value;
        const productoItem = selectElement.closest('.producto-item');
        if (!productoId) {
            return;
        }
        actualizarTiposProductoYDetalles(productoId, productoItem);
    }

    // Función para cargar los tipos de productos desde el servidor
    function actualizarTiposProductoYDetalles(productoId, productoItem) {
        fetch(`../controllers/obtener_tipos_producto.php?id_pro=${productoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const tipoProductoSelect = productoItem.querySelector('.tipo-producto-select');
                    const detalleSelect = productoItem.querySelector('.detalle-select');

                    // Guardar el valor actual antes de sobrescribir
                    const tipoProductoActual = tipoProductoSelect.value;
                    const detalleActual = detalleSelect.value;

                    // Actualizar el select de tipos de producto
                    tipoProductoSelect.innerHTML = data.tipos.map(tipo => {
                        const selected = tipo === tipoProductoActual ? 'selected' : '';
                        return `<option value="${tipo}" ${selected}>${tipo}</option>`;
                    }).join('');

                    // Obtener tcomida desde el atributo data-tcomida del contenedor
                    const tcomida = productoItem.getAttribute('data-tcomida');
                    console.log("Actualizando detalles para tcomida:", tcomida);

                    // Actualizar el select de detalles usando el valor de tcomida
                    const detalles = obtenerDetallesPermitidos(tcomida);
                    detalleSelect.innerHTML = detalles.length > 0
                        ? detalles.map(detalle => {
                            const selected = detalle === detalleActual ? 'selected' : '';
                            return `<option value="${detalle}" ${selected}>${detalle}</option>`;
                        }).join('')
                        : '<option value="NULL">Ninguno</option>';
                } else {
                    console.error('Error al obtener tipos de producto:', data.message);
                }
            })
            .catch(error => console.error('Error al obtener tipos de producto:', error));
    }


    // Cargar valores por defecto al cargar la página
    function cargarValoresPorDefecto() {
        document.querySelectorAll('.producto-item').forEach(function (item) {
            const selectElement = item.querySelector('.producto-select');
            const productoId = selectElement.value;
            if (productoId) {
                actualizarTiposProductoYDetalles(productoId, item);
            }
        });
    }

    // Ejecutar cuando la página esté lista
    document.addEventListener('DOMContentLoaded', cargarValoresPorDefecto);


    // Función para mostrar el envío en consola
    function verificarEnvio() {
        const formData = new FormData(document.getElementById('form-editar-pedido'));
        const entries = Object.fromEntries(formData);
        console.log("Datos que se envían:", entries);
    }


    // Función para obtener los detalles permitidos desde el cliente (JavaScript)
    function obtenerDetallesPermitidos(tcomida) {
        let detalles = [];
        console.log("tcomida recibida:", tcomida);
        tcomida = parseInt(tcomida);
        if (tcomida === 1) {
            detalles = ['amarillo', 'cafe'];
        } else if (tcomida === 2) {
            detalles = ['papa', 'amarillo', 'cafe'];
        } else if (tcomida === 10) {
            detalles = ['Sindetalle'];
        }
        return detalles;
    }


    // Función para actualizar dinámicamente el tipo de producto y detalles
    function actualizarProductoNombre(selectElement, index) {
        const productoId = selectElement.value;
        const productoItem = selectElement.closest('.producto-item');
        if (!productoId) {
            return;
        }
        actualizarTiposProductoYDetalles(productoId, productoItem);
    }

    // Cargar valores por defecto al cargar la página
    function cargarValoresPorDefecto() {
        document.querySelectorAll('.producto-item').forEach(function (item) {
            const selectElement = item.querySelector('.producto-select');
            const productoId = selectElement.value;
            if (productoId) {
                actualizarTiposProductoYDetalles(productoId, item);
            }
        });
    }

    // Ejecutar cuando la página esté lista
    document.addEventListener('DOMContentLoaded', cargarValoresPorDefecto);

    // Función para agregar un nuevo producto
    document.getElementById('btn-agregar-producto').addEventListener('click', function () {
        const nuevoProductoIndex = document.querySelectorAll('.producto-item').length; // Para identificar el índice de producto

        let nuevoProductoHTML = `
        <div class="producto-item">
            <div class="form-group">
                <label>Producto</label>
                <select name="productos_nuevos[${nuevoProductoIndex}][id_pro]" class="form-control producto-select" required onchange="actualizarProductoNombre(this, ${nuevoProductoIndex})">
                    <option value="">Seleccionar Producto</option>
                    <?php foreach ($productos as $producto): ?>
                        <option value="<?php echo $producto['id_pro']; ?>"><?php echo $producto['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group col-md-2">
                    <label>Cantidad</label>
                    <input type="number" name="productos_nuevos[${nuevoProductoIndex}][cantidad]" class="form-control input-number" value="1" min="1" required>
                </div>
                <div class="form-group col-md-5">
                    <label>Tipo de Producto</label>
                    <select name="productos_nuevos[${nuevoProductoIndex}][tipo_producto]" class="form-control tipo-producto-select" required>
                        <option value="">Seleccionar Tipo</option>
                    </select>
                </div>
                <div class="form-group col-md-5">
                    <label>Detalle</label>
                    <select name="productos_nuevos[${nuevoProductoIndex}][detalle]" class="form-control detalle-select" required>
                        <option value="NULL">Ninguno</option>
                    </select>
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-eliminar-producto" onclick="eliminarProductoNuevo(this)">Eliminar</button>
        </div>
        <hr>`;

        document.getElementById('productos-container').insertAdjacentHTML('beforeend', nuevoProductoHTML);
    });



    // Función para eliminar nuevos productos
    function eliminarProductoNuevo(button) {
        if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
            button.closest('.producto-item').remove();
        }
    }



</script>