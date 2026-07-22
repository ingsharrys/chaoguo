<?php
require_once '../controllers/actualizar_reporte.php';

$reporteController = new ReporteController();
$data = $reporteController->getReporteData();

// Extract variables for use in the view
extract($data);
?>


<div class="container mt-5">
    <h1>Reporte de Turnos - <?php echo $fechaActual; ?></h1>

    <!-- Formulario para seleccionar la fecha y el cajero -->
    <form method="POST" action="">
        <div class="form-group">
            <label for="fecha_seleccionada">Seleccione la Fecha:</label>
            <input type="date" id="fecha_seleccionada" name="fecha_seleccionada"
                   class="form-control"
                   value="<?php echo $fechaActual; ?>">
        </div>

    <!-- Select para filtrar por cajero (dinámico) -->
    <div class="form-group">
        <label for="cajero">Seleccione el Cajero:</label>
        <select id="cajero" name="cajero" class="form-control">
            <option value="consolidado" <?php echo ($cajeroSeleccionado=='consolidado'?'selected':''); ?>>Consolidado (Todos)</option>
            <?php foreach ($cajerosDb as $cajero): ?>
                <option value="<?php echo htmlspecialchars($cajero['id_mese']); ?>" <?php echo ($cajeroSeleccionado==$cajero['id_mese'] ? 'selected' : ''); ?>>
                    <?php echo htmlspecialchars($cajero['nombre_mese']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
        
         <!-- Select para filtrar por banco -->
    <div class="form-group">
        <label for="banco">Seleccione el Banco:</label>
        <select id="banco" name="banco" class="form-control">
            <option value="">Todos</option>
            <option value="Nequi" <?php echo $bancoSeleccionado == 'Nequi' ? 'selected' : ''; ?>>Nequi</option>
            <option value="Bancolombia" <?php echo $bancoSeleccionado == 'Bancolombia' ? 'selected' : ''; ?>>Bancolombia</option>
            <option value="Davivienda" <?php echo $bancoSeleccionado == 'Davivienda' ? 'selected' : ''; ?>>Davivienda</option>
            <option value="Daviplata" <?php echo $bancoSeleccionado == 'Daviplata' ? 'selected' : ''; ?>>Daviplata</option>
        </select>
    </div>

    <!-- Select para filtrar por tipo de solicitud -->
    <div class="form-group">
        <label for="tipo_solicitud">Seleccione el Tipo de Solicitud:</label>
        <select id="tipo_solicitud" name="tipo_solicitud" class="form-control">
            <option value="">Todos</option>
            <option value="50" <?php echo $tipoSolicitudSeleccionado == '50' ? 'selected' : ''; ?>>Domicilio</option>
            <option value="51" <?php echo $tipoSolicitudSeleccionado == '51' ? 'selected' : ''; ?>>Turno</option>
            <option value="52" <?php echo $tipoSolicitudSeleccionado == '52' ? 'selected' : ''; ?>>Mesas</option>
            <option value="53" <?php echo $tipoSolicitudSeleccionado == '53' ? 'selected' : ''; ?>>Recoger</option>
        </select>
    </div>

    <div class="form-group">
        <div class="justify-content-between">
            <button type="submit" class="btn btn-primary">Actualizar Reporte</button>
            <button type="button" class="btn btn-primary" onclick="imprimirDatos()">Imprimir Datos</button>
             <button type="button" class="btn btn-success" onclick="imprimirResumen()">Imprimir Resumen</button>
        </div>
    </div>
    </form>
    
        <!-- Resumen de ventas -->
    <div class="card-body">
        <h4>Total Recibido en Efectivo: $<?php echo number_format($totales['totalRecibidoEfectivo'], 2); ?></h4>
        <ul>
            <li>Total vendido en efectivo: $<?php echo number_format($totales['totalEfectivo'], 2); ?></li>
            <li>Total credito en efectivo: $<?php echo number_format($totales['total_efectivo_abonos'], 2); ?></li>
            <li>Total pagado en nomina: $<?php echo number_format($totales['total_nomina'], 2); ?></li>
        </ul>
    
        <h4>Total Recibido en Tarjetas: $<?php echo number_format($totales['totalRecibidoTarjeta'], 2); ?></h4>
        <ul>
            <li>Total vendido por tarjeta: $<?php echo number_format($totales['totalTarjeta'], 2); ?></li>
            <li>Total credito por tarjeta: $<?php echo number_format($totales['total_tarjeta_abonos'], 2); ?></li>
        </ul>
    
        <h4>Total Recibido en Transferencias: $<?php echo number_format($totales['totalRecibidoTransferencia'], 2); ?></h4>
        <ul>
            <li>Total vendido por transferencia: $<?php echo number_format($totales['totalTransferencia'], 2); ?></li>
            <li>Total credito por transferencia: $<?php echo number_format($totales['total_transferencia_abonos'], 2); ?></li>
        </ul>
    
        <h4>Total en Devoluciones: $<?php echo number_format($totales['totalDevolucion'], 2); ?></h4>
    </div>

    <!-- Sistema de Tabs -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <?php foreach ($metodosPago as $metodo): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($metodo=='consolidado'?'active':''); ?>"
                   id="<?php echo $metodo; ?>-tab"
                   data-toggle="tab"
                   href="#<?php echo $metodo; ?>"
                   role="tab"
                   aria-controls="<?php echo $metodo; ?>"
                   aria-selected="true">
                    <?php echo ucfirst($metodo=='consolidado'?'Todos':$metodo); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content" id="myTabContent">
        <?php foreach ($metodosPago as $metodo): ?>
        <div class="tab-pane fade <?php echo ($metodo=='consolidado'?'show active':''); ?>"
             id="<?php echo $metodo; ?>"
             role="tabpanel"
             aria-labelledby="<?php echo $metodo; ?>-tab">
            
            <div class="scroll-box">
                <table class="table table-bordered table-striped mt-3">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Número Pedido</th>
                            <th>Turno</th>
                            <th>Tipo Solicitud</th>
                            <th>Estado</th>
                            <th>Método de Pago</th>
                            <th>Cajero</th>
                            <th>Costo</th>
                            <th>Efectivo</th>
                            <th>Banco</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($turnosPorMetodo[$metodo] as $registro):
                        $fecha         = $registro['fecha'] ?? '';
                        $idPedido      = $registro['id_pedido'] ?? '';
                        $turno         = $registro['turno'] ?? '';
                        $tipoSolicitud = $registro['tipo_solicitud'] ?? '';
                        $estado        = $registro['estado'] ?? '';
                        $idCliente     = $registro['id_cliente'] ?? '';
                        $metodoPago    = $registro['m_pago'] ?? '';
                        $cajero        = $registro['cajero'] ?? '';
                        $costo         = $registro['costo'] ?? 0;
                        $banco         = $registro['banco'] ;
                        $efectivo      = $registro['efectivo'] ?? 0;
                        
                       
                    ?>
                        <tr>
                            <td><?php echo $fecha; ?></td>
                            <td><?php echo $idPedido; ?></td>
                            <td><?php echo $turno; ?></td>
                            <td>
                                <?php 
                                    // Mostrar la descripción del tipo de solicitud
                                    switch ($tipoSolicitud) {
                                        case 50:
                                            echo 'Domicilio';
                                            break;
                                        case 51:
                                            echo 'Turno';
                                            break;
                                        case 52:
                                            echo 'Mesas';
                                            break;
                                        case 53:
                                            echo 'Recoger';
                                            break;
                                        default:
                                            echo 'Mesas';
                                            break;
                                    }
                                ?>
                            </td>
                            <td><?php echo $estado; ?></td>
                            <td><?php echo ucfirst($metodoPago); ?></td>
                            <td><?php echo $cajero; ?></td>
                            <td><?php echo number_format($costo, 2); ?></td>
                            <td><?php echo number_format($efectivo, 2); ?></td>
                            <td><?php echo $banco; ?></td>
                            <td><button class="btn btn-info" data-toggle="modal" data-target="#detalleModal" data-pedido="<?php echo $idPedido; ?>">Ver Detalle</button></td>
                        </tr>
                    <?php  endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal para mostrar el detalle del pedido -->
<div class="modal fade" id="detalleModal" tabindex="-1" role="dialog" aria-labelledby="detalleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detalleModalLabel">Detalle del Pedido</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Aquí se cargará el contenido del pedido dinámicamente -->
                <div id="detallePedidoContent"></div>
            </div>
        </div>
    </div>
</div>

<!-- Librerías JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
  window.$jq = jQuery.noConflict(true); // guarda la versión completa
  window.$   = window.jQuery = $jq;     // vuelve a asignarla a $
</script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>


// Script para cargar el detalle del pedido en el modal
$jq('#detalleModal').on('show.bs.modal', function (e) {
    const id = $jq(e.relatedTarget).data('pedido');
    $jq('#detallePedidoContent').html('Cargando…');

    $jq.ajax({
        url : '../controllers/detalle_pedido.php',
        data: { id_pedido: id },
        success: function (html) {
            console.log('[DEBUG] recibido', html.length, 'bytes');
            $jq('#detallePedidoContent').html(html);
        },
        error: function (xhr, status, err) {
            console.error('[AJAX ERROR]', status, err, xhr.responseText);
            $jq('#detallePedidoContent').html('<p>Error al cargar.</p>');
        }
    });
});


    async function imprimirDatos() {
    let fechaSeleccionada = document.getElementById("fecha_seleccionada").value;

    if (typeof qz === "undefined") {
        console.error("❌ QZ Tray no está definido. Asegúrate de que QZ Tray esté instalado y en ejecución.");
        alert("Error: No se pudo conectar a QZ Tray. Verifica que la aplicación esté abierta.");
        return;
    }

    let productosAgrupados = {};
    let totalFinal = 0;

    try {
        let response = await fetch(`../controllers/obtener_productos_reporte.php?fecha=${fechaSeleccionada}`);
        let pedidos = await response.json();

        if (pedidos.error) {
            alert(pedidos.error);
            return;
        }

        pedidos.forEach(pedido => {
            let key = `${pedido.nombre_producto} - ${pedido.tipo_producto}`; // ✅ Clave única por producto + tipo_producto
            let cantidad = Number(pedido.cantidad_total) || 0;
            let precio = Number(pedido.precio_unitario) || 0;
            let totalProducto = cantidad * precio;

            // Si ya existe en el objeto, sumamos la cantidad y el total
            if (productosAgrupados[key]) {
                productosAgrupados[key].cantidad += cantidad;
                productosAgrupados[key].totalProducto += totalProducto;
            } else {
                productosAgrupados[key] = {
                    cantidad,
                    precio,
                    totalProducto
                };
            }

            totalFinal += totalProducto;
        });

        let facturaPOS = "\x1B\x40"; // Reset impresora
        facturaPOS += "\x1B\x61\x01\x1B\x21\x20REPORTE DE VENTAS\x1B\x21\x00\n"; // Centrar y agrandar
        facturaPOS += "------------------------------------------\n";
        facturaPOS += "Producto (Tipo)          Cantidad   Total\n";
        facturaPOS += "------------------------------------------\n";

        for (let key in productosAgrupados) {
            let { cantidad, precio, totalProducto } = productosAgrupados[key];
            let [producto, tipoProducto] = key.split(" - "); // ✅ Separar el nombre del producto y el tipo_producto
            facturaPOS += `${producto.padEnd(15)} ${tipoProducto.padEnd(10)} ${cantidad.toString().padEnd(5)} $${totalProducto.toLocaleString('es-CO')}\n`;
        }

        facturaPOS += "------------------------------------------\n";
        facturaPOS += `TOTAL GENERAL: $${totalFinal.toLocaleString('es-CO')}\n`; // Suma total de los productos
        facturaPOS += "==========================================\n";
        facturaPOS += "\x1B\x61\x01\x1B\x21\x20¡Reporte!\x1B\x21\x00\n";
        facturaPOS += "\n\n\n";
        facturaPOS += "\x1D\x56\x00"; // Corte de papel
        facturaPOS += "\x1B\x70\x00\x19\xFA"; // Abrir cajón

        console.log("✅ Contenido a imprimir:\n", facturaPOS);

        await ensureConnection();
        const printer = await qz.printers.getDefault();
        if (!printer) {
            console.error("⚠ No se encontró una impresora predeterminada.");
            return;
        }
        const config = qz.configs.create(printer);
        const printData = [{ type: 'raw', format: 'plain', data: facturaPOS }];
        await qz.print(config, printData);
        console.log("✅ Impresión completada.");
    } catch (error) {
        console.error('❌ Error al obtener datos o imprimir:', error);
    }
}




async function imprimirResumen() {
    let fechaSeleccionada = document.getElementById("fecha_seleccionada").value;

    if (typeof qz === "undefined") {
        console.error("❌ QZ Tray no está definido. Asegúrate de que QZ Tray esté instalado y en ejecución.");
        alert("Error: No se pudo conectar a QZ Tray. Verifica que la aplicación esté abierta.");
        return;
    }

    // Obtener valores del resumen de ventas directamente del HTML
    let totalEfectivo = document.querySelector("h4:nth-of-type(1)").innerText.replace("Total Recibido en Efectivo: $", "").trim();
    let totalTarjeta = document.querySelector("h4:nth-of-type(2)").innerText.replace("Total Recibido en Tarjetas: $", "").trim();
    let totalTransferencia = document.querySelector("h4:nth-of-type(3)").innerText.replace("Total Recibido en Transferencias: $", "").trim();
    let totalDevoluciones = document.querySelector("h4:nth-of-type(4)").innerText.replace("Total en Devoluciones: $", "").trim();

    let facturaResumen = "\x1B\x40"; // Reset impresora
    facturaResumen += "\x1B\x61\x01\x1B\x21\x20REPORTE DE RESUMEN\x1B\x21\x00\n"; // Centrar y agrandar
    facturaResumen += `Fecha: ${fechaSeleccionada}\n`;
    facturaResumen += "------------------------------------------\n";
    facturaResumen += `Total Recibido en Efectivo: $${totalEfectivo}\n`;
    facturaResumen += `Total Recibido en Tarjetas: $${totalTarjeta}\n`;
    facturaResumen += `Total Recibido en Transferencias: $${totalTransferencia}\n`;
    facturaResumen += `Total en Devoluciones: $${totalDevoluciones}\n`;
    facturaResumen += "==========================================\n";
    facturaResumen += "\x1B\x61\x01\x1B\x21\x20¡Resumen de ventas!\x1B\x21\x00\n";
    facturaResumen += "\n\n\n";
    facturaResumen += "\x1D\x56\x00"; // Corte de papel
    facturaResumen += "\x1B\x70\x00\x19\xFA"; // Abrir cajón

    console.log("✅ Contenido a imprimir:\n", facturaResumen);

    try {
        await ensureConnection();
        const printer = await qz.printers.getDefault();
        if (!printer) {
            console.error("⚠ No se encontró una impresora predeterminada.");
            return;
        }
        const config = qz.configs.create(printer);
        const printData = [{ type: 'raw', format: 'plain', data: facturaResumen }];
        await qz.print(config, printData);
        console.log("✅ Impresión del resumen completada.");
    } catch (error) {
        console.error('❌ Error al imprimir el resumen:', error);
    }
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







</script>

