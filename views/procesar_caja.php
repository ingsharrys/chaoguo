<?php
// Mostrar errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php'; 
$database = new Database();
$conn = $database->getConnection();

// Variable para la sumatoria total de todos los pedidos
$sumatoria_total_pedidos = 0;
$all_paid = true; // Determina si todos los pedidos están pagados

// Verificar si se recibieron datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedidos']) && is_array($_POST['pedidos'])) {
    $pedidos = $_POST['pedidos']; // Recibe los pedidos como un arreglo

    foreach ($pedidos as $numero_pedido) {

        // Obtener detalles del pedido **SIEMPRE** desde `turnero`
      $query_pedido = "
    SELECT 
        pr.prefijo AS prefijo, 
        pr.nombre AS nombre_producto, 
        p.cantidad, 
        p.tipo_solicitud, 
        prp.precio AS precio_producto, 
        p.detalle, 
        p.tipo_producto, 
        c.cliente AS nombre_cliente, 
        c.celular AS celular_cliente,
        c.direccion AS direccion_cliente
    FROM pedidos p 
    JOIN productos pr ON p.id_pro = pr.id_pro
    LEFT JOIN precios prp ON p.id_pro = prp.idproduc AND p.tipo_producto = prp.tipo_prod
    JOIN turnero t ON p.numero_pedido = t.id_pedido
    LEFT JOIN clientes c ON t.id_cliente = c.id
    WHERE p.numero_pedido = ?
";

$stmt_pedido = $conn->prepare($query_pedido);
$stmt_pedido->bindValue(1, $numero_pedido, PDO::PARAM_INT);
$stmt_pedido->execute();
$detalles_pedido = $stmt_pedido->fetchAll(PDO::FETCH_ASSOC);


        if (!$detalles_pedido) {
            echo "<p>No se encontraron detalles para el pedido $numero_pedido.</p>";
            continue;
        }

        // Obtener datos del cliente
        $nombre_cliente = $detalles_pedido[0]['nombre_cliente'] ?? 'Sin nombre';
        $celular_cliente = $detalles_pedido[0]['celular_cliente'] ?? 'Sin celular';
        $direccion_cliente = $detalles_pedido[0]['direccion_cliente'] ?? 'Sin dirección';

        // Obtener el tipo de solicitud
        $tipo_solicitud = $detalles_pedido[0]['tipo_solicitud'] ?? '';

        // Inicializar costo de domicilio
        $costo_domicilio = 0;

        if ($tipo_solicitud == 50) {
            // Consultar el costo del domicilio
            $query_domicilio = "SELECT precio FROM domicilios WHERE id_pedido = :id_pedido";
            $stmt_domicilio = $conn->prepare($query_domicilio);
            $stmt_domicilio->bindParam(':id_pedido', $numero_pedido, PDO::PARAM_STR);
            $stmt_domicilio->execute();
            $resultado_domicilio = $stmt_domicilio->fetch(PDO::FETCH_ASSOC);

            if ($resultado_domicilio) {
                $costo_domicilio = (float)$resultado_domicilio['precio'];
            }
        }

        // Calcular el total de productos
        $total_productos = array_reduce($detalles_pedido, function($carry, $item) {
            return $carry + ($item['precio_producto'] * $item['cantidad']);
        }, 0);

        // Calcular el total a pagar
         $total_a_pagar = $total_productos + $costo_domicilio;
        //$total_a_pagar = $total_productos;
        // Sumar a la sumatoria total
        $sumatoria_total_pedidos += $total_a_pagar;

        // Verificar si ya ha sido pagado
        $query_check_pago = "SELECT COUNT(*) as count FROM caja WHERE id_pedidoc = :id_pedidoc";
        $stmt_check_pago = $conn->prepare($query_check_pago);
        $stmt_check_pago->bindParam(':id_pedidoc', $numero_pedido, PDO::PARAM_STR);
        $stmt_check_pago->execute();
        $pago_existente = $stmt_check_pago->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        ?>

        <div class="container mt-5">
            <h1>Pago del Pedido <?php echo htmlspecialchars($numero_pedido); ?></h1>

            <h2>Detalles del Pedido</h2>
            <table class="table table-bordered">
                <thead>
                    <tr>
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

            <p><strong>Total a Pagar:</strong> <?php echo "$" . number_format($total_a_pagar, 0, '', ','); ?></p>

            <!-- Datos del Cliente -->
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($nombre_cliente); ?></p>
            <p><strong>Celular:</strong> <?php echo htmlspecialchars($celular_cliente); ?></p>
            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($direccion_cliente); ?></p>

            <!-- Mostrar formulario o botón "Pagado" -->
            <?php if ($pago_existente): ?>
                <!-- Si ya ha sido pagado, mostrar el botón "Pagado" -->
                <button class="btn btn-success" disabled>Pagado</button>
            <?php else: ?>
                <!-- Si no ha sido pagado, mostrar el formulario -->
                <form>
                    <input type="hidden" name="pedidos[]" value="<?php echo htmlspecialchars($numero_pedido); ?>">
                    <input type="hidden" id="total_<?php echo $numero_pedido; ?>" value="<?php echo $total_a_pagar; ?>">

                    <label for="m_pago_<?php echo $numero_pedido; ?>">Método de Pago:</label>
                    <select name="m_pago_<?php echo $numero_pedido; ?>" id="m_pago_<?php echo $numero_pedido; ?>" data-pedido="<?php echo $numero_pedido; ?>" required>
                        <option value="">Seleccionar</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="efectivo_transferencia">Efectivo y Transferencia</option>
                        <option value="tarjeta_efectivo">Tarjeta y Efectivo</option>
                        <option value="devolucion">Devolución</option>
                    </select>

                    <!-- Campo para ingresar cantidad de efectivo -->
                    <div id="efectivoInput_<?php echo $numero_pedido; ?>" style="display: none;">
                        <label for="pago_<?php echo $numero_pedido; ?>">Cantidad de efectivo: </label>
                        <input type="number" id="pago_<?php echo $numero_pedido; ?>" value="<?php echo $total_a_pagar; ?>" name="pago_<?php echo $numero_pedido; ?>" step="0.01" data-pedido="<?php echo $numero_pedido; ?>">
                        <p id="resultado_<?php echo $numero_pedido; ?>"></p>
                    </div>

                    <!-- Campos para banco y referencia, solo para transferencia o efectivo y transferencia -->
                    <div id="transferenciaInputs_<?php echo $numero_pedido; ?>" style="display: none;">
                        <label for="banco_<?php echo $numero_pedido; ?>">Banco:</label>
                        <select name="banco_<?php echo $numero_pedido; ?>" id="banco_<?php echo $numero_pedido; ?>">
                            <option value="Nequi">Nequi</option>
                            <option value="Bancolombia">Bancolombia</option>
                            <option value="Davivienda">Davivienda</option>
                            <option value="Daviplata">Daviplata</option>
                        </select>

                        <label for="referencia_<?php echo $numero_pedido; ?>">Referencia:</label>
                        <input type="text" id="referencia_<?php echo $numero_pedido; ?>" name="referencia_<?php echo $numero_pedido; ?>">
                    </div>
                </form>
                <?php $all_paid = false; // Si algún pedido no está pagado, cambiar la variable ?>
            <?php endif; ?>
        </div>
        <?php
    }
} else {
    echo "<p>No se seleccionaron pedidos para procesar.</p>";
}
?>
<div class="container mt-5">
    <h3>Sumatoria Total Ingresada en Efectivo: <span id="sumatoria_total_efectivo"><?php echo "$" . number_format(0, 0, '', ','); ?></span></h3>
    <?php if (!$all_paid): ?>
        <button class="btn btn-primary" onclick="procesarTodosLosPagos()">Procesar Todos los Pagos</button>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('select[name^="m_pago_"]').forEach(select => {
        select.addEventListener('change', toggleEfectivoInput);
    });

    document.querySelectorAll('input[name^="pago_"]').forEach(input => {
        input.addEventListener('input', calcularCambio);
    });

    function toggleEfectivoInput(event) {
        const numeroPedido = event.target.dataset.pedido;
        const metodoPago = document.getElementById(`m_pago_${numeroPedido}`).value;
        const efectivoInput = document.getElementById(`efectivoInput_${numeroPedido}`);
        const transferenciaInputs = document.getElementById(`transferenciaInputs_${numeroPedido}`);

        if (metodoPago === 'efectivo' || metodoPago === 'efectivo_transferencia' || metodoPago === 'tarjeta_efectivo') {
            efectivoInput.style.display = 'block';
        } else {
            efectivoInput.style.display = 'none';
            document.getElementById(`pago_${numeroPedido}`).value = '';
        }

        if (metodoPago === 'transferencia' || metodoPago === 'efectivo_transferencia') {
            transferenciaInputs.style.display = 'block';
        } else {
            transferenciaInputs.style.display = 'none';
        }

        // Actualizar la sumatoria total después de cualquier cambio en el método de pago
        recalcularSumatoria();
    }

    function calcularCambio(event) {
        const numeroPedido = event.target.dataset.pedido;
        var totalElement = document.getElementById(`total_${numeroPedido}`);
        var pagoElement = document.getElementById(`pago_${numeroPedido}`);
        var resultadoElement = document.getElementById(`resultado_${numeroPedido}`);
        var metodoPago = document.getElementById(`m_pago_${numeroPedido}`).value;

        if (!totalElement || !pagoElement || !resultadoElement) {
            return;
        }

        var total = parseFloat(totalElement.value);
        var pago = parseFloat(pagoElement.value || 0);
        var resultado = resultadoElement;

        var formateadorMoneda = new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });

        if (metodoPago === 'efectivo') {
            if (pago < total) {
                var restante = total - pago;
                resultado.textContent = "Restante por pagar: " + formateadorMoneda.format(restante);
            } else if (pago === total) {
                resultado.textContent = "Pagado completamente en efectivo.";
            } else {
                var cambio = pago - total;
                resultado.textContent = "Cambio a devolver: " + formateadorMoneda.format(cambio);
            }
        }

        // Recalcular la sumatoria después de cualquier cambio en el campo de pago
        recalcularSumatoria();
    }

    // Función para recalcular la sumatoria total de efectivo
    function recalcularSumatoria() {
        let sumatoria = 0;

        document.querySelectorAll('input[name^="pago_"]').forEach(input => {
            const valorPago = parseFloat(input.value) || 0;
            sumatoria += valorPago;
        });

        document.getElementById('sumatoria_total_efectivo').textContent = new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(sumatoria);
    }

    window.procesarTodosLosPagos = function() {
        let pedidos = [];
        const cajero = "<?php echo Session::get('cajero'); ?>"; 
        const id_cajero = "<?php echo $idsmese; ?>"; 
        

        document.querySelectorAll('select[name^="m_pago_"]').forEach(select => {
            const numeroPedido = select.dataset.pedido;
            const metodoPago = document.getElementById(`m_pago_${numeroPedido}`).value;
            const totalAPagar = document.getElementById(`total_${numeroPedido}`).value;
            const pago = document.getElementById(`pago_${numeroPedido}`).value || null;
            let banco = null;
            let referencia = null;

            if (metodoPago === 'transferencia' || metodoPago === 'efectivo_transferencia' || metodoPago === 'tarjeta_efectivo') {
                banco = document.getElementById(`banco_${numeroPedido}`).value || null;
                referencia = document.getElementById(`referencia_${numeroPedido}`).value || null;
            }

            pedidos.push({
                id_pedidoc: numeroPedido,
                costo: totalAPagar,
                m_pago: metodoPago,
                efectivo: pago || null,
                banco: banco || null,
                referencia: referencia || null,
                cajero: cajero,
                id_cajero: id_cajero
            });
        });

        fetch('../controllers/process_pagos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ pedidos })
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('Pagos procesados correctamente.');
                  location.reload();
              } else {
                  alert('Error procesando los pagos.');
              }
          }).catch(error => {
              console.error('Error:', error);
          });
    };
});
</script>
