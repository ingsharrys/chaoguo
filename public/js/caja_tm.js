document.addEventListener('DOMContentLoaded', function() {
    // -------------------------------------------------------------------
    // Botón "Continuar" del modal de confirmación (pago procesado)
    // -------------------------------------------------------------------
    const continueButton = document.getElementById('continueButton');
    if (continueButton) {
        continueButton.addEventListener('click', redireccionarPorTipoSolicitud);
    }

    // -------------------------------------------------------------------
    // Botón "Mostrar descuento"
    // -------------------------------------------------------------------
  /*  const mostrarDescuentoBtn = document.getElementById('mostrar-descuento-btn');
    if (mostrarDescuentoBtn) {
        mostrarDescuentoBtn.addEventListener('click', function() {
            const descuentoInput = document.getElementById('descuentoInput');
            if (!descuentoInput) return;

            if (descuentoInput.style.display === 'none') {
                descuentoInput.style.display = 'block';
            } else {
                descuentoInput.style.display = 'none';
            }
        });
    }
*/
    // -------------------------------------------------------------------
    // Formulario de pago normal (si existe)
    // -------------------------------------------------------------------
    const formPago = document.getElementById('form-pago');
    if (formPago) {
        formPago.addEventListener('submit', function(e) {
            e.preventDefault(); // Evitar POST tradicional
            handlePaymentAndPrint(); // Se encarga de enviar con fetch
        });
    }

    // -------------------------------------------------------------------
    // Modal "Agregar Abonos" - Botones para clonar fila y guardar
    // -------------------------------------------------------------------
    const btnAgregarAbono = document.getElementById('btn-agregar-abono');
    if (btnAgregarAbono) {
        btnAgregarAbono.addEventListener('click', function() {
            const contenedor = document.getElementById('abonos-container');
            if (!contenedor) return;

            const rows = contenedor.querySelectorAll('.abono-row');
            const ultimaRow = rows[rows.length - 1];
            const nuevaRow = ultimaRow.cloneNode(true);

            // Limpiar el valor del input "efectivo" en la nueva fila
            const inputs = nuevaRow.querySelectorAll('input[name="efectivo[]"]');
            inputs.forEach(inp => inp.value = '');

            contenedor.appendChild(nuevaRow);
        });
    }

    const btnGuardarAbonos = document.getElementById('btn-guardar-abonos');
    if (btnGuardarAbonos) {
        btnGuardarAbonos.addEventListener('click', function() {
            guardarAbonos();
        });
    }

}); // Fin DOMContentLoaded

// -------------------------------------------------------------------
// 1) Redirección por tipo de solicitud (domicilios, etc.)
// -------------------------------------------------------------------
function redireccionarPorTipoSolicitud() {
    const tipoSolicitudElement = document.getElementById('tipo_solicitud');
    if (!tipoSolicitudElement) {
        console.error("El elemento con id 'tipo_solicitud' no existe. Redireccionando por defecto.");
        window.location.href = "https://admin.restaurantechaoguo.com//public/index.php?page=dashboard.php";
        return;
    }

    const tipoSolicitud = tipoSolicitudElement.value;
    let urlRedireccion;

    switch (tipoSolicitud) {
        case '50':
            urlRedireccion = "https://admin.restaurantechaoguo.com//public/index.php?page=whatsapp.php";
            break;
        case '51':
        case '52':
            urlRedireccion = "https://admin.restaurantechaoguo.com//public/index.php?page=dashboard.php";
            break;
        case '53':
            urlRedireccion = "https://admin.restaurantechaoguo.com//public/index.php?page=llamadas.php";
            break;
        default:
            urlRedireccion = "https://admin.restaurantechaoguo.com//public/index.php?page=dashboard.php";
    }

    window.location.href = urlRedireccion;
}

// -------------------------------------------------------------------
// 2) Calcular total con descuento
// -------------------------------------------------------------------
/*function calcularTotalConDescuento() {
    var descuentoInput = document.getElementById('descuento');
    if (!descuentoInput) return;

    var descuento = parseFloat(descuentoInput.value) || 0;
    var totalProductos = parseFloat(document.getElementById('total').value) || 0;
    var costoDomicilio = parseFloat(document.getElementById('costo_domicilio')?.value || 0);

    // Asegurar que el descuento no sea mayor al total de productos
    if (descuento > totalProductos) {
        alert("El descuento no puede ser mayor que el total.");
        descuento = totalProductos;
        descuentoInput.value = descuento;
    }

    var totalAPagarConDescuento = totalProductos - descuento + costoDomicilio;

    // Evitar que el total sea negativo
    if (totalAPagarConDescuento < 0) {
        totalAPagarConDescuento = 0;
    }

    // Mostrar el total con descuento en el HTML
    var totalDescuentoSpan = document.getElementById('total_a_pagar_con_descuento');
    if (totalDescuentoSpan) {
        totalDescuentoSpan.textContent = "$" + totalAPagarConDescuento.toLocaleString('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    // Actualizar el input oculto con el nuevo total
    var totalHidden = document.getElementById('total');
    if (totalHidden) {
        totalHidden.value = totalAPagarConDescuento;
    }
}
*/

// -------------------------------------------------------------------
// 3) Mostrar/ocultar inputs según método de pago
// -------------------------------------------------------------------
function toggleEfectivoInput() {
    var metodoPago = document.getElementById('m_pago').value;
    var efectivoInput       = document.getElementById('efectivoInput');
    var transferenciaInputs = document.getElementById('transferenciaInputs');
    var especialesInputs    = document.getElementById('especialesInputs');

    if (!efectivoInput || !transferenciaInputs || !especialesInputs) return;

    // Reset
    var pagoElem       = document.getElementById('pago');
    var bancoElem      = document.getElementById('banco');
    var referenciaElem = document.getElementById('referencia');
    var detalleElem    = document.getElementById('detalle');

    if (pagoElem)       pagoElem.value       = '';
    if (bancoElem)      bancoElem.value      = '';
    if (referenciaElem) referenciaElem.value = '';
    if (detalleElem)    detalleElem.value    = '';

    // Ocultar por defecto
    efectivoInput.style.display       = 'none';
    transferenciaInputs.style.display = 'none';
    especialesInputs.style.display    = 'none';

    // Mostrar según metodoPago
    if (metodoPago === 'efectivo'
     || metodoPago === 'efectivo_transferencia'
     || metodoPago === 'tarjeta_efectivo'
     || metodoPago === 'credito') {
        efectivoInput.style.display = 'block';
    }

    if (metodoPago === 'transferencia'
     || metodoPago === 'efectivo_transferencia') {
        transferenciaInputs.style.display = 'block';
    }

    if (metodoPago === 'cortesia'
     || metodoPago === 'devolucion'
     || metodoPago === 'credito') {
        especialesInputs.style.display = 'block';
    }
}

// -------------------------------------------------------------------
// 4) handlePaymentAndPrint: Enviar pago con fetch a pagoCaja.php
// -------------------------------------------------------------------
function handlePaymentAndPrint() {
    const form = document.getElementById('form-pago');
    if (!form) {
        console.error("No se encontró el formulario #form-pago.");
        return false;
    }

    // Recolectar datos del formulario
    const formData = new FormData(form);

    // Mostrar en consola todos los pares clave-valor
    console.log("Datos del Formulario:");
    for (const pair of formData.entries()) {
        console.log(pair[0] + ": " + pair[1]);
    }

    fetch('../controllers/pagoCaja.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta de pagoCaja:', data);
        if (data.status === 'success') {
            console.log('Pago procesado exitosamente.');

            // Enviar datos a la impresora
            const numeroPedido = formData.get('numero_pedido');
            const metodoPago = formData.get('m_pago');        // 👈 nuevo
            imprimirTicket(numeroPedido, metodoPago); // Llamamos la función para imprimir

            // Mostrar modal de confirmación
            $('#confirmationModal').modal({
                backdrop: 'static',
                keyboard: false
            });
            $('#confirmationModal').modal('show');
        } else {
            console.error('Error al procesar el pago:', data.message);
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error en la solicitud:', error);
        alert('Error al procesar el pago.');
    });

    return false;
}


function imprimirTicket(numeroPedido, metodoPago) {
    console.log("🖨️ Iniciando impresión con QZ Tray...");

    // Obtener datos desde la tabla HTML
    const filas = document.querySelectorAll('table tbody tr');
    const cajero = document.getElementById('nombre_cajero')?.value || 'N/A';
    const fechaHoraImp = new Date().toLocaleString('es-CO');
    // Buscar manualmente los elementos de Cliente y Celular en la pantalla
    let cliente = "Sin nombre";
    let celular = "Sin celular";

    document.querySelectorAll("p").forEach(p => {
        if (p.textContent.includes("Cliente:")) {
            cliente = p.textContent.replace("Cliente:", "").trim();
        }
        if (p.textContent.includes("Celular:")) {
            celular = p.textContent.replace("Celular:", "").trim();
        }
    });

    let contenidoTicket = [];

    // 🔹 Formato de impresión
    contenidoTicket.push("\x1B\x40"); // Reset de impresora
    contenidoTicket.push("\x1B\x61\x01"); // Centrar texto
    contenidoTicket.push("\x1B\x21\x30"); // Texto grande
    contenidoTicket.push("Restaurante CHAO GUO\n");
    contenidoTicket.push("\x1B\x21\x00"); // Restaurar tamaño normal
    contenidoTicket.push("--------------------------------\n");
    contenidoTicket.push("Pedido N°: " + numeroPedido + "\n");
    contenidoTicket.push("Fecha/Hora: " + fechaHoraImp + "\n");
    contenidoTicket.push("Cajero: " + cajero + "\n");              // ⬅️ nuevo
    contenidoTicket.push("Pago: " + (metodoPago || '—') + "\n"); 
    contenidoTicket.push("Cliente: " + cliente + "\n");
    contenidoTicket.push("Celular: " + celular + "\n");
    contenidoTicket.push("--------------------------------\n");

    // 📌 **Tabla de Productos**
    contenidoTicket.push("Prefijo    Cant   Tipo       Precio  Total\n");
    contenidoTicket.push("------------------------------------------\n");

    // 🔹 Recorrer los productos y formatear los datos
    filas.forEach(tr => {
        let prefijo = tr.children[0]?.innerText.trim().substring(0, 10) || 'N/A';
        let cantidad = tr.children[2]?.innerText.trim() || '0';
        let tipoProd = tr.children[6]?.innerText.trim().substring(0, 8) || 'N/A';
        let precio = tr.children[3]?.innerText.replace('$', '').replace(',', '') || '0';
        let subtotal = tr.children[4]?.innerText.replace('$', '').replace(',', '') || '0';
        let detalle = tr.children[5]?.innerText.trim().substring(0, 20) || '';

        // Formato alineado para impresora térmica
        contenidoTicket.push(
            prefijo.padEnd(10) + " " + cantidad.padStart(3) + " " + tipoProd.padEnd(10) + " $" + precio.padStart(6) + " $" + subtotal.padStart(6) + "\n"
        );
        if (detalle) {
            contenidoTicket.push("   Detalle: " + detalle + "\n");
        }
    });

    // 🔹 Total a pagar
    let totalElement = document.getElementById('total_a_pagar_con_descuento');
    let totalPagar = totalElement ? totalElement.innerText.replace('$', '').replace(',', '') : '0';

    contenidoTicket.push("--------------------------------\n");
    contenidoTicket.push("\x1B\x21\x20TOTAL PAGADO: $" + totalPagar + "\x1B\x21\x00\n"); // Negrita
    contenidoTicket.push("================================\n");

    // ✅ **Mensaje de agradecimiento**
    contenidoTicket.push("\x1B\x61\x01\x1B\x21\x20¡Gracias por su compra!\x1B\x21\x00\n");

    // 🏁 **Corte de papel y apertura del cajón de dinero**
    contenidoTicket.push("\n\n\n\n"); // Espacio para corte
    contenidoTicket.push("\x1D\x56\x00"); // Corte de papel
    contenidoTicket.push("\x1B\x70\x00\x19\xFA"); // Abrir cajón de dinero

    console.log("✅ Contenido a imprimir:\n", contenidoTicket.join(""));

    // 🔹 **Conectar a QZ Tray e imprimir**
    qz.websocket.connect().then(() => {
        return qz.printers.find("POS-80"); // Nombre de la impresora POS
    }).then(printer => {
        let config = qz.configs.create(printer, { encoding: "ISO-8859-1" });
        return qz.print(config, [{ type: 'raw', format: 'plain', data: contenidoTicket.join("") }]);
    }).then(() => {
        console.log("✅ Ticket impreso correctamente.");
        return qz.websocket.disconnect();
    }).catch(err => {
        console.error("❌ Error al imprimir con QZ Tray:", err);
    });
}








// -------------------------------------------------------------------
// 5) Calcular cambio
// -------------------------------------------------------------------
function calcularCambio() {
    var totalElement     = document.getElementById('total');
    var pagoElement      = document.getElementById('pago');
    var resultadoElement = document.getElementById('resultado');
    if (!totalElement || !pagoElement || !resultadoElement) return;

    var total      = parseFloat(totalElement.value) || 0;
    var pago       = parseFloat(pagoElement.value || '0');
    var metodoPago = document.getElementById('m_pago').value;

    var formateadorMoneda = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    if (metodoPago === 'efectivo') {
        if (pago < total) {
            resultadoElement.textContent = "Falta por pagar: " + formateadorMoneda.format(total - pago);
        } else if (pago === total) {
            resultadoElement.textContent = "Pagado completamente en efectivo.";
        } else {
            resultadoElement.textContent = "Cambio a devolver: " + formateadorMoneda.format(pago - total);
        }
    } else if (metodoPago === 'tarjeta_efectivo') {
        if (pago < total) {
            resultadoElement.textContent = "Restante por pagar con tarjeta: " + formateadorMoneda.format(total - pago);
        } else if (pago === total) {
            resultadoElement.textContent = "Pagado completamente en efectivo.";
        } else {
            resultadoElement.textContent = "Cambio a devolver: " + formateadorMoneda.format(pago - total);
        }
    } else if (metodoPago === 'efectivo_transferencia') {
        if (pago < total) {
            resultadoElement.textContent = "Restante por pagar con transferencia: " + formateadorMoneda.format(total - pago);
        } else if (pago === total) {
            resultadoElement.textContent = "Pagado completamente en efectivo.";
        } else {
            resultadoElement.textContent = "Cambio a devolver: " + formateadorMoneda.format(pago - total);
        }
    } else if (metodoPago === 'credito') {
        if (pago < total) {
            resultadoElement.textContent = "Restante por pagar con crédito: " + formateadorMoneda.format(total - pago);
        } else if (pago === total) {
            resultadoElement.textContent = "Pagado completamente en efectivo.";
        } else {
            resultadoElement.textContent = "Cambio a devolver: " + formateadorMoneda.format(pago - total);
        }
    } else if (metodoPago === 'transferencia') {
        resultadoElement.textContent = "Pagado completamente por transferencia.";
    } else {
        resultadoElement.textContent = "Método de pago no válido.";
    }
}

// -------------------------------------------------------------------
// 6) Reversar Caja
// -------------------------------------------------------------------
function reversarCaja(numeroPedido) {
    const codigoSeguridad = prompt('Por favor, ingresa el código de seguridad para reversar el pago:');
    if (codigoSeguridad && codigoSeguridad.trim() !== '') {
        fetch('../controllers/reversar_caja.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ numero_pedido: numeroPedido, codigo_seguridad: codigoSeguridad })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Pago reversado correctamente.');
                window.location.reload();
            } else {
                alert('Error al reversar el pago: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error al reversar el pago:', error);
        });
    } else {
        alert('El código de seguridad es obligatorio para reversar el pago.');
    }
}

// -------------------------------------------------------------------
// 7) Abonos de Crédito: guardarAbonos()
// -------------------------------------------------------------------
function guardarAbonos() {
    const idCreditoHidden = document.getElementById('id_credito_hidden');
    if (!idCreditoHidden) {
        alert('No se encontró el id_credito para el abono.');
        return;
    }

    const idCredito = idCreditoHidden.value;
    if (!idCredito) {
        alert('No se encontró el id_credito para el abono.');
        return;
    }

    const abonosContainer = document.getElementById('abonos-container');
    if (!abonosContainer) return;

    const rows = abonosContainer.querySelectorAll('.abono-row');
    let abonosData = [];

    rows.forEach(row => {
        let metodo = row.querySelector('select[name="m_pagocr[]"]').value;
        let valor  = row.querySelector('input[name="efectivo[]"]').value;
        if (!valor) { valor = 0; }
        abonosData.push({ m_pagocr: metodo, efectivo: valor });
    });

    fetch('../controllers/abonar_credito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_credito: idCredito,
            abonos: abonosData
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Abonos guardados exitosamente.');
            $('#modal-abonar').modal('hide');
            location.reload();
        } else {
            alert('Error al guardar abonos: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Error al guardar abonos:', err);
        alert('Error al guardar abonos.');
    });
}

// -------------------------------------------------------------------
// 8) Funciones para imprimir la factura
// -------------------------------------------------------------------
function findElementWithText(tag, text) {
    const elements = document.querySelectorAll(tag);
    for (let element of elements) {
        if (element.innerText.includes(text)) {
            return element;
        }
    }
    return null;
}

function printInvoicec(numeroPedido, metodoPago) {
    
    const cajero = document.getElementById('nombre_cajero')?.value || 'N/A';
    // Buscar en el DOM ciertos <p> para info de cliente
    const clienteElement   = findElementWithText('p', 'Cliente:');
    const celularElement   = findElementWithText('p', 'Celular:');
    const direccionElement = findElementWithText('p', 'Dirección:');
    const totalElement     = findElementWithText('p', 'Total a Pagar:');
    const fechaHoraImp = new Date().toLocaleString('es-CO');
    const cliente   = clienteElement ? clienteElement.innerText.replace('Cliente:', '').trim() : '';
    const celular   = celularElement ? celularElement.innerText.replace('Celular:', '').trim() : '';
    const direccion = direccionElement ? direccionElement.innerText.replace('Dirección:', '').trim() : '';
    let mesa        = '';
    const total     = totalElement ? totalElement.innerText.replace('Total a Pagar: $', '').trim() : '';

    // Tomar los productos
    const filas = document.querySelectorAll('table tbody tr');
    const productos = Array.from(filas).map(tr => {
        const td0 = tr.children[0]?.innerText || '';
        const td1 = tr.children[1]?.innerText || '0';
        const td2 = tr.children[2]?.innerText.replace('$','').replace(',','').trim() || '0';
        const td3 = tr.children[3]?.innerText.replace('$','').replace(',','').trim() || '0';
        const td4 = tr.children[4]?.innerText || '';
        const td5 = tr.children[5]?.innerText || '';

        return {
            nombreProducto: td0.slice(0,13),
            cantidad       : parseInt(td1) || 0,
            precioUnitario : parseFloat(td2) || 0,
            subtotal       : parseFloat(td3) || 0,
            detalle        : td4,
            tipoProducto   : td5
        };
    });

    let contenido = `
Restaurante CHAO GUO
Pedido N°: ${numeroPedido}
Fecha/Hora: ${fechaHoraImp}
Cajero: ${cajero}
Pago: ${metodoPago}
Cliente: ${cliente}
Celular: ${celular}
Dirección: ${direccion}
${mesa ? 'Mesa: ' + mesa : 'Domicilio'}\n
----------------------------------------
Producto      Cant.   Precio  Subtotal
----------------------------------------
`;

    let totalCalculado = 0;
    productos.forEach(prod => {
        totalCalculado += prod.subtotal;
        contenido += `${prod.nombreProducto.padEnd(14)} ${String(prod.cantidad).padEnd(6)} $${prod.precioUnitario.toFixed(0).padEnd(7)} $${prod.subtotal.toFixed(0)}\n`;
        contenido += `-  ${prod.detalle}\n-  ${prod.tipoProducto}\n`;
        contenido += `----------------------------------------\n`;
    });
    contenido += `TOTAL: $${totalCalculado.toFixed(2)}\n========================================\n`;

    // fetch a tu servidor de impresión
    fetch('http://127.0.0.1:5000/imprimir', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ contenido })
    })
    .then(res => {
        if (res.ok) {
            console.log("Impresión completada");
        } else {
            console.error("Error al imprimir:", res.statusText);
        }
    })
    .catch(err => {
        console.error("Error al conectar con el servidor de impresión:", err);
    });
}
