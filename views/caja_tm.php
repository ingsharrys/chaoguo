<?php


// Verificar si se recibieron los datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_pedido'])) {
    $numero_pedido = $_POST['numero_pedido'];
    $cajeros       = Session::get('cajero');

    // 1) Obtener los detalles del pedido
  if (!empty($numero_pedido)) {
    // Verificar si el pedido existe en la tabla turnero
    $query_verificar_turno = "SELECT COUNT(*) as count FROM turnero WHERE id_pedido = ?";
    $stmt_verificar_turno = $conn->prepare($query_verificar_turno);
    $stmt_verificar_turno->execute([$numero_pedido]);
    $es_turno = $stmt_verificar_turno->fetch(PDO::FETCH_ASSOC)['count'] > 0;

    if ($es_turno) {
        // ✅ Pedido de Turno: Se usa la tabla turnero
        $query_pedido = "
            SELECT 
                pr.prefijo AS prefijo, 
                pr.nombre AS nombre_producto, 
                p.cantidad, 
                p.tipo_solicitud, 
                prp.precio AS precio_producto, 
                p.detalle, 
                p.tipo_producto, 
                COALESCE(c.cliente, 'Cliente de Mesa') AS nombre_cliente, 
                COALESCE(c.celular, 'Sin celular') AS celular_cliente
            FROM pedidos p 
            JOIN productos pr ON p.id_pro = pr.id_pro
            JOIN precios prp ON pr.id_pro = prp.idproduc
            JOIN turnero t ON p.numero_pedido = t.id_pedido
            LEFT JOIN clientes c ON t.id_cliente = c.id
            WHERE p.numero_pedido = ? 
            AND prp.tipo_prod = p.tipo_producto
        ";
    } else {
        // ✅ Pedido de Mesa: No usa la tabla turnero, busca directamente en pedidos
        $query_pedido = "
            SELECT 
                pr.prefijo AS prefijo, 
                pr.nombre AS nombre_producto, 
                p.cantidad, 
                p.tipo_solicitud, 
                prp.precio AS precio_producto, 
                p.detalle, 
                p.tipo_producto, 
                'Cliente de Mesa' AS nombre_cliente, 
                'Sin celular' AS celular_cliente
            FROM pedidos p 
            JOIN productos pr ON p.id_pro = pr.id_pro
            JOIN precios prp ON pr.id_pro = prp.idproduc
            WHERE p.numero_pedido = ? 
            AND prp.tipo_prod = p.tipo_producto
        ";
    }

    $stmt_pedido = $conn->prepare($query_pedido);
    $stmt_pedido->bindValue(1, $numero_pedido, PDO::PARAM_STR);
    $stmt_pedido->execute();
    $detalles_pedido = $stmt_pedido->fetchAll(PDO::FETCH_ASSOC);
}


// Validar que los datos del cliente existan
$nombre_cliente = $detalles_pedido[0]['nombre_cliente'] ?? "Cliente de Mesa";
$celular_cliente = $detalles_pedido[0]['celular_cliente'] ?? "Sin celular";






    // Manejo del costo de domicilio
    $costo_domicilio = 0;
    $tipo_solicitud  = null;

    if (!empty($detalles_pedido)) {
        $tipo_solicitud = $detalles_pedido[0]['tipo_solicitud'];
        if ($tipo_solicitud == 50) {
            // Consultar el costo del domicilio
            $query_domicilio = "SELECT precio FROM domicilios WHERE id_pedido = :id_pedido";
            $stmt_domicilio = $conn->prepare($query_domicilio);
            $stmt_domicilio->bindParam(':id_pedido', $numero_pedido);
            $stmt_domicilio->execute();
            $resultado_domicilio = $stmt_domicilio->fetch(PDO::FETCH_ASSOC);
            if ($resultado_domicilio) {
                $costo_domicilio = (float)$resultado_domicilio['precio'];
            }
        }
    }
    
    
    
     // Calcular el total de productos
    $total_productos = 0;
    foreach ($detalles_pedido as $detalle) {
        $cantidad = (float)$detalle['cantidad'];
        $precio_producto = (float)$detalle['precio_producto'];
        $total_productos += $cantidad * $precio_producto;
    }

    // Asegurarse de que el total de productos sea correcto
    echo "Total Productos: " . number_format($total_productos, 0, '', ',') . "<br>";

    // Calcular el total a pagar sumando el costo de domicilio
    //$total_a_pagar = $total_productos + $costo_domicilio;
    
    // Calcular el total a pagar sin sumar el costo de domicilio
    $total_a_pagar = $total_productos;

    // Asegurarse de que el total a pagar sea correcto
    echo "Total a Pagar: " . number_format($total_a_pagar, 0, '', ',') . "<br>";
    
    





    // 2) Consultar en 'caja' si ya existe un pago
    $query_check_pago = "
        SELECT COUNT(*) AS count, costo, m_pago 
        FROM caja 
        WHERE id_pedidoc = :id_pedidoc
    ";
    $stmt_check_pago = $conn->prepare($query_check_pago);
    $stmt_check_pago->bindParam(':id_pedidoc', $numero_pedido);
    $stmt_check_pago->execute();
    $pago_existente_data = $stmt_check_pago->fetch(PDO::FETCH_ASSOC);

    $pago_existente = $pago_existente_data['count'] > 0;
    $monto_pagado   = $pago_existente_data['costo'] ?? 0; // El costo total en 'caja'
    $metodo_pagado  = $pago_existente_data['m_pago'] ?? '';

    // 3) Si el método de pago es 'credito', buscar abonos
    $abonos = [];      
    $abonosTotal = 0;  

    $idCredito = null; // Para guardar si existe
    if ($metodo_pagado === 'credito') {
        // Buscar el crédito en la tabla 'creditos'
        $query_credito = "
            SELECT idcr
            FROM creditos
            WHERE m_pedidocr = :numero_pedido
            LIMIT 1
        ";
        $stmt_credito = $conn->prepare($query_credito);
        $stmt_credito->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
        $stmt_credito->execute();
        $rowCredito = $stmt_credito->fetch(PDO::FETCH_ASSOC);

        if ($rowCredito) {
            $idCredito = $rowCredito['idcr'];
            // Obtener abonos de 'abono_credito'
            $query_abonos = "
                SELECT id, m_pagocr, efectivo, fecha_abono
                FROM abono_credito
                WHERE id_credito = :id_credito
                ORDER BY fecha_abono DESC
            ";
            $stmt_abonos = $conn->prepare($query_abonos);
            $stmt_abonos->bindParam(':id_credito', $idCredito, PDO::PARAM_INT);
            $stmt_abonos->execute();
            $abonos = $stmt_abonos->fetchAll(PDO::FETCH_ASSOC);

            // Calcular la sumatoria de abonos
            foreach ($abonos as $ab) {
                $abonosTotal += (float)$ab['efectivo'];
            }
        }
    }

} else {
    echo "Datos incompletos para procesar la solicitud.";
    exit;
}
?>

<div class="container">
    <h1>Pago del Pedido <?php echo htmlspecialchars($numero_pedido); ?></h1>
    <h2>Detalles del Pedido <?php echo htmlspecialchars($cajero); ?></h2>
     <!-- Mostrar Datos del Cliente -->
    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($nombre_cliente); ?></p>
    <p><strong>Celular:</strong> <?php echo htmlspecialchars($celular_cliente); ?></p>


    <!-- Tabla de Detalles del Pedido -->
    <table class="table table-bordered">
        <thead>
            <tr>
                 <th>Prefijo</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
                <th>Detalle</th>
                <th>Tipo de Producto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles_pedido as $detalle): ?>
            <tr>
                <td><?php echo htmlspecialchars($detalle['prefijo']); ?></td>
                <td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td>
                <td><?php echo htmlspecialchars($detalle['cantidad']); ?></td>
                <td><?php echo "$" . number_format($detalle['precio_producto'], 0, '', ','); ?></td>
                <td><?php echo "$" . number_format($detalle['precio_producto'] * $detalle['cantidad'], 0, '', ','); ?></td>
                <td><?php echo htmlspecialchars($detalle['detalle']); ?></td>
                <td><?php echo htmlspecialchars($detalle['tipo_producto']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><strong>Total de Productos:</strong> <?php echo "$" . number_format($total_productos, 0, '', ','); ?></p>

<?php if ($tipo_solicitud == 50 && $costo_domicilio > 0): ?>
    <p><strong>Costo de Domicilio:</strong> <?php echo "$" . number_format($costo_domicilio, 0, '', ','); ?></p>
<?php endif; ?>

<!-- Botón para activar el campo de descuento -->
<button type="button" class="btn btn-info" id="mostrar-descuento-btn">Aplicar Descuento</button>
            <!-- Campo de descuento (oculto inicialmente) -->
        <div id="descuentoInput" style="display: none;">
    <label for="descuento">Ingresar Descuento:</label>
    <input type="number" id="descuento" name="descuento" step="0.01" value="0" oninput="calcularTotalConDescuento()">
</div>



<?php
// Si el pedido es un domicilio (tipo_solicitud = 50) y tiene costo de domicilio > 0
if ($tipo_solicitud == 50 && $costo_domicilio > 0) {
    // Sumar el costo de domicilio al total a pagar
    $total_a_pagar += $costo_domicilio;
    ?>
    <p><strong>Costo de Domicilio:</strong> 
        <?php echo "$" . number_format($costo_domicilio, 0, '', ','); ?>
    </p>
    <?php
}

// Mostrar el total a pagar (que ya incluye el domicilio si se dio la condición)
?>





<p><strong>Total a Pagar (con descuento):</strong> <span id="total_a_pagar_con_descuento"><?php echo "$" . number_format($total_a_pagar, 0, '', ','); ?></span></p>

    <h2>Pago</h2>

    <?php if ($pago_existente): ?>
        <!-- Ya existe un registro en "caja" -->
        <button class="btn btn-success">
          PAGADO (<?php echo htmlspecialchars($metodo_pagado); ?>)
        </button>
        <a href="../public/index.php?page=dashboard.php" class="btn btn-warning">Volver</a>
        <button type="button" class="btn btn-danger" onclick="reversarCaja(<?php echo (int)$numero_pedido; ?>)">
            Reversar Caja
        </button>

        <!-- Si m_pago es 'credito', mostrar abonos -->
        <?php if ($metodo_pagado === 'credito'): ?>
            <h3>Abonos Registrados</h3>
            <!-- Botón para abrir el modal de abonos -->
            <button 
              class="btn btn-primary" 
              data-toggle="modal" 
              data-target="#modal-abonar"
              onclick="document.getElementById('id_credito_hidden').value='<?php echo (int)$idCredito; ?>';"
            >
              Abonar
            </button>

            <?php if (!empty($abonos)): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Fecha Abono</th>
                            <th>Método</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($abonos as $ab): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ab['fecha_abono']); ?></td>
                            <td><?php echo htmlspecialchars($ab['m_pagocr']); ?></td>
                            <td><?php echo "$" . number_format($ab['efectivo'], 0, '', ','); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                  <strong>Total Abonado:</strong>
                  <?php echo "$" . number_format($abonosTotal, 0, '', ','); ?>
                </p>
                <p>
                  <strong>Saldo Pendiente:</strong>
                  <?php echo "$" . number_format(($monto_pagado - $abonosTotal), 0, '', ','); ?>
                </p>t
            <?php else: ?>
                <p>No hay abonos registrados todavía.</p>
            <?php endif; ?>
        <?php endif; ?>

    <?php else: ?>
        <!-- Formulario para realizar el pago si no existe en "caja" -->
        <form id="form-pago" method="POST">
            <input type="hidden" name="numero_pedido" value="<?php echo htmlspecialchars($numero_pedido); ?>">
            <input type="hidden" name="tpago" id="total" value="<?php echo $total_a_pagar; ?>">
            <input type="hidden" name="idmeses" id="total" value="<?php echo $idsmese; ?>">
            <input type="hidden" id="nombre_cajero" value="<?= htmlspecialchars($cajeros) ?>">




            <label for="m_pago">Método de Pago:</label>
            <select name="m_pago" id="m_pago" onchange="toggleEfectivoInput()" required>
                <option value="">Seleccionar</option>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="efectivo_transferencia">Efectivo y Transferencia</option>
                <option value="tarjeta_efectivo">Tarjeta y Efectivo</option>
                <option value="credito">Crédito</option>
                <option value="cortesia">Cortesía</option>
                <option value="devolucion">Devolución</option>
            </select>

            <!-- Efectivo -->
            <div id="efectivoInput" style="display: none;">
    <label for="pago">Cantidad de efectivo: </label>
    <input type="number" id="pago" name="pago" value="<?php echo $total_a_pagar; ?>" step="0.01" oninput="calcularCambio()">
    <p id="resultado"></p>
</div>


            <!-- Transferencia -->
            <div id="transferenciaInputs" style="display: none;">
                <label for="banco">Banco:</label>
                <select name="banco" id="banco">
                    <option value="Nequi">Nequi</option>
                    <option value="Bancolombia">Bancolombia</option>
                    <option value="Davivienda">Davivienda</option>
                    <option value="Daviplata">Daviplata</option>
                    <option value="breb">Bre-b</option>
                </select>
                <label for="referencia">Referencia:</label>
                <input type="text" id="referencia" name="referencia">
            </div>

            <!-- Cortesía, Devolución o Crédito -->
            <div id="especialesInputs" style="display: none;">
                <label for="referencia">Detalle:</label>
                <input type="text" id="detalle" name="detalle">
            </div>

            <button type="submit" class="btn btn-primary">Procesar Pago</button>
        </form>
    <?php endif; ?>
</div>


<!-- Modal de confirmación -->
<div class="modal fade" id="confirmationModal" tabindex="-1"
     aria-labelledby="confirmationModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmationModalLabel">Pago Procesado</h5>
      </div>
      <div class="modal-body">
        El pago ha sido procesado e impreso. Haz clic en "Continuar" para finalizar.
      </div>
      <div class="modal-footer">
          <input type="hidden" id="tipo_solicitud" value="<?php echo htmlspecialchars($tipo_solicitud ?? ''); ?>">
        <button type="button" class="btn btn-primary" id="continueButton">Continuar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Agregar Abonos -->
<div class="modal fade" id="modal-abonar" tabindex="-1" role="dialog"
     aria-labelledby="modalAbonarLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title" id="modalAbonarLabel">Agregar Abonos</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
      <div class="modal-body">
        <!-- input oculto para saber el id_credito -->
        <input type="hidden" id="id_credito_hidden" name="id_credito_hidden" value="">

        <div id="abonos-container">
          <!-- Contenedor dinámico de abonos -->
          <div class="abono-row form-row">
            <div class="form-group col-md-7">
              <label>Método de Pago:</label>
              <select class="form-control" name="m_pagocr[]">
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="efectivo_transferencia">Efectivo + Transferencia</option>
                <option value="tarjeta_efectivo">Tarjeta + Efectivo</option>
              </select>
            </div>
            <div class="form-group col-md-5">
              <label>Valor:</label>
              <input type="number" class="form-control" name="efectivo[]" step="0.01">
            </div>
          </div>
          <!-- fin abono-row -->
        </div>

        <button type="button" class="btn btn-secondary" id="btn-agregar-abono">Agregar Abono</button>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-abonos">Guardar Abonos</button>
      </div>
    
    </div>
  </div>
</div>


<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script type="text/javascript" src="https://admin.restaurantechaoguo.com/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    // Verificar si el botón y el campo existen
    var btnMostrarDescuento = document.getElementById('mostrar-descuento-btn');
    var descuentoInput = document.getElementById('descuentoInput');

    if (btnMostrarDescuento && descuentoInput) {
        btnMostrarDescuento.addEventListener('click', function () {
            // Alternar visibilidad del campo de descuento
            descuentoInput.style.display = (descuentoInput.style.display === 'none' || descuentoInput.style.display === '') ? 'block' : 'none';
        });
    } else {
        console.error("No se encontró el botón o el campo de descuento.");
    }
});


function calcularTotalConDescuento() {
    var totalProductos = <?php echo $total_productos; ?>;
    var costoDomicilio = <?php echo $costo_domicilio; ?>;
    var descuento = parseFloat(document.getElementById('descuento').value) || 0;
    
    // Verificar que el descuento no sea mayor al total
    var totalConDomicilio = totalProductos + costoDomicilio;
    if (descuento > totalConDomicilio) {
        alert("El descuento no puede ser mayor que el total.");
        descuento = totalConDomicilio;
        document.getElementById('descuento').value = descuento;
    }

    // Calcular el total a pagar después del descuento
    var totalAPagarConDescuento = totalConDomicilio - descuento;

    // Actualizar el valor mostrado en el HTML
    document.getElementById('total_a_pagar_con_descuento').textContent = "$" + totalAPagarConDescuento.toLocaleString('es-CO', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    // Actualizar el campo oculto que se enviará con el formulario
    document.getElementById('total').value = totalAPagarConDescuento;
}
</script>
<script src="../public/js/caja_tm.js?cache=FRGH"></script>