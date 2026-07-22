<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Establecer la zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Obtener la fecha actual
$fechaActual = isset($_POST['fecha_seleccionada']) && !empty($_POST['fecha_seleccionada']) 
    ? $_POST['fecha_seleccionada'] 
    : date('Y-m-d');

// Obtener el cajero seleccionado (si no hay cajero seleccionado, mostrar el consolidado por defecto)
$cajeroSeleccionado = isset($_POST['cajero']) && !empty($_POST['cajero']) 
    ? $_POST['cajero'] 
    : 'consolidado';

// 1) CONSULTAR “meseros” para obtener la lista de cajeros (cargo='cajero')
$cajerosDb = [];
try {
    $queryMeseros = "SELECT nombre_mese FROM meseros WHERE cargo = 'cajero'";
    $stmtMeseros  = $conn->prepare($queryMeseros);
    $stmtMeseros->execute();
    while($row = $stmtMeseros->fetch(PDO::FETCH_ASSOC)) {
        $cajerosDb[] = $row['nombre_mese']; // Por ejemplo 'Catalina', 'Majo', etc.
    }
} catch (Exception $e) {
    echo "Error consultando meseros: " . $e->getMessage();
    exit;
}

// Función para filtrar productos por método de pago y cajero
function filtrarTurnosPorFechaYMetodoPago($conn, $metodoPago, $fechaActual, $cajeroSeleccionado) {
    $queryTurnos = "
            SELECT 
                t.id_pedido, 
                t.turno, 
                t.fecha, 
                t.tipo_solicitud, 
                t.estado, 
                t.id_cliente,
                c.m_pago, 
                c.cajero,
                c.banco,  
                c.costo,
                c.efectivo
            FROM turnero t
            LEFT JOIN caja c ON c.id_pedidoc = t.id_pedido
            WHERE DATE(t.fecha) = :fechaActual
            ";

    // Si se selecciona un metodo de pago
    if ($metodoPago !== 'consolidado') {
        $queryTurnos .= " AND c.m_pago = :metodo_pago";
    }

    // Si se selecciona un cajero específico, añadir una cláusula para filtrar por cajero
    if ($cajeroSeleccionado !== 'consolidado') {
        $queryTurnos .= " AND c.cajero = :cajeroSeleccionado";
    }

    $queryTurnos .= " ORDER BY t.fecha ASC";

    $stmtTurnos = $conn->prepare($queryTurnos);
    $stmtTurnos->bindParam(':fechaActual', $fechaActual);

    if ($metodoPago !== 'consolidado') {
        $stmtTurnos->bindParam(':metodo_pago', $metodoPago);
    }
    if ($cajeroSeleccionado !== 'consolidado') {
        $stmtTurnos->bindParam(':cajeroSeleccionado', $cajeroSeleccionado);
    }

    $stmtTurnos->execute();
    return $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener los turnos para cada método de pago
$metodosPago = [
    'consolidado', 
    'efectivo', 
    'transferencia', 
    'tarjeta', 
    'efectivo_transferencia', 
    'tarjeta_efectivo', 
    'devolucion'
];

$turnosPorMetodo = [];

foreach ($metodosPago as $metodo) {
    $turnosPorMetodo[$metodo] = filtrarTurnosPorFechaYMetodoPago($conn, $metodo, $fechaActual, $cajeroSeleccionado);
}

// Inicializar variables para el resumen de ventas
$totalRecibidoEfectivo = 0;
$totalEfectivo = 0;
$total_efectivo_abonos = 0;
$total_nomina = 0;
$totalRecibidoTarjeta = 0;
$totalTarjeta = 0;
$total_tarjeta_abonos = 0;
$totalRecibidoTransferencia = 0;
$totalTransferencia = 0;
$total_transferencia_abonos = 0;
$totalDevolucion = 0;

// Array para evitar duplicados
$procesados = [];

// Calcular los totales
foreach ($turnosPorMetodo as $metodo => $turnos) {
    foreach ($turnos as $registro) {
        $idPedido = $registro['id_pedido'] ?? null;

        // Verificar si el pedido ya fue procesado
        if (isset($procesados[$idPedido])) {
            continue; // Saltar este registro si ya fue procesado
        }

        // Marcar el pedido como procesado
        $procesados[$idPedido] = true;

        $costo = $registro['costo'] ?? 0;
        $efectivo = $registro['efectivo'] ?? 0;
        $metodoPago = $registro['m_pago'] ?? '';

        if ($metodoPago == 'efectivo') {
            // Si el método de pago es "efectivo", sumar el costo al total vendido en efectivo
            $totalEfectivo += $costo;
            $totalRecibidoEfectivo += $costo; // También sumar al total recibido en efectivo
        } elseif ($metodoPago == 'efectivo_transferencia' || $metodoPago == 'tarjeta_efectivo') {
            // Si el método de pago es combinado, sumar solo el campo "efectivo" al total recibido en efectivo
            $totalRecibidoEfectivo += $efectivo;
        } elseif ($metodoPago == 'tarjeta') {
            // Si el método de pago es "tarjeta", sumar el costo al total vendido por tarjeta
            $totalTarjeta += $costo;
            $totalRecibidoTarjeta += $costo;
        } elseif ($metodoPago == 'transferencia') {
            // Si el método de pago es "transferencia", sumar el costo al total vendido por transferencia
            $totalTransferencia += $costo;
            $totalRecibidoTransferencia += $costo;
        } elseif ($metodoPago == 'devolucion') {
            // Si el método de pago es "devolucion", sumar el costo al total de devoluciones
            $totalDevolucion += $costo;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Turnos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .scroll-box {
            width: 100%;
            height: 500px; /* Ajusta la altura según lo que necesites */
            overflow-x: auto;
            overflow-y: auto;
        }
    </style>
</head>
<body>
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
                <!-- Opción Consolidado -->
                <option value="consolidado"
                  <?php echo ($cajeroSeleccionado=='consolidado'?'selected':''); ?>>
                  Consolidado (Todos)
                </option>
                <!-- Recorrer los cajeros de la DB -->
                <?php foreach ($cajerosDb as $cajeroNombre): ?>
                    <option
                      value="<?php echo htmlspecialchars($cajeroNombre); ?>"
                      <?php echo ($cajeroSeleccionado==$cajeroNombre ? 'selected' : ''); ?>
                    >
                      <?php echo htmlspecialchars($cajeroNombre); ?>
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

        <button type="submit" class="btn btn-primary">Actualizar Reporte</button>
        <button class="btn btn-primary mb-3" onclick="imprimirDatos()">Imprimir Datos</button>
    </form>
    
    <!-- Resumen de ventas -->
    <div class="card my-4">
        <div class="card-body">
            <h4>Total Recibido en Efectivo: $<?php echo number_format($totalRecibidoEfectivo ?? 0, 2); ?></h4>
            <ul>
                <li>Total vendido en efectivo: $<?php echo number_format($totalEfectivo ?? 0, 2); ?></li>
                <li>Total credito en efectivo: $<?php echo number_format($total_efectivo_abonos ?? 0, 2); ?></li>
                <li>Total pagado en nomina: $<?php echo number_format($total_nomina ?? 0, 2); ?></li>
            </ul>

            <h4>Total Recibido en Tarjetas: $<?php echo number_format($totalRecibidoTarjeta ?? 0, 2); ?></h4>
            <ul>
                <li>Total vendido por tarjeta: $<?php echo number_format($totalTarjeta ?? 0, 2); ?></li>
                <li>Total credito por tarjeta: $<?php echo number_format($total_tarjeta_abonos ?? 0, 2); ?></li>
            </ul>

            <h4>Total Recibido en Transferencias: $<?php echo number_format($totalRecibidoTransferencia ?? 0, 2); ?></h4>
            <ul>
                <li>Total vendido por transferencia: $<?php echo number_format($totalTransferencia ?? 0, 2); ?></li>
                <li>Total credito por transferencia: $<?php echo number_format($total_transferencia_abonos ?? 0, 2); ?></li>
            </ul>

            <h4>Total en Devoluciones: $<?php echo number_format($totalDevolucion ?? 0, 2); ?></h4>
        </div>
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
                    <?php endforeach; ?>
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
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    function imprimirDatos() {
        let contenido = document.querySelector('.tab-content').innerHTML;
        let ventanaImpresion = window.open('', '', 'height=600,width=800');
        ventanaImpresion.document.write('<html><head><title>Impresión</title>');
        ventanaImpresion.document.write('<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">');
        ventanaImpresion.document.write('</head><body>');
        ventanaImpresion.document.write(contenido);
        ventanaImpresion.document.write('</body></html>');
        ventanaImpresion.document.close();
        ventanaImpresion.print();
    }

// Script para cargar el detalle del pedido en el modal
$('#detalleModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var pedidoId = button.data('pedido');
    console.log(pedidoId);

    // Limpiar el contenido anterior antes de hacer la solicitud
    $('#detallePedidoContent').html('');

    // Hacer la solicitud AJAX para obtener el detalle del pedido
    $.ajax({
        url: '../controllers/detalle_pedido.php', // Cambia esta URL al archivo que obtenga el detalle del pedido
        method: 'GET',
        data: { id_pedido: pedidoId },
        success: function(response) {
            $('#detallePedidoContent').html(response);
        },
        error: function() {
            $('#detallePedidoContent').html('<p>Error al cargar el detalle del pedido.</p>');
        }
    });
});

</script>

</body>
</html>