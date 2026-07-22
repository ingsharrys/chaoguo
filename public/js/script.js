/* ═════════════ CONFIG ═════════════ */
const REFRESH_MS = 15000;                 // intervalo de refresco (poll)
const MESAS_URL  = '../controllers/obtener_datos.php';
const TURNOS_URL = '../controllers/obtener_datos_turnos.php';

/* ═════════════ ESTADO ═════════════ */
let hashMesas  = '';
let hashTurnos = '';

/* ═════════════ HELPERS ═════════════ */
const $    = s => document.querySelector(s);
const $$   = s => document.querySelectorAll(s);
const toJSON = r => { if (!r.ok) throw Error('HTTP '+r.status); return r.json(); };
const hash = (arr, keys) =>
  Array.isArray(arr) ? arr.map(o => keys.map(k => o[k]).join('-')).join('|') : '';
const pad2 = n => n.toString().padStart(2,'0');
const ago  = date => {
  const s = ((Date.now() - date.getTime()) / 1e3) | 0;
  return `${pad2(s/3600|0)}:${pad2((s/60|0)%60)}:${pad2(s%60)}`;
};
const tipoSol = () => $('#tipoSolicitud')?.value || '51';

/* ═════════════ MESAS (igual que antes) ═════════════ */
async function renderMesas() {
  const wrap = $('#mesas-container'); if (!wrap) return;
  const d    = await fetch(MESAS_URL).then(toJSON).catch(console.error);
  if (!d?.mesas) return;

  const h = hash(d.mesas, ['numero_mesa','estado','pagado']);
  if (h === hashMesas) return;   // sin cambios
  hashMesas = h;  wrap.innerHTML = '';

  d.mesas.sort((a,b) => a.numero_mesa - b.numero_mesa).forEach(m => {
    const col =
      m.estado === 'nuevo'                     ? 'warning' :
      m.estado === 'en_cocina'                 ? 'primary' :
      m.estado === 'entregado' && !m.pagado    ? 'success' : 'secondary';

    const label = (!m.id_pedido && !m.estado)
      ? `Mesa ${m.numero_mesa}`
      : `Mesa ${m.numero_mesa}<br>${m.estado || 'Sin estado'}<br>${m.pagado ? 'Pagado' : 'Por pagar'}`;

    wrap.insertAdjacentHTML('beforeend', `
      <div class="col-md-4 mb-2">
        <button class="btn btn-${col} w-100" 
                onclick="procesarMesa(${m.id_pedido}, '${m.estado}', ${m.numero_mesa}, ${m.pagado})">
          ${label}
        </button>
      </div>`);
  });
}

/* ═════════════ TURNOS (celda Cliente mejorada) ═════════════ */
let lastUpdateTurnos = 0;

async function renderTurnos() {
  const cont = $('#turnos-container'); if (!cont) return;

  if (!$('#tabla-turnos')) {
    cont.innerHTML = `<table class="table table-bordered" id="tabla-turnos">
      <thead>
        <tr><th>N°</th><th>Cliente</th><th>Tiempo</th><th>Estado</th>
        <th>Productos</th><th>Acción</th></tr>
      </thead>
      <tbody id="tbd"></tbody>
    </table>`;
  }

  const tbody = $('#tbd');
  const d = await fetch(`${TURNOS_URL}?tipo_solicitud=${tipoSol()}&since=${lastUpdateTurnos}`)
                  .then(toJSON).catch(console.error);
  if (!d?.turnos?.length) return;

  lastUpdateTurnos = Date.now();

  d.turnos.forEach(t => {
    if (t.estado === 'entregado' && t.pagado) return;

    const rowClass =
      t.estado === 'nuevo'     ? 'warning' :
      t.estado === 'en_cocina' ? 'primary' :
      t.estado === 'entregado' ? 'success' : 'secondary';

    const fecha = new Date(t.fecha);
    const clienteHTML = (tipoSol() === '50')
      ? `<strong>${t.cliente}</strong><br>
         Dirección: ${t.direccion || '—'}<br>
         Barrio: ${t.barrio || '—'}<br>
         <a href="https://api.whatsapp.com/send?phone=57${t.telefono}&text=Hola" target="_blank">
           ${t.telefono || '—'}
         </a><br>
         Fecha y Hora: ${fecha.toLocaleString('es-CO')}`
      : t.cliente;

    const existingRow = document.getElementById(`fila-${t.numero_pedido}`);
    
    if (existingRow) {
      existingRow.className = `table-${rowClass}`;
      existingRow.dataset.fecha = fecha.toISOString();
      existingRow.querySelector(`#tmp-${t.numero_pedido}`).textContent = ago(fecha);
      existingRow.querySelector(`#est-${t.numero_pedido}`).innerHTML = `${t.estado}${t.pagado ? '<br>Pagado' : ''}`;
      if (!prodCache.has(t.numero_pedido)) {
    cargarProductos(t.numero_pedido);
}

      pintarAcciones(t); // actualizar botones
    } else {
      tbody.insertAdjacentHTML('beforeend', `
        <tr id="fila-${t.numero_pedido}" class="table-${rowClass}" data-fecha="${fecha.toISOString()}">
          <td><h1>${t.turno}</h1></td>
          <td style="font-size:${tipoSol()==='50'?'9pt':'inherit'};white-space:normal;">${clienteHTML}</td>
          <td id="tmp-${t.numero_pedido}">${ago(fecha)}</td>
          <td id="est-${t.numero_pedido}">${t.estado}${t.pagado ? '<br>Pagado' : ''}</td>
          <td id="prod-${t.numero_pedido}">Cargando…</td>
          <td id="acc-${t.numero_pedido}"></td>
        </tr>`);
      pintarAcciones(t);
      cargarProductos(t.numero_pedido);
    }
  });
}


/* ═════ Botones de acción (igual que tu versión funcional) ═════ */
function pintarAcciones(t){
  const td = document.querySelector(`#acc-${t.numero_pedido}`); if (!td) return;
  let html = '';

  /* Cocina / Entregar */
  if (t.estado !== 'entregado') {
    const next  = (t.estado === 'nuevo' ? 'en_cocina' : 'entregado');
    const color = (t.estado === 'nuevo' ? 'warning'  : 'primary');
    html += `
      <button class="btn btn-${color} mb-2 me-2"
              onclick="cambiarEstadoTurnero(${t.numero_pedido}, '${next}')">
        ${next === 'en_cocina' ? 'Cocina' : 'Entregar'}
      </button>`;
  }

  /* Imprimir */
  html += `
    <button class="btn btn-success mb-2 me-2"
            onclick="printInvoicepc(${t.numero_pedido})">Imprimir</button>`;


  /* Despachar / Despachado solo para tipo 50 */
if (tipoSol() === '50') {
  const estado  = t.tiene_domiciliario ? 'success' : 'warning';   // color
  const etiqueta= t.tiene_domiciliario ? 'Despachado' : 'Despachar';

  /* ⬇️  ID único  */
  html += `
    <button id="btn-desp-${t.numero_pedido}"
            class="btn btn-${estado} mb-2 me-2"
            onclick="mostrarModalTurno(${t.numero_pedido})">
      ${etiqueta}
    </button>`;
}


  /* Caja / Pagado + Editar */
  if (t.pagado) {
    html += `
      <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
        <input type="hidden" name="numero_pedido" value="${t.numero_pedido}">
        <button class="btn btn-success mb-2 me-2">Pagado</button>
      </form>`;
  } else {
    html += `
      <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
        <input type="hidden" name="numero_pedido" value="${t.numero_pedido}">
        <button class="btn btn-info mb-2 me-2">Caja</button>
      </form>
      <form action="../public/index.php?page=edit_pedido.php" method="POST" style="display:inline;">
        <input type="hidden" name="numero_pedido" value="${t.numero_pedido}">
        <button class="btn btn-warning mb-2">Editar</button>
      </form>`;
  }

  td.innerHTML = html;
}

/* ═════ Productos (lazy load) ═════ */
const prodCache = new Set();

function cargarProductos(id) {
    prodCache.delete(id); // Permitir recarga tras cambios
    if (prodCache.has(id)) return;
    prodCache.add(id);

    fetch(`../controllers/obtener_datos_pedido.php?id_pedido=${id}`)
        .then(toJSON)
        .then(d => {
            const cell = document.querySelector(`#prod-${id}`);
            if (!cell) return;

            if (!d?.productos?.length) {
                cell.textContent = 'Sin productos';
                return;
            }

            let total = 0;

            // Mostrar productos y calcular total
            const productosHTML = d.productos.map(p => {
                const subtotal = parseFloat(p.precio) * parseInt(p.cantidad);
                total += subtotal;

                return `${p.cantidad}x [${p.tipo_prod || '—'}] ${p.nombre_producto.slice(0, 40)}<br>
                    <small class="text-muted">${p.detalle || ''}</small> – $${subtotal.toLocaleString('es-CO')}`;
            }).join('<br>');

            // Mostrar información adicional según tipo de solicitud
            let adicionalesHTML = '';
            const tipo = tipoSol();

            if (tipo === '50') {
                let costoDomicilio = parseFloat(d.costo_domicilio || 0);
                total += costoDomicilio;

                adicionalesHTML += `
                    <hr>
                    <strong>Costo Domicilio:</strong> $${costoDomicilio.toLocaleString('es-CO')}<br>
                `;
            }

            adicionalesHTML += `
                <strong>Total del pedido:</strong> $${total.toLocaleString('es-CO')}<br>
                <strong>Comentario:</strong> ${d.comentario?.trim() || 'No disponible'}
            `;

            cell.innerHTML = productosHTML + adicionalesHTML;
        })
        .catch(error => {
            console.error('❌ Error cargando productos:', error);
            const cell = document.querySelector(`#prod-${id}`);
            if (cell) cell.textContent = 'Error al cargar productos';
        });
}





/* ═════ Refresco cíclico + cronómetros ═════ */
function mainLoop(){
  if (tipoSol() === '51') renderMesas();
  renderTurnos();
}
document.addEventListener('DOMContentLoaded',()=>{
  mainLoop(); setInterval(mainLoop, REFRESH_MS);
  setInterval(() => $$('#tabla-turnos [id^="tmp-"]').forEach(el=>{
    const fecha = new Date(el.closest('tr').dataset.fecha);
    el.textContent = ago(fecha);
  }), 1_000);
});

/* ═════ Mantén globales tus demás funciones originales ═════ */


function procesarMesa(id_pedido, estado, numero_mesa, pagado) {
    if (!id_pedido) {
        alert(`La mesa ${numero_mesa} no tiene pedido asignado.`);
        return;
    }

   if (estado === 'entregado' && pagado) {
            if (confirm('¿Deseas liberar esta mesa?')) {
                // Enviar solicitud para liberar la mesa
                fetch('../controllers/liberar_mesa.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', },
                    body: JSON.stringify({ numero_mesa: numero_mesa })

                    
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
            mostrarModal(id_pedido, estado, pagado);

        } 
        // Para cualquier otro estado, también abrir el modal
        else {
            mostrarModal(id_pedido, estado, pagado);

        }
}
    // -------------------------------------------------------------------
    //mostrar Modal de La Mesa
    // --------------------------------------------------------------------

    function mostrarModal(id_pedido, estado, pagado) {
    // Llamar al backend para obtener datos del pedido
    fetch(`../controllers/obtener_datos.php?numero_pedido=${id_pedido}`)
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
                <div id="productos-${id_pedido}">

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
                    <ul id="comentarios-${id_pedido}">
                `;
                data.comentarios.forEach(com => {
                    comentariosHTML += `<li>${com}</li>`;
                });
                comentariosHTML += `</ul>`;
            } else {
                comentariosHTML = `<p id="comentarios-${id_pedido}">Sin comentarios</p>`;
            }

            // Botones dinámicos según el estado del pedido
            let botonesEstadoHTML = '';
            if (estado === 'nuevo') {
                botonesEstadoHTML = `
                    <button type="button" class="btn btn-primary" 
                            onclick="cambiarEstadoMesa(${id_pedido}, 'en_cocina')">Mandar a Cocina</button>`;
            } else if (estado === 'en_cocina') {
                botonesEstadoHTML = `
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-warning mb-2 me-2" 
                            onclick="cambiarEstadoMesa(${id_pedido}, 'entregado')">Entregar</button>
                </div>
                `;
            } 

            // Insertar todo en el modal
            modalContent.innerHTML = `
                <div id="fila-turno-${id_pedido}">
                    <p><strong>Mesa Actual:</strong> ${data.numero_mesa || 'No asignada'}</p>
                    <p><strong>Mesero:</strong> ${data.nombre_mesero || 'No asignado'}</p>
                 
                    <label for="nueva_mesa">Cambiar Mesa:</label>
                <select id="nueva_mesa" class="form-control">
                    <option value="">Seleccionar mesa</option>
                    ${mesasHTML}
                </select>
                <button type="button" class="btn btn-primary mt-3" onclick="cambiarMesa(${id_pedido}, ${data.numero_mesa})">Cambiar Mesa</button>
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
                    <button class="btn btn-success mb-2 me-2" onclick="printInvoicemesa(${id_pedido})">
                        Imprimir
                    </button>
                </div>
                    
                    <form action="../public/index.php?page=edit_pedido.php" method="POST" id="form-editar" style="display:inline;">
                      <input type="hidden" name="numero_pedido" id="numero_pedido_editar" value="${id_pedido}">
                    <div class="d-flex justify-content-center"> 
                      <button type="submit" class="btn btn-warning mb-2 me-2" id="boton-editar">Editar</button>
                    </div>
                    </form>
                    
                    <form action="../public/index.php?page=caja_tm.php" method="POST" id="form-pagar" style="display:inline;">
                      <input type="hidden" name="numero_pedido" id="numero_pedido" value="${id_pedido}">   
                      <button type="submit" class="btn btn-danger" id="boton-editar">Pagar</button>
                      
                    </form>
                </div>
            `;

            // Mostrar el modal
            if (myModal) myModal.show();

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
            const filaTurno = document.getElementById(`fila-${numero_pedido}`);

            
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


    function openPopupWindow(form) {
        const url = new URL(form.action);
        const params = new URLSearchParams(new FormData(form));
        url.search = params.toString();
        window.open(url, 'Registrar Pedido', 'width=400,height=600');
    }
function mostrarModalTurno(numero_pedido){
  console.log('Numero de pedido recibido:', numero_pedido);
  fetch(`../controllers/obtener_datos_pedidowp.php?id_pedido=${numero_pedido}`)
    .then(toJSON)
    .then(data=>{
      console.log('Respuesta del servidor:', data);
      const modalContent = document.getElementById('modal-content');
      modalContent.innerHTML=''; 

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
                if (myModal) myModal.show();


            } else {
                modalContent.innerHTML = '<p>No se encontraron datos para este pedido.</p>';
                if (myModal) myModal.show();


            }
        })
        .catch(error => console.error('Error al obtener los datos del turno:', error));
}

let myModal; // Declaración global

document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('myModal');
  if (modalEl) {
    myModal = new bootstrap.Modal(modalEl);
  }

  // Tu lógica de inicio
  mainLoop();
  setInterval(mainLoop, REFRESH_MS);
  setInterval(() => {
    $$('#tabla-turnos [id^="tmp-"]').forEach(el => {
      const fecha = new Date(el.closest('tr').dataset.fecha);
      el.textContent = ago(fecha);
    });
  }, 1000);
});


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
            myModal.hide();

        } else {
            alert('Error al guardar el costo de domicilio: ' + data.message);
        }
    })
    .catch(error => console.error('Error al guardar el costo de domicilio:', error));
}

function asignarDomiciliario(numero_pedido){
  const idDom = document.getElementById('domiciliario_select').value;

  fetch('../controllers/asignar_domiciliario.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({ pedido:numero_pedido, repartidor:idDom })
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.status==='success'){
      alert('Domiciliario asignado correctamente.');
      cargarProductos(numero_pedido);          // refresca totales

      /* 🎯 Cambiar SOLO el botón de esta fila */
      const btn = document.getElementById(`btn-desp-${numero_pedido}`);
      if(btn){
        btn.classList.remove('btn-warning');
        btn.classList.add   ('btn-success');
        btn.textContent = 'Despachado';
        btn.disabled    = false;                 // opcional: evita más clics
      }

      if(myModal) myModal.hide();
    }else{
      alert('Error al asignar el domiciliario.');
    }
  })
  .catch(err=>console.error('Error al asignar el domiciliario:',err));
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
    let contenido = `Restaurante Chao Guo\n`;
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
        const fechaISO   = filaTurno.dataset.fecha;                 // viene de renderTurnos()
  const fechaHora  = new Date(fechaISO).toLocaleString('es-CO');
        

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
    contenido += "\x1B\x61\x01\x1B\x21\x30Restaurante CHAO GUO\x1B\x21\x00\n";
    contenido += "------------------------------------------\n";
    contenido += `\x1B\x21\x20TURNO N°: ${turno}\x1B\x21\x00\n`;
    contenido += `Pedido N°: ${numeroPedido}\n`;
    contenido += `Cliente: ${cliente}\n`;
    contenido += `Fecha y Hora: ${fechaHora}\n`;
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

    const filaTurno = document.getElementById(`fila-${numeroPedido}`);
    if (!filaTurno) {
        console.error("⚠ No se encontró la fila del turno:", numeroPedido);
        return;
    }

    const turno    = filaTurno.children[0]?.innerText.trim() || 'N/A';
    const cliente  = filaTurno.children[1]?.innerText.trim() || 'N/A';
    const tiempo   = filaTurno.children[2]?.innerText.trim() || 'N/A';
    const estado   = filaTurno.children[3]?.innerText.trim() || 'N/A';
    
    const fechaISO   = filaTurno.dataset.fecha;                 // viene de renderTurnos()
  const fechaHora  = new Date(fechaISO).toLocaleString('es-CO');

    let productosContainer = document.getElementById(`productos-${numeroPedido}`);
    if (!productosContainer) {
        console.warn(`⚠ productos-${numeroPedido} no encontrado. Probando con prod-${numeroPedido}`);
        productosContainer = document.getElementById(`prod-${numeroPedido}`);
    }

    if (!productosContainer) {
        console.error("❌ No se pudo encontrar ningún contenedor de productos.");
        return;
    }

    // Extraer productos y formatear en lista
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = productosContainer.innerHTML;

    const productosTexto = [];

    // Detectar líneas que tienen productos (usamos <br> u otras etiquetas)
    tempDiv.innerHTML.split('<br>').forEach(lineaHtml => {
        const temp = document.createElement('div');
        temp.innerHTML = lineaHtml;
        const texto = temp.innerText.trim();
        if (texto) productosTexto.push(texto);
    });

    // Procesar líneas en formato estructurado
    let productosFormateados = '';
    let totalLineas = productosTexto.length;

    for (let i = 0; i < totalLineas; i++) {
        const linea = productosTexto[i];

        // Detectar si es línea de producto (empieza con "n x" o contiene precio)
        if (/^\d+x/i.test(linea)) {
            productosFormateados += `${linea}\n`;
        } else if (linea.includes('$')) {
            productosFormateados += `${linea}\n`;
        } else {
            // Es un posible detalle
            productosFormateados += `   ${linea}\n`;
        }
    }

    // Construir ticket
    let contenido = "\x1B\x40"; // Reset impresora
    contenido += "\x1B\x61\x01\x1B\x21\x30Restaurante CHAO GUO\x1B\x21\x00\n";
    contenido += "------------------------------------------\n";
    contenido += `TURNO N°: ${turno}\n`;
    contenido += `Pedido N°: ${numeroPedido}\n`;
    contenido += `\x1B\x61\x01\x1B\x21\x30Cliente: ${cliente}\x1B\x21\x00\n`;
    contenido += `Fecha y Hora: ${fechaHora}\n`;
    contenido += `Estado: ${estado}\n`;
    contenido += "------------------------------------------\n";
    contenido += "Productos:\n";
    contenido += productosFormateados;
    contenido += "------------------------------------------\n";
    contenido += "\x1B\x61\x01¡Gracias por su compra!\n";
    contenido += "\n\n\n\n";
    contenido += "\x1D\x56\x00"; // Corte
    contenido += "\x1B\x70\x00\x19\xFA"; // Abrir cajón

    console.log("✅ Contenido final:\n", contenido);

    ensureConnection().then(() => {
        return qz.printers.getDefault();
    }).then((printer) => {
        if (!printer) {
            console.error("⚠ No se encontró impresora predeterminada.");
            return;
        }

        const config = qz.configs.create(printer);
        const data = [{ type: 'raw', format: 'plain', data: contenido }];
        return qz.print(config, data);
    }).then(() => {
        console.log("✅ Impresión completada.");
    }).catch((error) => {
        console.error("❌ Error al imprimir:", error);
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

