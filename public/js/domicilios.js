   //ABRIR VRNTANA EMERGENTE PARA EL PEDIDO
   function openPopupWindow(form) {
    const url = new URL(form.action);
    const params = new URLSearchParams(new FormData(form));
    url.search = params.toString();
    window.open(url, 'Registrar Pedido', 'width=400,height=600');
}

// -------------------------------------------------------------------
// Función para obtener datos de la tabla 'mesas'
// --------------------------------------------------------------------

function cargarDatosMesas() {
    fetch('../controllers/obtener_datos.php') 
.then(response => response.json())
.then(data => {
    const mesasContainer = document.getElementById('mesas-container');

    // Limpiar contenedor de mesas
    mesasContainer.innerHTML = '';

    // Ordenar las mesas por número
    if (Array.isArray(data.mesas) && data.mesas.length > 0) {
        data.mesas.sort((a, b) => a.numero_mesa - b.numero_mesa); // Ordenar numéricamente
        
        data.mesas.forEach(mesa => {
            const mesaButton = document.createElement('div');
            mesaButton.className = 'col-md-4 mb-2';

            // Determinar la clase de color del botón según el estado
            let buttonClass = 'btn-secondary';  // Color por defecto (gris) si el estado está vacío

            if (mesa.estado === 'nuevo') {
                buttonClass = 'btn-warning';  // Color amarillo para "nuevo"
            } else if (mesa.estado === 'en_cocina') {
                buttonClass = 'btn-primary';  // Color azul para "en_cocina"
            } else if (mesa.estado === 'entregado' && mesa.pagado) {
                buttonClass = 'btn-secondary';  // Color gris si está entregado y pagado
            } else if (mesa.estado === 'entregado' && !mesa.pagado) {
                buttonClass = 'btn-success';  // Color verde si está entregado y no pagado
            } else if (
                (mesa.estado === '' || mesa.estado === 'Sin estado') &&  // Estado vacío o "Sin estado"
                mesa.pagado &&                                           // Pedido pagado
                mesa.id_pedido                                           // Comprobar si hay un id_pedido
            ) {
                buttonClass = 'btn-secondary';  // Color gris si cumple todas las condiciones
            } else if (
                (mesa.estado === '' || mesa.estado === 'Sin estado') &&  // Estado vacío o "Sin estado"
                !mesa.pagado &&                                          // No existe valor en caja (no pagado)
                mesa.id_pedido                                           // Comprobar si hay un id_pedido
            ) {
                buttonClass = 'btn-success';  // Color verde si cumple todas las condiciones
            }

            // Determinar el texto del botón basado en el estado de pago
            let buttonText = '';
            if (mesa.estado === '' && !mesa.id_pedido) {
                buttonText = `Mesa ${mesa.numero_mesa}`;
            } else {
                const estadoText = mesa.estado || 'Sin estado';
                const cajaText = mesa.pagado ? 'Pagado' : 'Por pagar';
                buttonText = `Mesa ${mesa.numero_mesa}<br>${estadoText}<br>${cajaText}`;
            }

            mesaButton.innerHTML = `
                <button class="btn ${buttonClass} btn-block" 
                    onclick="procesarMesa(${mesa.id_pedido}, '${mesa.estado}', ${mesa.numero_mesa}, ${mesa.pagado})">
                    ${buttonText}
                </button>
            `;

            mesasContainer.appendChild(mesaButton);
        });
    } else {
        mesasContainer.innerHTML = '<p>No hay mesas disponibles.</p>';
    }
})
.catch(error => console.error('Error al cargar los datos:', error));

}

// -------------------------------------------------------------------
// Función para procesar datos de la 'mesas'
// --------------------------------------------------------------------

function procesarMesa(idPedido, estado, numeroMesa, pagado) {
    // Si el estado es entregado y está pagado, se puede liberar la mesa
    if (estado === 'entregado' && pagado) {
        if (confirm('¿Deseas liberar esta mesa?')) {
            // Enviar solicitud para liberar la mesa
            fetch('../controllers/liberar_mesa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', },
                body: JSON.stringify({ numero_mesa: numeroMesa })
            })
        .then(response => response.json()) // Convertir la respuesta en JSON
        .then(data => {
            if (data.success) {
                alert(data.message); // Mostrar mensaje de éxito
                cargarDatosMesas();  // Volver a cargar las mesas
            } else {
                alert('Error: ' + data.message); // Mostrar mensaje de error si lo hay
            }
        })
        .catch(error => console.error('Error al liberar la mesa:', error));  // Manejar errores de red o JSON
        }
    } 
    // Si el estado es entregado pero no está pagado, abrir el modal
    else if (estado === 'entregado' && !pagado) {
        mostrarModal(idPedido, estado);
    } 
    // Para cualquier otro estado, también abrir el modal
    else {
        mostrarModal(idPedido, estado);
    }
}

// -------------------------------------------------------------------
//mostrar Modal de La Mesa
// --------------------------------------------------------------------

function mostrarModal(numero_pedido, estado, pagado) {
// Llamar al backend para obtener datos del pedido
fetch(`../controllers/obtener_datos.php?numero_pedido=${numero_pedido}`)
    .then(response => response.json())
    .then(data => {
        // Seleccionar contenedor del modal
        const modalContent = document.getElementById('modal-content');
        modalContent.innerHTML = ''; // Limpiar contenido anterior
        const fecha = data.fecha ? new Date(data.fecha).toLocaleString('es-CO') : 'No disponible';

        let productosHTML = '';
        let total = 0;

        // Generar tabla con los productos
        if (data.productos && data.productos.length > 0) {
            productosHTML += `
            <div id="productos-${numero_pedido}">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Detalle</th>
                            <th>Tipo Producto</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>`;

            data.productos.forEach(producto => {
                const subtotal = producto.precio * producto.cantidad;
                total += subtotal;

                productosHTML += `
                    <tr>
                        <td>${producto.nombre}</td>
                        <td>${producto.cantidad}</td>
                        <td>${producto.detalle || ''}</td>
                        <td>${producto.tipo_producto || ''}</td>
                        <td>$${producto.precio.toLocaleString('es-CO', { minimumFractionDigits: 2 })}</td>
                        <td>$${subtotal.toLocaleString('es-CO', { minimumFractionDigits: 2 })}</td>
                    </tr> `;
            });

                productosHTML += `
                    </tbody></table>
                    <p><strong>Total: $${total.toLocaleString('es-CO', { minimumFractionDigits: 2 })}</strong></p>
                    </div>`;
        } else {
            productosHTML = '<p>No hay productos disponibles para este pedido.</p>';
        }
        console.log(data);
        

  // Insertar el número de mesa antes de la información del mesero y el select de cambio de mesa
let mesasHTML = '<option value="" disabled selected>Seleccionar mesa</option>'; // Opción por defecto

if (Array.isArray(data.mesas_libres) && data.mesas_libres.length > 0) {
mesasHTML += data.mesas_libres.map(mesa => 
    `<option value="${mesa.numero_mesa}">Mesa ${mesa.numero_mesa}</option>`
).join('');
} else {
mesasHTML += '<option value="" disabled>No hay mesas disponibles</option>';
}

        
        




        // Generar la lista de comentarios (si existen)
        let comentariosHTML = '';
        if (data.comentarios && data.comentarios.length > 0) {
            comentariosHTML += `
                <ul id="comentarios-${numero_pedido}">
            `;
            data.comentarios.forEach(com => {
                comentariosHTML += `<li>${com}</li>`;
            });
            comentariosHTML += `</ul>`;
        } else {
            comentariosHTML = `<p id="comentarios-${numero_pedido}">Sin comentarios</p>`;
        }

        // Botones dinámicos según el estado del pedido
        let botonesEstadoHTML = '';
        if (estado === 'nuevo') {
            botonesEstadoHTML = `
                <button type="button" class="btn btn-primary" 
                        onclick="cambiarEstadoMesa(${numero_pedido}, 'en_cocina')">Mandar a Cocina</button>`;
        } else if (estado === 'en_cocina') {
            botonesEstadoHTML = `
            <div class="d-flex justify-content-center">
                <button type="button" class="btn btn-warning mb-2 me-2" 
                        onclick="cambiarEstadoMesa(${numero_pedido}, 'entregado')">Entregar</button>
            </div>
            `;
        } 

        // Insertar todo en el modal
        modalContent.innerHTML = `
            <div id="fila-turno-${numero_pedido}">
                <p><strong>Mesa Actual:</strong> ${data.numero_mesa || 'No asignada'}</p>
                <p><strong>Mesero:</strong> ${data.nombre_mesero || 'No asignado'}</p>
             
                <label for="nueva_mesa">Cambiar Mesa:</label>
            <select id="nueva_mesa" class="form-control">
                <option value="">Seleccionar mesa</option>
                ${mesasHTML}
            </select>
            <button type="button" class="btn btn-primary mt-3" onclick="cambiarMesa(${numero_pedido}, ${data.numero_mesa})">Cambiar Mesa</button>
                <p><strong>Estado:</strong> ${estado}</p>
                <p><strong>Fecha y Hora:</strong> ${fecha}</p>

                <p><strong>Comentarios:</strong></p>
                ${comentariosHTML}

                ${productosHTML}
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                ${botonesEstadoHTML}
            <div class="d-flex justify-content-center">
                <button class="btn btn-success mb-2 me-2" onclick="printInvoicemesa(${numero_pedido})">
                    Imprimir
                </button>
            </div>
                
                <form action="../public/index.php?page=edit_pedido.php" method="POST" id="form-editar" style="display:inline;">
                  <input type="hidden" name="numero_pedido" id="numero_pedido_editar" value="${numero_pedido}">
                <div class="d-flex justify-content-center"> 
                  <button type="submit" class="btn btn-warning mb-2 me-2" id="boton-editar">Editar</button>
                </div>
                </form>
                
                <form action="../public/index.php?page=caja_tm.php" method="POST" id="form-pagar" style="display:inline;">
                  <input type="hidden" name="numero_pedido" id="numero_pedido" value="${numero_pedido}">   
                  <button type="submit" class="btn btn-danger" id="boton-editar">Pagar</button>
                  
                </form>
            </div>
        `;

        // Mostrar el modal
        $('#myModal').modal('show');
    })
    .catch(error => console.error('Error al obtener los productos:', error));
}



 // -------------------------------------------------------------------
 // CAMBIAR MESA
// --------------------------------------------------------------------

function cambiarMesa(numero_pedido, mesa_actual) {
const nueva_mesa = document.getElementById('nueva_mesa').value;

if (!nueva_mesa) {
    alert('Por favor, selecciona una mesa para cambiar.');
    return;
}

fetch('../controllers/cambiar_mesa.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        numero_pedido: numero_pedido,
        nueva_mesa: nueva_mesa,
        mesa_actual: mesa_actual
    })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        alert('Mesa cambiada exitosamente.');
        $('#myModal').modal('hide');  // Cerrar modal después de cambiar la mesa
        // Actualizar la UI o recargar los datos si es necesario
    } else {
        alert('Error al cambiar la mesa: ' + data.message);
    }
})
.catch(error => {
    console.error('Error al cambiar la mesa:', error);
});
}


 // -------------------------------------------------------------------
 // Función para cambiar el estado de la mesa
// --------------------------------------------------------------------


function cambiarEstadoMesa(numeroPedido, nuevoEstado) {
fetch('../controllers/actualizar_estado.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ numero_pedido: numeroPedido, nuevo_estado: nuevoEstado })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('El estado ha sido actualizado exitosamente.');
        $('#myModal').modal('hide');
        cargarDatosMesas();  // Volver a cargar los datos de las mesas
    } else {
        alert('Error al actualizar el estado.');
    }
})
.catch(error => console.error('Error al actualizar el estado:', error));
}

// Función para actualizar los temporizadores de los turnos cada segundo
function actualizarTemporizadores() {
document.querySelectorAll('[id^="temporizador-"]').forEach(temporizadorElement => {
    const numeroPedido = temporizadorElement.id.replace('temporizador-', '');
    const fechaTurno = new Date(temporizadorElement.getAttribute('data-fecha-turno'));
    const tiempoRestante = calcularTiempoRestante(fechaTurno);
    temporizadorElement.innerText = tiempoRestante;
});
}

 // -------------------------------------------------------------------
 //TERMINA FUNCIONES DE LAS MESAS
// --------------------------------------------------------------------

let isLoading = false; // Variable para evitar múltiples llamadas simultáneas


 // -------------------------------------------------------------------
 // Función para cargar TODOS los turnos sin paginación
// --------------------------------------------------------------------


function cargarDatosTurnos(tipoSolicitud) {
    if (isLoading) return; // Evita múltiples solicitudes simultáneas
    isLoading = true; 

   // console.log(`Enviando solicitud: tipo_solicitud=${tipoSolicitud}`);

    // Se realiza la petición (fetch) al backend
    fetch(`../controllers/obtener_datos_turnos.php?tipo_solicitud=${tipoSolicitud}`)
        .then(response => {
            if (!response.ok) throw new Error("Error al obtener los datos");
            return response.json();
        })
        .then(data => {
           // console.log('Datos recibidos:', data);

            const turnosContainer = document.getElementById('turnos-container');
            if (!turnosContainer) return;

            // 1. Verifica si ya existe la tabla con id="tabla-turnos"
            //    Si NO existe, la crea dinámicamente.
            if (!document.getElementById('tabla-turnos')) {
                turnosContainer.innerHTML = `
                    <table id="tabla-turnos" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>N° Turno</th>
                                <th>Cliente</th>
                                <th>Tiempo</th>
                                <th>Estado</th>
                                <th>Productos</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="turnos-body"></tbody>
                    </table>
                `;
            }

            // 2. Obtiene el <tbody> de la tabla para insertar o actualizar filas
            const turnosBody = document.getElementById('turnos-body');

            // 3. Crea un mapa para evitar duplicados (por número de pedido)
            const turnosMap = new Map();

            // 4. Verifica si se recibieron turnos en el arreglo data.turnos
            if (Array.isArray(data.turnos) && data.turnos.length > 0) {

                // Recorre cada turno/pedido
                data.turnos.forEach(turno => {
                    // a) Omitir los pedidos que ya están entregados y pagados
                    if (turno.estado === 'entregado' && turno.pagado) return;

                    // b) Evitar duplicados: si el número de pedido ya está, saltar
                    if (turnosMap.has(turno.numero_pedido)) return;
                    turnosMap.set(turno.numero_pedido, true);

                    // c) Convertir la fecha en objeto Date y calcular el tiempo restante
                    const fechaTurno = new Date(turno.fecha);
                    const tiempoRestante = calcularTiempoRestante(fechaTurno);

                    // d) Determinar el estado del pedido y su clase de color para la tabla
                    const estadoPedido = turno.estado || 'Sin estado';
                    let rowClass =
                        estadoPedido === 'nuevo'      ? 'table-warning' :
                        estadoPedido === 'en_cocina'  ? 'table-primary' :
                        estadoPedido === 'entregado'  ? 'table-success' :
                                                         'table-secondary';

                    // g) Verifica si existe la fila <tr> para el turno actual (por su ID)
                    let fila = document.getElementById(`fila-turno-${turno.numero_pedido}`);

                    // Si la fila YA existe, solo actualiza los datos (estado, tiempo, etc.)
                    if (fila) {
                        fila.className = rowClass;
                        document.getElementById(`temporizador-${turno.numero_pedido}`).innerText = tiempoRestante;
                        
                        // Actualiza el estado mostrado
                        const estadoTd = fila.querySelector('td:nth-child(4)');
                        if (estadoTd) {
                            estadoTd.innerHTML = estadoPedido + (turno.pagado ? '<br>Pagado' : '');
                        }
                        
                        // Solo actualiza los botones de acción si el estado NO es 'entregado'
                        // o si el contenedor de acciones está vacío
                        const accionTd = document.getElementById(`accion-${turno.numero_pedido}`);
                        if (estadoPedido !== 'entregado' || !accionTd.innerHTML.includes('btn')) {
                            // e) Construye los botones de acción según el estado y si está pagado o no
                            let botonesAccion = '';
                            
                            // Solo mostrar botón de cambio de estado si NO está entregado
                            if (estadoPedido !== 'entregado') {
                                botonesAccion += `
                                <div class="d-flex justify-content-center">
                                    <button class="btn me-2 mb-2 btn-${estadoPedido === 'nuevo' ? 'warning' : 'primary'}" 
                                        onclick="cambiarEstadoTurnero(${turno.numero_pedido}, '${estadoPedido === 'nuevo' ? 'en_cocina' : 'entregado'}')">
                                        ${estadoPedido === 'nuevo' ? 'Cocina' : 'Entregar'}
                                    </button>
                                 </div>
                                `;
                            }
                            
                            // Botón de imprimir siempre visible
                            botonesAccion += `
                            <div class="d-flex justify-content-center">
                                <button class="btn btn-success mb-2 me-2" onclick="printInvoicepc(${turno.numero_pedido})">Imprimir</button>
                            </div>`;

                            // Si es un tipo de solicitud especial (por ejemplo, 50), añade botones adicionales
                            if (tipoSolicitud == 50) {
                                if (turno.tiene_domiciliario) {
                                    botonesAccion += `
                                    <div class="d-flex justify-content-center">
                                        <button class="btn btn-success mb-2 me-2" onclick="mostrarModalTurno(${turno.numero_pedido})">
                                            Despachado
                                        </button>
                                    </div>
                                    `;
                                } else {
                                    botonesAccion += `
                                    <div class="d-flex justify-content-center">
                                        <button class="btn btn-warning mb-2 me-2" onclick="mostrarModalTurno(${turno.numero_pedido})">
                                            Despachar
                                        </button>
                                    </div>
                                    `;
                                }
                            }

                            // Botones para "Pagado" o ir a "Caja" y "Editar"
                            if (turno.pagado) {
                                botonesAccion += `
                                    <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="numero_pedido" value="${turno.numero_pedido}">
                                    <div class="d-flex justify-content-center">
                                        <button class="btn btn-success mb-2 me-2">Pagado</button>
                                    </div>
                                    </form>
                                `;
                            } else {
                                botonesAccion += `
                                    <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="numero_pedido" value="${turno.numero_pedido}">
                                    <div class="d-flex justify-content-center">
                                        <button class="btn btn-info me-2 mb-2">Caja</button>
                                    </div>
                                    </form>
                                    <form action="../public/index.php?page=edit_pedido.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="numero_pedido" value="${turno.numero_pedido}">
                                    <div class="d-flex justify-content-center">
                                        <button class="btn btn-warning">Editar</button>
                                    </div>
                                    </form>
                                `;
                            }
                            
                            accionTd.innerHTML = botonesAccion;
                        }
                    } else {
                        // Si la fila NO existe, crea una nueva <tr> y la inserta en el tbody
                        
                        // e) Construye los botones de acción según el estado y si está pagado o no
                        let botonesAccion = '';
                        
                        // Solo mostrar botón de cambio de estado si NO está entregado
                        if (estadoPedido !== 'entregado') {
                            botonesAccion += `
                            <div class="d-flex justify-content-center">
                                <button class="btn mb-2 me-2 btn-${estadoPedido === 'nuevo' ? 'warning' : 'primary'}" 
                                    onclick="cambiarEstadoTurnero(${turno.numero_pedido}, '${estadoPedido === 'nuevo' ? 'en_cocina' : 'entregado'}')">
                                    ${estadoPedido === 'nuevo' ? 'Cocina' : 'Entregar'}
                                </button>
                            </div>
                            `;
                        }
                        
                        botonesAccion += `
                        <div class="d-flex justify-content-center">
                            <button class="btn btn-success mb-2 me-2" onclick="printInvoicepc(${turno.numero_pedido})">Imprimir</button>
                        </div>`;

                        // Si es un tipo de solicitud especial (por ejemplo, 50), añade botones adicionales
                        if (tipoSolicitud == 50) {
                            if (turno.tiene_domiciliario) {
                                botonesAccion += `
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-success mb-2 me-2" onclick="mostrarModalTurno(${turno.numero_pedido})">
                                        Despachado
                                    </button>
                                </div>
                                `;
                            } else {
                                botonesAccion += `
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-warning mb-2 me-2" onclick="mostrarModalTurno(${turno.numero_pedido})">
                                        Despachar
                                    </button>
                                </div>
                                `;
                            }
                        }

                        // Botones para "Pagado" o ir a "Caja" y "Editar"
                        if (turno.pagado) {
                            botonesAccion += `
                                <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="numero_pedido" value="${turno.numero_pedido}">
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-success mb-2 me-2">Pagado</button>
                                </div>
                                </form>
                            `;
                        } else {
                            botonesAccion += `
                                <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="numero_pedido" value="${turno.numero_pedido}">
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-info me-2 mb-2">Caja</button>
                                </div>
                                </form>
                                <form action="../public/index.php?page=edit_pedido.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="numero_pedido" value="${turno.numero_pedido}">
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-warning">Editar</button>
                                </div>
                                </form>
                            `;
                        }

                        // f) Información del cliente (depende de si es tipoSolicitud=50 o no)
                        let clienteInfo = tipoSolicitud == 50
                            ? `
                                <td style="font-size: 8pt; width: 150px; word-wrap: break-word; white-space: normal;">
                                    <strong>Cliente:</strong> ${turno.cliente}<br>
                                    <strong>Dirección:</strong> ${turno.direccion || 'No especificada'}<br>
                                    <strong>Barrio:</strong> ${turno.barrio || 'No especificado'}<br>
                                    <a href="https://api.whatsapp.com/send?phone=57${turno.telefono}&text=Hola, quisiera información sobre mi pedido." target="_blank">
                                        ${turno.telefono}
                                    </a><br>
                                    <strong>Fecha y Hora:</strong> ${fechaTurno.toLocaleString('es-CO')}
                                </td>`
                            : `
                                <td>
                                    <strong>Cliente:</strong> ${turno.cliente}<br>
                                    ${turno.telefono ? `<a href="tel:${turno.telefono}">${turno.telefono}</a>` : 'No disponible'}<br>
                                    
                                </td>`;

                        fila = document.createElement('tr');
                        fila.id = `fila-turno-${turno.numero_pedido}`;
                        fila.className = rowClass;

                        fila.innerHTML = `
                            <td><h1>${turno.turno}</h1></td>
                            ${clienteInfo}
                            <td id="temporizador-${turno.numero_pedido}" data-fecha-turno="${fechaTurno.toISOString()}">
                                ${tiempoRestante}
                            </td>
                            <td>${estadoPedido}${turno.pagado ? '<br>Pagado' : ''}</td>
                            <td id="productos-${turno.numero_pedido}">Cargando productos...</td>
                            <td id="accion-${turno.numero_pedido}">${botonesAccion}</td>
                        `;

                        // Se agrega la fila al tbody
                        turnosBody.appendChild(fila);

                        // Se llama a otra función (no mostrada aquí) para cargar los productos de ese pedido
                        cargarProductos(turno.numero_pedido);
                    }
                });

                // 5. Crear un intervalo (setInterval) para que cada 1 segundo actualice el tiempo restante
                setInterval(() => {
                    data.turnos.forEach(turno => {
                        const fila = document.getElementById(`temporizador-${turno.numero_pedido}`);
                        if (fila) {
                            const tiempoRestanteActualizado = calcularTiempoRestante(new Date(turno.fecha));
                            fila.innerText = tiempoRestanteActualizado;
                        }
                    });
                }, 1000);

            } else {
                // Si no hay turnos, se muestra un mensaje simple
                turnosContainer.innerHTML = '<p>No hay turnos disponibles.</p>';
            }

            // Se libera el candado que evita solicitudes simultáneas
            isLoading = false;
        })
        .catch(error => {
            console.error('Error al cargar los datos de turnos:', error);
            // Si hay error, también se libera el candado
            isLoading = false;
        });
}

// Función para cargar productos de un pedido específico
function cargarProductos(numero_pedido) {
$('#myModal').modal('hide'); //Ocultar Modal
fetch(`../controllers/obtener_datos_pedido.php?id_pedido=${numero_pedido}`)
    .then(response => response.json())
    .then(data => {
        console.log("Respuesta del servidor para pedido:", numero_pedido, data);

        let productosHTML = '<ul>';
        let totalPedido = 0;

        // Comentario
        let comentarioHTML = data.comentario 
            ? `<p id="comentarios-${numero_pedido}"><strong>Comentario:</strong> ${data.comentario}</p>` 
            : `<p id="comentarios-${numero_pedido}"><strong>Comentario:</strong> No disponible</p>`;

        // Mira en la consola si data.costo_domicilio es null o un valor
        console.log("Costo de domicilio para pedido", numero_pedido, "=", data.costo_domicilio);

        // Tomar el tipoSolicitud
        const tipoSolicitud = document.getElementById('tipoSolicitud').value;

        // Construir la lista de productos
        if (data.productos && data.productos.length > 0) {
            data.productos.forEach(producto => {
                const subtotal = producto.precio * producto.cantidad;
                totalPedido += subtotal;

                const precioFormateado = Math.round(producto.precio).toLocaleString('es-CO');
                const subtotalFormateado = Math.round(subtotal).toLocaleString('es-CO');

                productosHTML += `
                    <li>
                       <b>${producto.cantidad}</b> 
                       - ${producto.tipo_prod} 
                       ${producto.nombre_producto.substring(0, 40)}
                       ${producto.nombre_producto.length > 40 ? '...' : ''} 
                       - ${producto.detalle}
                       <br>   
                       <b>Precio:</b> $${precioFormateado} 
                       - <b>Subtotal:</b> $${subtotalFormateado}
                    </li>
                `;
            });
            productosHTML += '</ul>';
            
            
            let totalConDomicilio = totalPedido; 
if (tipoSolicitud === '50' && data.costo_domicilio) {
 productosHTML += `
                
                 <p><strong>Costo Domicilio:</strong> $${data.costo_domicilio}</p>
                <span id="domicilio-${numero_pedido}" style="display:none;">
                        ${data.costo_domicilio}
                    </span>`;
totalConDomicilio += parseFloat(data.costo_domicilio);
}

// Formatear el total final
const totalFormateado = totalConDomicilio.toLocaleString('es-CO');

// Construir HTML
productosHTML += `
<p><b>Total del pedido:</b> $${totalFormateado}</p>
<br>
`;
            
            
    

            

            // Finalmente, el comentario
            productosHTML += comentarioHTML;

        } else {
            productosHTML = 'No hay productos disponibles.';
        }

        // Insertar en el contenedor
        const productosContainer = document.getElementById(`productos-${numero_pedido}`);
        if (productosContainer) {
            productosContainer.innerHTML = productosHTML;
            console.log("HTML final en productosContainer:", productosContainer.innerHTML);
        } else {
            console.warn("No se encontró el contenedor productos-", numero_pedido);
        }
    })
    .catch(error => console.error('Error al cargar los productos:', error));
}







// Función para cambiar el estado del turno y actualizar el botón dinámicamente
function cambiarEstadoTurnero(numero_pedido, nuevoEstado) {
console.log(`🔄 Cambiando estado del pedido ${numero_pedido} a ${nuevoEstado}...`);

fetch('../controllers/actualizar_estado_turnero.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ numero_pedido, nuevo_estado: nuevoEstado })
})
.then(response => response.json())
.then(data => {
    console.log("📢 Respuesta del servidor:", data);

    if (data.success) {
        // Obtener la fila correspondiente al pedido
        const filaTurno = document.getElementById(`fila-turno-${numero_pedido}`);
        
        if (filaTurno) {
            console.log(`✅ Pedido ${numero_pedido} encontrado en la interfaz. Actualizando estado...`);

            // Actualizar el estado del pedido en la fila
            const estadoTd = filaTurno.querySelector('td:nth-child(4)');
            estadoTd.innerHTML = nuevoEstado + (data.pagado ? '<br>Pagado' : '');

            // Actualizar la clase del color de la fila según el nuevo estado
            if (nuevoEstado === 'en_cocina') {
                filaTurno.className = 'table-primary';
                
                // Llamar a la función de impresión cuando se cambia a "en_cocina"
                console.log(`🖨 Enviando pedido ${numero_pedido} a impresión...`);
                printInvoicepc(numero_pedido);
            } else if (nuevoEstado === 'entregado') {
                filaTurno.className = 'table-success'; // Cambiar a verde cuando está entregado
            }

            // Actualizar los botones de acción
            let botonesAccion = '';

            // Solo mostrar el botón "Entregar" si el estado es "en_cocina"
            if (nuevoEstado === 'en_cocina') {
                botonesAccion += `
                <div class="d-flex justify-content-center">
                    <button id="btn-accion-${numero_pedido}" class="btn btn-primary mb-2 me-2" onclick="cambiarEstadoTurnero(${numero_pedido}, 'entregado')">Entregar</button>
                </div>
                `;
            }

            // El botón de imprimir siempre está presente
            botonesAccion += `
            <div class="d-flex justify-content-center">
                <button class="btn btn-success mb-2 me-2" onclick="printInvoicepc(${numero_pedido})">Imprimir</button>
            </div>`;

            // Agregar botones de despacho según corresponda (desde la función original)
            const tipoSolicitud = document.getElementById('tipoSolicitud').value;
            if (tipoSolicitud == 50) {
                // Verificar si tiene domiciliario (necesitamos obtener esta información)
                // Para simplificar, asumimos que tenemos acceso a esta información desde data
                if (data.tiene_domiciliario) {
                    botonesAccion += `
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-success mb-2 me-2" onclick="mostrarModalTurno(${numero_pedido})">
                            Despachado
                        </button>
                    </div>
                    `;
                } else {
                    botonesAccion += `
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-warning mb-2 me-2" onclick="mostrarModalTurno(${numero_pedido})">
                            Despachar
                        </button>
                    </div>
                    `;
                }
            }

            // Botones para Caja y Editar (siempre presentes a menos que esté pagado)
            if (data.pagado) {
                botonesAccion += `
                    <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                        <input type="hidden" name="numero_pedido" value="${numero_pedido}">
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-success mb-2 me-2">Pagado</button>
                    </div>
                    </form>
                `;
            } else {
                botonesAccion += `
                    <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                        <input type="hidden" name="numero_pedido" value="${numero_pedido}">
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-info me-2 mb-2">Caja</button>
                    </div>
                    </form>
                    <form action="../public/index.php?page=edit_pedido.php" method="POST" style="display:inline;">
                        <input type="hidden" name="numero_pedido" value="${numero_pedido}">
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-warning">Editar</button>
                    </div>
                    </form>
                `;
            }

            // Actualizar el contenido de la columna de acciones
            const accionTd = filaTurno.querySelector(`#accion-${numero_pedido}`);
            accionTd.innerHTML = botonesAccion;
        }
    } else {
        console.error("❌ Error al actualizar el estado:", data.message);
    }
})
.catch(error => console.error('⚠ Error al actualizar el estado del pedido:', error));
}



// Función para calcular el tiempo restante desde la fecha del turno
function calcularTiempoRestante(fechaTurno) {
const ahora = new Date();
const diferencia = ahora - fechaTurno;  // Diferencia en milisegundos

const horas = Math.floor(diferencia / (1000 * 60 * 60));
const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

return `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')}`;
}




document.addEventListener('DOMContentLoaded', () => {
// Obtener el tipo de solicitud desde el campo oculto
let tipoSolicitud = document.getElementById('tipoSolicitud').value;

// Cargar los datos iniciales de las mesas y los turnos
if(tipoSolicitud == 51){
    cargarDatosMesas();
}else{}

cargarDatosTurnos(tipoSolicitud);

// Actualizar solo los cronómetros cada segundo
setInterval(actualizarTemporizadores, 1000);

// Actualizar la tabla completa cada 5 segundos sin eliminar los datos
setInterval(() => {
    tipoSolicitud = document.getElementById('tipoSolicitud').value; // Obtener el valor actualizado de tipoSolicitud
    if(tipoSolicitud == 51){
        cargarDatosMesas();
    }else{}
    cargarDatosTurnos(tipoSolicitud);
}, 5000);
});

//DOMICILIOS

// Función para mostrar el modal con información del turno
function mostrarModalTurno(numero_pedido) {
console.log('Numero de pedido recibido:', numero_pedido);

fetch(`../controllers/obtener_datos_pedidowp.php?id_pedido=${numero_pedido}`)
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta del servidor:', data);  
        const modalContent = document.getElementById('modal-content');
        modalContent.innerHTML = '';  

        if (data && data.cliente) {
            let productosHTML = '';
            let totalPedido = 0;

            // Verificar si el costo de domicilio está registrado
            let domicilioHTML = '';

            if (data.costo_domicilio && parseFloat(data.costo_domicilio) > 0) {
                // Mostrar el costo del domicilio ya registrado y agregar botón para cambiar el costo
                domicilioHTML = `
                    <p><strong>Costo de Domicilio:</strong> $${data.costo_domicilio}</p>
                    <button class="btn btn-warning mt-2" onclick="mostrarInputDomicilio(${numero_pedido})">Cambiar Costo de Domicilio</button>
                    <div id="cambio-domicilio-${numero_pedido}" style="display: none;">
                        <input type="number" class="form-control" id="nuevo_costo_domicilio" placeholder="Ingrese el nuevo costo del domicilio">
                        <button class="btn btn-primary mt-2" onclick="guardarNuevoCostoDomicilio(${numero_pedido})">Guardar</button>
                    </div>
                `;
                totalPedido += parseFloat(data.costo_domicilio);
            } else {
                // Mostrar input para ingresar el costo de domicilio si no está disponible
                domicilioHTML = `
                    <div class="form-group">
                        <label for="costo_domicilio"><strong>Ingresar Costo de Domicilio:</strong></label>
                        <input type="number" class="form-control" id="costo_domicilio" name="costo_domicilio" placeholder="Ingrese el costo del domicilio">
                        <button class="btn btn-primary mt-2" onclick="guardarCostoDomicilio(${numero_pedido})">Guardar</button>
                    </div>
                `;
            }

            modalContent.innerHTML += domicilioHTML;

            let domiciliarioHTML = '';

            // Verificar si hay un domiciliario asignado
            if (data.domiciliario && data.domiciliario.repartidor && data.domiciliario.celu_reparti) {
                // Mostrar domiciliario asignado
                domiciliarioHTML = `
                    <p><strong>Domiciliario Asignado:</strong> ${data.domiciliario.repartidor || 'No disponible'} - ${data.domiciliario.celu_reparti || 'No disponible'}</p>
                    <button class="btn btn-warning mt-2" id="btn-cambiar-domiciliario">Cambiar Domiciliario</button>
                `;

                document.getElementById('modal-content').innerHTML += domiciliarioHTML;

                // Agregar evento de "Cambiar Domiciliario" dinámicamente
                document.getElementById('btn-cambiar-domiciliario').addEventListener('click', function () {
                    limpiarDomiciliario(numero_pedido); // Limpiar el domiciliario
                    console.log('Clic en Cambiar Domiciliario, número pedido:', numero_pedido);
                });
            } else {
                // Si no hay domiciliario asignado, mostrar el select para asignar uno
                mostrarSelectDomiciliario(numero_pedido);
            }

            // Mostrar el modal
            $('#myModal').modal('show');
        } else {
            modalContent.innerHTML = '<p>No se encontraron datos para este pedido.</p>';
            $('#myModal').modal('show');
        }
    })
    .catch(error => console.error('Error al obtener los datos del turno:', error));
}

// Función para mostrar el input para cambiar el costo de domicilio
function mostrarInputDomicilio(numero_pedido) {
const cambioDomicilio = document.getElementById(`cambio-domicilio-${numero_pedido}`);
if (cambioDomicilio) {
    cambioDomicilio.style.display = 'block';
}
}

// Función para guardar el nuevo costo de domicilio
function guardarNuevoCostoDomicilio(numero_pedido) {
const nuevoCostoDomicilio = document.getElementById('nuevo_costo_domicilio').value;

if (!nuevoCostoDomicilio) {
    alert('Por favor, ingresa un costo de domicilio válido.');
    return;
}

fetch('../controllers/guardar_domicilio.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ id_pedido: numero_pedido, precio: nuevoCostoDomicilio })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        alert('Costo de domicilio actualizado exitosamente.');

        // Refrescar la tabla de productos
        cargarProductos(numero_pedido);
    } else {
        alert('Error al actualizar el costo de domicilio: ' + data.message);
    }
})
.catch(error => console.error('Error al actualizar el costo de domicilio:', error));
}

// Función para mostrar el select de domiciliarios
function mostrarSelectDomiciliario(numero_pedido) {
console.log("Mostrando el select de domiciliarios para el pedido:", numero_pedido);
fetch('../controllers/obtener_domiciliario.php')  // Llamada para obtener los domiciliarios
    .then(response => {
        console.log("Respuesta de obtener_domiciliario:", response);
        return response.json();
    })
    .then(domiciliarios => {
        console.log("Domiciliarios obtenidos:", domiciliarios);
        let selectOptions = '';

        if (domiciliarios.status === 'success' && domiciliarios.domiciliarios.length > 0) {
            domiciliarios.domiciliarios.forEach(domiciliario => {
                selectOptions += `<option value="${domiciliario.id_e}">${domiciliario.repartidor}</option>`;
            });

            let domiciliarioHTML = `
                <div class="form-group">
                    <label for="domiciliario_select"><strong>Seleccionar Domiciliario:</strong></label>
                    <select id="domiciliario_select" class="form-control">
                        <option value="">Seleccione un domiciliario</option>
                        ${selectOptions}
                    </select>
                    <button class="btn btn-primary mt-2" onclick="asignarDomiciliario(${numero_pedido})">Seleccionar</button>
                </div>
            `;

            document.getElementById('modal-content').innerHTML += domiciliarioHTML;
        } else {
            console.error("No hay domiciliarios disponibles.");
            document.getElementById('modal-content').innerHTML += '<p>No hay domiciliarios disponibles.</p>';
        }
    })
    .catch(error => {
        console.error('Error al obtener los domiciliarios:', error);
        document.getElementById('modal-content').innerHTML += '<p>Error al cargar los domiciliarios.</p>';
    });
}

// Función para limpiar el domiciliario actual (dejarlo en blanco)
function limpiarDomiciliario(numero_pedido) {
console.log("Llamando a limpiarDomiciliario para el pedido:", numero_pedido);
fetch('../controllers/limpiar_domiciliario.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ numero_pedido })
})
.then(response => response.json())
.then(data => {
    console.log("Respuesta de limpiarDomiciliario:", data);
    if (data.status === 'success') {
        console.log("Domiciliario limpiado con éxito.");
        mostrarSelectDomiciliario(numero_pedido);  // Mostrar el select de domiciliarios después de limpiar
    } else {
        console.error("Error al limpiar el domiciliario:", data.message);
        alert('Error al limpiar el domiciliario.');
    }
})
.catch(error => console.error('Error al limpiar el domiciliario:', error));
}

function guardarCostoDomicilio(numero_pedido) {
const costo_domicilio = document.getElementById('costo_domicilio').value;

if (!costo_domicilio) {
    alert('Por favor, ingresa un costo de domicilio válido.');
    return;
}

fetch('../controllers/guardar_domicilio.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ id_pedido: numero_pedido, precio: costo_domicilio })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        alert('Costo de domicilio guardado exitosamente.');

        // Refrescar la tabla de productos
        cargarProductos(numero_pedido);

        // Cerrar el modal
        $('#myModal').modal('hide');
    } else {
        alert('Error al guardar el costo de domicilio: ' + data.message);
    }
})
.catch(error => console.error('Error al guardar el costo de domicilio:', error));
}

function enviarWhatsApp(numero_pedido, costo_domicilio) {
// Datos del cliente que podrías obtener desde el backend
const celularCliente = '+573174742056';  // Reemplaza con el número de celular del cliente
const mensaje = `El costo de domicilio  es de $${costo_domicilio}`;

// Formatear el número de celular y el mensaje para la URL de WhatsApp
const celularClienteFormateado = celularCliente.replace(/\s+/g, ''); // Asegurarse de que no haya espacios
const url = `https://wa.me/${celularClienteFormateado}?text=${encodeURIComponent(mensaje)}`;

// Abrir la ventana de WhatsApp
window.open(url, '_blank');
}







function asignarDomiciliario(numero_pedido) {
const domiciliarioId = document.getElementById('domiciliario_select').value;

fetch('../controllers/asignar_domiciliario.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        pedido: numero_pedido, // Cambié de id_pedido a pedido
        repartidor: domiciliarioId // Cambié de id_domi a repartidor
    })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {  // Cambié de data.success a data.status
        alert('Domiciliario asignado correctamente.');
        cargarProductos(numero_pedido);
        //cargarDatosTurnos();  // Recargar los turnos para reflejar el cambio
        $('#myModal').modal('hide');  // Cerrar el modal
    } else {
        alert('Error al asignar el domiciliario.');
    }
})
.catch(error => console.error('Error al asignar el domiciliario:', error));
}




//IMPRIMIR TABLETAS Y TELEFONOS
function printInvoice(numeroPedido) {
console.log("Iniciando impresión con RawBT...");

// Selecciona la fila del turno que corresponde al número de pedido
const filaTurno = document.getElementById(`fila-turno-${numeroPedido}`);

if (!filaTurno) {
    console.error("No se encontró la fila del turno para el número de pedido:", numeroPedido);
    return;
}

// Obtener los datos del cliente, productos y otros campos directamente de la fila
const turno = filaTurno.children[0].innerText.trim(); 
const cliente = filaTurno.children[1].innerText.trim() || 'Desconocido';
const estado = filaTurno.children[3].innerText.trim();
const productos = filaTurno.children[4].innerText.trim(); // Aquí tienes los productos

// Generar el contenido de la impresión
let contenido = `Restaurante Heiyubai\n`;
contenido += `TURNO N°: ${turno}\n`;
contenido += `Pedido N°: ${numeroPedido}\n`;
contenido += `Cliente: ${cliente}\n`;
contenido += `Estado: ${estado}\n`;
contenido += `----------------------------------------\n`;
contenido += `Productos:\n`;
contenido += `${productos}\n`;
contenido += `----------------------------------------\n`;

// Mostrar el contenido de la impresión en la consola
console.log("Contenido a imprimir:", contenido);

// Agregar el comando para cortar el papel
contenido += `\x1D\x56\x42\x00`;  // Comando ESC/POS para cortar el papel

// Codificar el contenido de la impresión
const encodedContent = encodeURIComponent(contenido);

// Crear la URL para RawBT
const rawbtURL = `rawbt://print?data=${encodedContent}`;

// Abrir la URL para iniciar automáticamente la impresión
window.open(rawbtURL, '_system');  // Usamos '_system' para abrir la app de RawBT directamente
}


//IMPRIMIR PC

function findElementWithText(tag, text) {
const elements = document.querySelectorAll(tag);
for (let element of elements) {
    if (element.innerText.includes(text)) {
        return element;
    }
}
return null;
}


function printInvoicemesa(numeroPedido) {
console.log("🖨️ Iniciando impresión con QZ Tray...");

// 1) Obtener <div id="fila-turno-...">
const filaTurno = document.getElementById(`fila-turno-${numeroPedido}`);
if (!filaTurno) {
    console.error("⚠ No se encontró la fila del turno:", numeroPedido);
    return;
}

// 2) Capturar el estado (aquí lo tomamos del <p><strong>Estado:</strong> ...)
let estadoP = filaTurno.querySelector('p:nth-of-type(3)');
let estado = estadoP ? estadoP.innerText.replace('Estado:', '').trim() : 'Sin estado';

 let mesaP = filaTurno.querySelector('p:nth-of-type(1)'); 
let mesa = mesaP 
  ? mesaP.innerText.replace('Mesa Actual:', '').trim() 
  : 'No asignada';

// 3) Capturar los comentarios
const comentariosContainer = document.getElementById(`comentarios-${numeroPedido}`);
let comentarios = [];

if (comentariosContainer) {
    // Si es un <ul>, obtiene todos los <li>. 
    // Si era <p>, solo tomamos su innerText.
    if (comentariosContainer.tagName.toLowerCase() === 'ul') {
        comentariosContainer.querySelectorAll('li').forEach(li => {
            comentarios.push(li.innerText.trim());
        });
    } else {
        // Por ejemplo, si no hay comentarios y mostramos <p>Sin comentarios</p>
        comentarios.push(comentariosContainer.innerText.trim());
    }
}

// 4) Capturar los productos (tabla <tbody> ...)
const productosContainer = document.getElementById(`productos-${numeroPedido}`);
if (!productosContainer) {
    console.error("⚠ No se encontró el contenedor de productos:", numeroPedido);
    return;
}

let productos = [];
let totalFinal = 0;

// Hallar las filas <tr> en la tabla
const filas = productosContainer.querySelectorAll("table tbody tr");

filas.forEach((tr) => {
    const celdas = tr.querySelectorAll("td");
    // Orden: 0=Producto, 1=Cantidad, 2=Detalle, 3=Tipo, 4=Precio, 5=Subtotal
    const nombre = celdas[0].innerText.trim();
    const cantidad = parseFloat(celdas[1].innerText.trim()) || 0;
    const detalle = celdas[2].innerText.trim();
    const tipoProducto = celdas[3].innerText.trim();

    // Quitar símbolos/puntos para parsear precio y subtotal
    const precioRaw = celdas[4].innerText.replace(/[^\d,\.]/g, "");
    const subtotalRaw = celdas[5].innerText.replace(/[^\d,\.]/g, "");

    const precio = parseFloat(precioRaw.replace(/\./g, '').replace(',', '.')) || 0;
    const subtotal = parseFloat(subtotalRaw.replace(/\./g, '').replace(',', '.')) || 0;

    totalFinal += subtotal;

    productos.push({ nombre, cantidad, detalle, tipoProducto, precio, subtotal });
});

// (Opcional) Variables extra
let turno = 0;    // Ajusta según tu necesidad
let cliente = ''; // Ajusta/elimina si no la usas
let tiempo = '';  // Ajusta/elimina si no la usas

// 5) Construir el texto ESC/POS
let contenido = "\x1B\x40"; // Reset impresora

// Encabezado
contenido += "\x1B\x61\x01\x1B\x21\x30Restaurante HEIYUBAI\x1B\x21\x00\n";
contenido += "------------------------------------------\n";
contenido += `\x1B\x21\x20TURNO N°: ${turno}\x1B\x21\x00\n`;
contenido += `Pedido N°: ${numeroPedido}\n`;
contenido += `Cliente: ${cliente}\n`;
contenido += `Tiempo: ${tiempo}\n`;
contenido += `Estado: ${estado}\n`;
contenido += `Mesa: ${mesa}\n`;
contenido += "------------------------------------------\n";

// (Opcional) Incluir comentarios en el ticket
if (comentarios.length > 0) {
    contenido += "Comentarios:\n";
    comentarios.forEach((com) => {
        contenido += `  - ${com}\n`;
    });
    contenido += "------------------------------------------\n";
}

// Cabecera de productos en el ticket
contenido += "Cant  Tipo       Producto               Detalle\n";
contenido += "------------------------------------------------\n";

productos.forEach(({ cantidad, tipoProducto, nombre, detalle }) => {
    contenido += `${String(cantidad).padEnd(4)} ${tipoProducto.padEnd(10)} ${nombre.padEnd(20)} ${detalle}\n`;
});

contenido += "------------------------------------------\n";
contenido += `\x1B\x21\x20TOTAL: $${totalFinal.toFixed(0)}\x1B\x21\x00\n`;
contenido += "==========================================\n";

// Mensaje de agradecimiento
contenido += "\x1B\x61\x01\x1B\x21\x20¡Gracias por su compra!\x1B\x21\x00\n";

// Corte de papel y apertura de cajón
contenido += "\n\n\n\n";
contenido += "\x1D\x56\x00"; // Corte parcial
contenido += "\x1B\x70\x00\x19\xFA"; // Abrir cajón

console.log("✅ Contenido a imprimir:\n", contenido);

// 6) Imprimir con QZ Tray
ensureConnection().then(() => {
    return qz.printers.getDefault();
}).then((printer) => {
    if (!printer) {
        console.error("⚠ No se encontró una impresora predeterminada.");
        return;
    }

    const config = qz.configs.create(printer);
    const printData = [{ type: 'raw', format: 'plain', data: contenido }];

    return qz.print(config, printData).then(() => {
        console.log("✅ Impresión completada.");
    });
}).catch((error) => {
    console.error('❌ Error al imprimir con QZ Tray:', error);
});
}






function printInvoicepc(numeroPedido) {
console.log("🖨️ Iniciando impresión con QZ Tray...");

const filaTurno = document.getElementById(`fila-turno-${numeroPedido}`);
if (!filaTurno) {
    console.error("⚠ No se encontró la fila del turno:", numeroPedido);
    return;
}

// 🔹 Obtener datos del pedido
const turno = filaTurno.children[0]?.innerText.trim() || "N/A";
const cliente = filaTurno.children[1]?.innerText.trim() || "Desconocido";
const tiempo = filaTurno.children[2]?.innerText.trim() || "N/A";
const estado = filaTurno.children[3]?.innerText.trim() || "Sin estado";

// 🔹 Contenedor con <li> de productos
const productosContainer = document.getElementById(`productos-${numeroPedido}`);
if (!productosContainer) {
    console.error("⚠ No se encontró el contenedor de productos:", numeroPedido);
    return;
}

let productos = [];
let totalFinal = 0;


// 🔹 Extraer productos
productosContainer.querySelectorAll("li").forEach((item) => {
    const texto = item.innerText.trim();

    // Ver qué texto exacto se está leyendo
    console.log("texto original:", texto);

    // Separar en subpartes por " - "
    const partes = texto.split(" - ");
    console.log("partes:", partes);

    // Debemos tener al menos 4 partes: [0]=cantidad, [1]=tipo, [2]="Precio: ...", [3]="Subtotal: ..." 
    if (partes.length >= 4) {
        let cantidad     = partes[0]?.replace(/\D/g, "") || "0";  // Solo dígitos
        let tipoProducto = partes[1]?.trim() || "Sin tipo";
        let nombre       = partes[2]?.trim() || "Sin nombre";     // En tu caso, podría ser "Price: $..."
        let detalle      = partes[3]?.trim() || "Sin detalle";    // Podría ser "Subtotal: $..."

        // 🛠 Extraer precios usando regex flexible: (Precio|Price)
        let precioTexto   = texto.match(/(?:Precio|Price):\s*\$([\d,.]+)/);
        let subtotalTexto = texto.match(/Subtotal:\s*\$([\d,.]+)/);

        let precio = precioTexto 
            ? parseFloat(precioTexto[1].replace(/\./g, "").replace(",", ".")) 
            : 0;
        let subtotal = subtotalTexto 
            ? parseFloat(subtotalTexto[1].replace(/\./g, "").replace(",", ".")) 
            : 0;

        totalFinal += subtotal;

        productos.push({ 
            cantidad, 
            tipoProducto, 
            nombre,    // Esto tomará lo que esté en partes[2]
            detalle,   // Esto tomará lo que esté en partes[3]
            precio, 
            subtotal 
        });
    } else {
        // Si las partes no llegan a 4, se muestra la advertencia
        console.warn(`⚠ Producto con formato inesperado: ${texto}`);
    }
});

// 🔹 Extraer comentario
let comentarios = [];
const comentariosEl = document.getElementById(`comentarios-${numeroPedido}`);
if (comentariosEl) {
    if (comentariosEl.tagName.toLowerCase() === 'p') {
        let textoComentario = comentariosEl.innerText;
        comentarios.push(textoComentario.trim());
    }
}

let costoDomicilio = 0;
console.log('Domicilio costo', costoDomicilio)

    // Buscar el <span id="domicilio-...">
    const domicilioEl = document.getElementById(`domicilio-${numeroPedido}`);
    if (domicilioEl) {
        // Convertir a número
        costoDomicilio = parseFloat(
            domicilioEl.innerText.trim().replace(/\./g, '').replace(',', '.')
        ) || 0;

        // Sumar al totalFinal (solo si así lo deseas)
        // totalFinal += costoDomicilio;
    }



// 🔹 Construir texto ESC/POS
let contenido = "\x1B\x40"; // Reset impresora

// Encabezado
contenido += "\x1B\x61\x01\x1B\x21\x30Restaurante HEIYUBAI\x1B\x21\x00\n";
contenido += "------------------------------------------\n";
contenido += `\x1B\x21\x20TURNO N°: ${turno}\x1B\x21\x00\n`;
contenido += `Pedido N°: ${numeroPedido}\n`;
contenido += `\x1B\x21\x30${cliente}\x1B\x21\x00\n`;
contenido += `Tiempo: ${tiempo}\n`;
contenido += `Estado: ${estado}\n`;
contenido += "------------------------------------------\n";

// Comentario (si existe)
if (comentarios.length > 0) {
    contenido += "Comentario:\n";
    comentarios.forEach((com) => {
        contenido += `  ${com}\n`;
    });
    contenido += "------------------------------------------\n";
}

// Cabecera productos
contenido += "------------------------------------------------------\n";
productos.forEach(({ cantidad, tipoProducto, nombre, detalle }) => {
    // Ajusta la longitud de campos como gustes
    contenido += `${cantidad.toString().padStart(3)} ${tipoProducto.padEnd(10)} ${nombre.padEnd(30)} ${detalle}\n`;
});

// Total
contenido += "------------------------------------------\n";
contenido += `\x1B\x21\x20TOTAL PRODUCTOS: $${totalFinal.toFixed(0)}\x1B\x21\x00\n`;

// Imprimir costo de domicilio
if (costoDomicilio > 0) {
    contenido += `Costo Domicilio: $${costoDomicilio.toFixed(0)}\n`;
    let totalConDomicilio = totalFinal + costoDomicilio;
    contenido += `------------------------------------------\n`;
    contenido += `\x1B\x21\x20TOTAL A PAGAR: $${totalConDomicilio.toFixed(0)}\x1B\x21\x00\n`;
} else {
    // Sino, dejamos el totalFinal normal
    contenido += "==========================================\n";
}

// Mensaje final
contenido += "\x1B\x61\x01\x1B\x21\x20¡Gracias por su compra!\x1B\x21\x00\n";

// Corte de papel + cajón
contenido += "\n\n\n\n";
contenido += "\x1D\x56\x00"; // Corte
contenido += "\x1B\x70\x00\x19\xFA"; // Abrir cajón

// Ver el resultado en consola antes de imprimir
console.log("✅ Contenido a imprimir:\n", contenido);

// 🔹 Imprimir con QZ Tray
ensureConnection().then(() => {
    return qz.printers.getDefault();
}).then((printer) => {
    if (!printer) {
        console.error("⚠ No se encontró una impresora predeterminada.");
        return;
    }

    const config = qz.configs.create(printer);
    const printData = [{ type: 'raw', format: 'plain', data: contenido }];

    return qz.print(config, printData).then(() => {
        console.log("✅ Impresión completada.");
    });
}).catch((error) => {
    console.error('❌ Error al imprimir con QZ Tray:', error);
});
}

// ✅ **Función para conectar a QZ Tray**
function ensureConnection() {
return qz.websocket.connect({ host: 'localhost', secure: false }).then(() => {
    console.log("✅ Conectado a QZ Tray.");
}).catch(err => {
    console.error("❌ Error al conectar a QZ Tray:", err);
    alert("No se pudo conectar a QZ Tray. Asegúrate de que la aplicación esté abierta.");
});
}









// Deshabilitar la validación de certificados
qz.security.setCertificatePromise((resolve, reject) => {
resolve(); // Aceptar cualquier certificado sin validación
});

// Deshabilitar la firma digital
qz.security.setSignaturePromise((toSign) => {
return (resolve, reject) => {
    resolve(); // No requiere firma
};
});





// 🔹 Configurar el certificado personalizado
qz.security.setCertificatePromise((resolve, reject) => {
fetch('../qz-cert.pem')
    .then(response => response.text())
    .then(data => resolve(data))
    .catch(err => reject('❌ Error al cargar el certificado: ' + err));
});

// 🔹 Configurar la firma digital usando la clave privada correcta
qz.security.setSignaturePromise((toSign) => {
return function(resolve, reject) {
    fetch('../qz-key.pem')
        .then(response => response.text())
        .then(pk => {
            if (!pk.includes('-----BEGIN PRIVATE KEY-----') || !pk.includes('-----END PRIVATE KEY-----')) {
                reject('❌ Error: Clave privada incorrecta.');
                return;
            }

            var sig = new KJUR.crypto.Signature({ "alg": "SHA1withRSA" });
            sig.init(pk);
            sig.updateString(toSign);
            var sign = sig.sign();
            resolve(sign);
        })
        .catch(err => reject('❌ Error al cargar la clave privada: ' + err));
};
});