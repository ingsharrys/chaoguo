// Función para cargar los datos de los turnos
function cargarDatosTurnos() {
    const fechaSeleccionada = document.getElementById('fechaSeleccionada').value || new Date().toISOString().split('T')[0];

    // Mostrar en consola la fecha que se está enviando para depuración
    console.log('Fecha seleccionada para enviar:', fechaSeleccionada);

    // Parámetros para enviar con la consulta
    const params = new URLSearchParams({
        fecha: fechaSeleccionada
    });

    fetch('../controllers/obtener_datos_consolidado.php?' + params.toString())
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data); // Para depurar
            const turnosContainer = document.getElementById('turnos-container');
            turnosContainer.innerHTML = '';  // Limpiar contenedor antes de llenarlo

            if (Array.isArray(data.turnos) && data.turnos.length > 0) {
                let tablaHTML = `
                    <table id="tabla-turnos" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Turno</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>Método de Pago</th>
                                <th>Productos</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="turnos-body">
                        </tbody>
                    </table>
                `;
                turnosContainer.innerHTML = tablaHTML;

                const turnosBody = document.getElementById('turnos-body');

                data.turnos.forEach(turno => {
                    let fila = document.createElement('tr');
                    fila.id = `fila-turno-${turno.id_pedidoc}`;
                    fila.className = turno.estado === 'nuevo' ? 'table-warning' : (turno.estado === 'en_cocina' ? 'table-primary' : 'table-secondary');

                    // Añadir columna para los productos (inicialmente vacía)
                    let productosHTML = `<div id="productos-${turno.id_pedidoc}">Cargando productos...</div>`;

                    let botonesAccion = `
                        <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                            <input type="hidden" name="numero_pedido" value="${turno.id_pedidoc}">
                            <button class="btn btn-info">Caja</button>
                        </form>
                    `;

                    fila.innerHTML = `
                        <td><h1>${turno.turno}</h1></td>
                        <td>${turno.cliente}</td>
                        <td>${turno.estado || 'Sin estado'}</td>
                        <td>${turno.m_pago}</td>
                        <td style="font-size: 1vw;">${productosHTML}</td>
                        <td id="accion-${turno.id_pedidoc}">${botonesAccion}</td>
                    `;
                    turnosBody.appendChild(fila);

                    // Cargar productos para cada pedido
                    cargarProductos(turno.id_pedidoc);
                });
            } else {
                turnosContainer.innerHTML = '<p>No hay turnos disponibles.</p>';
            }
        })
        .catch(error => console.error('Error al cargar los datos de turnos:', error));
}

// Función para cargar los productos de un pedido
function cargarProductos(numeroPedido) {
    const params = new URLSearchParams({
        id_pedido: numeroPedido
    });

    fetch('../controllers/obtener_productos_pedido.php?' + params.toString())
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Productos recibidos:', data); // Para depurar

            let productosHTML = '<ul>';
            data.productos.forEach(producto => {
                productosHTML += `<li><b>Producto:</b> ${producto.producto} - <b>Cantidad:</b> ${producto.cantidad} - <b>Precio:</b> $${producto.precio}</li>`;
            });
            productosHTML += '</ul>';

            const productosContainer = document.getElementById(`productos-${numeroPedido}`);
            productosContainer.innerHTML = productosHTML;
        })
        .catch(error => console.error('Error al cargar los productos del pedido:', error));
}

// Inicializar el selector de fecha y cargar los datos al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    setFechaActual();  // Inicializa el campo de fecha con la fecha actual
    cargarDatosTurnos();  // Carga los turnos de la fecha actual por defecto
});

// Función para establecer la fecha actual en el campo de fecha
function setFechaActual() {
    const filtroFecha = document.getElementById('fechaSeleccionada');
    if (filtroFecha) {
        const fechaActual = new Date().toISOString().split('T')[0];
        filtroFecha.value = fechaActual;
    }
}
