<?php
require_once '../config/database.php';

setlocale(LC_TIME, 'es_ES.UTF-8'); 
date_default_timezone_set('America/Bogota');

// Conexión a la BD
$database = new Database();
$conn     = $database->getConnection();

// 1) Fecha seleccionada (o actual) para mostrar gastos
$fecha_seleccionada = isset($_POST['fecha_seleccionada']) && !empty($_POST['fecha_seleccionada'])
    ? $_POST['fecha_seleccionada']
    : date('Y-m-d');

// -------------------------------------------------------------
// 2) Consultar GASTOS de la fecha seleccionada
// -------------------------------------------------------------
$queryGastos = "SELECT * 
                FROM gastos 
                WHERE fecha = ?
                ORDER BY id ASC";
$stmtGastos  = $conn->prepare($queryGastos);
$stmtGastos->execute([$fecha_seleccionada]);
$gastos = $stmtGastos->fetchAll(PDO::FETCH_ASSOC);

// Calcular total de gastos
$total_gastos = array_sum(array_column($gastos, 'monto'));

// -------------------------------------------------------------
// 3) Consultar VALES pendientes (gastos.categoria='vales', estado=0)
// -------------------------------------------------------------
$queryVales = "SELECT g.*, m.nombre_mese
               FROM gastos g
               LEFT JOIN meseros m ON g.id_mesero = m.id_mese
               WHERE g.categoria = 'vales'
                 AND g.estado = 0
               ORDER BY g.fecha DESC";
$stmtVales = $conn->prepare($queryVales);
$stmtVales->execute();
$valesPendientes = $stmtVales->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------------------
// 4) Consultar CREDITOS pendientes (tabla `creditos` + `abono_credito`)
//    Debes tener alguna forma de saber el MONTO TOTAL del crédito
//    Aquí suponemos que lo guardas en `caja` o en `creditos` (no definido en tu modelo).
//    Ejemplo: si no tienes monto, mostrar simplemente la lista de créditos y sus abonos
// -------------------------------------------------------------
$queryCreditos = "
    SELECT 
    c.idcr, 
    c.fecha, 
    c.id_clientecr, 
    c.m_pedidocr,
    cl.cliente,
    COALESCE((
        SELECT SUM(CAST(a.efectivo AS DECIMAL(10,2)))
        FROM abono_credito a
        WHERE a.id_credito = c.idcr
    ), 0) AS total_abonado,
    cl.cliente AS nombre_cliente
FROM creditos c
LEFT JOIN clientes cl 
    ON c.id_clientecr = cl.id
ORDER BY c.idcr DESC;

";
$stmtCred = $conn->prepare($queryCreditos);
$stmtCred->execute();
$creditos = $stmtCred->fetchAll(PDO::FETCH_ASSOC);

// Aquí podrías filtrar sólo los que estén "pendientes" si tuvieras un `monto_credito` - total_abonado > 0
// Ejemplo de filtrado:
$creditosPendientes = [];
foreach ($creditos as $cred) {
    // Suponiendo que no guardas el monto en `creditos`, se omite la validación "pendiente".
    // Si lo tuvieras, harías un if ($monto_credito > $cred['total_abonado']) ...
    $creditosPendientes[] = $cred;
}

// -------------------------------------------------------------
// 5) Lista de meseros (para formulario de vales)
// -------------------------------------------------------------
$queryMeseros = "SELECT * FROM meseros";
$stmtMeseros  = $conn->prepare($queryMeseros);
$stmtMeseros->execute();
$meseros = $stmtMeseros->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------------------
// 6) Función para obtener abonos de un crédito
// -------------------------------------------------------------
function obtenerAbonosCredito($conn, $id_credito) {
    $sql = "SELECT * 
            FROM abono_credito 
            WHERE id_credito = :id_credito
            ORDER BY fecha_abono ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_credito', $id_credito, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// -------------------------------------------------------------
// 7) Mostrar HTML
// -------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gastos y Créditos</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin: 2% 0; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; text-align: left; }
        .input_gastos { width:100%; margin:2% 0; }
        .submit-button { margin:2% 0; }
    </style>
</head>
<body>

<div class="container">
    <div class="row">
        
        <!-- FORMULARIO PARA INSERTAR GASTOS -->
        <div class="col-sm-12 col-md-4">
            <h2>Registrar Gasto</h2>
            <form id="gastos-form" method="post" action="../controllers/gastos_controller.php">
                <div class="form-group">
                    <label for="fecha">Fecha:</label>
                    <input class="input_gastos" type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="concepto">Concepto:</label>
                    <textarea class="input_gastos" id="concepto" name="concepto" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select class="input_gastos" id="categoria" name="categoria" onchange="mostrarCampoMeseros()">
                        <option value="gastos_varios">Gastos Varios</option>
                        <option value="nomina">Nómina</option>
                        <option value="vales">Vales</option>
                        <option value="proveedores">Pago Proveedores</option>
                    </select>
                </div>

                <div class="form-group" id="campoMeseros" style="display: none;">
                    <label for="mesero">Para quien es el vale:</label>
                    <select class="input_gastos" id="mesero" name="mesero">
                        <?php foreach ($meseros as $m): ?>
                            <option value="<?php echo $m['id_mese']; ?>">
                                <?php echo htmlspecialchars($m['nombre_mese']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="monto">Monto:</label>
                    <input class="input_gastos" type="number" id="monto" name="monto" required>
                </div>

                <!-- Campo oculto para cajero (si es necesario) -->
                <input type="hidden" id="cajero" name="cajero" value="<?php echo $cajero ?>">

                <input type="submit" value="Ingresar Gasto" class="submit-button btn btn-primary">
            </form>

            <hr>

            <!-- FORM para seleccionar fecha de GASTOS -->
            <h4>Seleccione una fecha de Gastos</h4>
            <form method="post" action="">
                <label for="fecha_seleccionada">Fecha:</label>
                <input class="input_gastos" type="date" id="fecha_seleccionada" name="fecha_seleccionada"
                       value="<?php echo $fecha_seleccionada; ?>" onchange="this.form.submit()">
            </form>
        </div>

        <!-- SECCIÓN DE TABLAS -->
        <div class="col-sm-12 col-md-8">
            <h2>Gastos y Créditos</h2>

            <!-- Nav Tabs -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="gastos-tab" data-toggle="tab" href="#gastosTab" role="tab">Gastos del día</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="vales-tab" data-toggle="tab" href="#valesTab" role="tab">Vales Pendientes</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="creditos-tab" data-toggle="tab" href="#creditosTab" role="tab">Créditos Pendientes</a>
              </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="myTabContent">
              
              <!-- GASTOS DIARIOS -->
              <div class="tab-pane fade show active" id="gastosTab" role="tabpanel">
                <h3>Gastos del <?php echo htmlspecialchars($fecha_seleccionada); ?></h3>
                <table>
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Concepto</th>
                      <th>Categoría</th>
                      <th>Monto</th>
                      <th>Cajero</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if (!empty($gastos)): ?>
                    <?php foreach ($gastos as $g): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($g['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($g['concepto']); ?></td>
                        <td><?php echo htmlspecialchars($g['categoria']); ?></td>
                        <td><?php echo "$" . number_format($g['monto'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($g['cajero'] ?: 'N/A'); ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <tr>
                      <th colspan="3">Total:</th>
                      <td colspan="2"><?php echo "$" . number_format($total_gastos, 2, ',', '.'); ?></td>
                    </tr>
                  <?php else: ?>
                    <tr><td colspan="5">No hay gastos para esta fecha</td></tr>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <!-- VALES PENDIENTES -->
              <div class="tab-pane fade" id="valesTab" role="tabpanel">
                <h3>Vales Pendientes</h3>
                <table>
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Concepto</th>
                      <th>Monto</th>
                      <th>Mesero</th>
                      <th>Cajero</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if (!empty($valesPendientes)): ?>
                    <?php foreach ($valesPendientes as $v): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($v['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($v['concepto']); ?></td>
                        <td><?php echo "$" . number_format($v['monto'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($v['nombre_mese'] ?: ''); ?></td>
                        <td><?php echo htmlspecialchars($v['cajero'] ?: ''); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="5">No hay vales pendientes</td></tr>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <!-- CRÉDITOS PENDIENTES -->
              <div class="tab-pane fade" id="creditosTab" role="tabpanel">
                <h3>Créditos Pendientes</h3>
                <table>
                  <thead>
                    <tr>
                      <th>ID Crédito</th>
                      <th>Fecha</th>
                      <th>ID Cliente</th>
                      <th>m_pedidocr</th>
                      <th>Total Abonado</th>
                      <th>Abonos</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if (!empty($creditosPendientes)): ?>
                    <?php foreach ($creditosPendientes as $c): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($c['idcr']); ?></td>
                        <td><?php echo htmlspecialchars($c['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($c['cliente']); ?></td>
                        <td><?php echo htmlspecialchars($c['m_pedidocr']); ?></td>
                        <td><?php echo "$" . number_format($c['total_abonado'], 2, ',', '.'); ?></td>
                        <td>
                          <!-- Botón para ver abonos en un modal -->
                          <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                  data-target="#creditoModal-<?php echo $c['idcr']; ?>">
                            Ver Abonos
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="6">No hay créditos pendientes</td></tr>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>

            </div> <!-- /tab-content -->
        </div> <!-- /col-md-8 -->
    </div> <!-- /row -->
</div> <!-- /container -->

<!-- MODALES para los abonos de cada crédito -->
<?php foreach ($creditosPendientes as $cp): ?>
<?php
  $abonos = obtenerAbonosCredito($conn, $cp['idcr']);
?>
<div class="modal fade" id="creditoModal-<?php echo $cp['idcr']; ?>" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Abonos del Crédito ID: <?php echo $cp['idcr']; ?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <?php if (!empty($abonos)): ?>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>ID Abono</th>
                <th>Método de Pago</th>
                <th>Valor</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($abonos as $ab): ?>
              <tr>
                <td><?php echo $ab['id']; ?></td>
                <td><?php echo htmlspecialchars($ab['m_pagocr']); ?></td>
                <td><?php echo "$" . number_format($ab['efectivo'], 2, ',', '.'); ?></td>
                <td><?php echo htmlspecialchars($ab['fecha_abono']); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No hay abonos para este crédito.</p>
        <?php endif; ?>
        <!-- Formulario para crear nuevo abono al crédito -->
        <form action="../controllers/abonar_credito.php" method="POST">
          <input type="hidden" name="id_credito" value="<?php echo $cp['idcr']; ?>">
          <div class="form-group">
            <label for="m_pagocr">Método de Pago:</label>
            <select name="m_pagocr" id="m_pagocr" class="form-control">
              <option value="efectivo">Efectivo</option>
              <option value="transferencia">Transferencia</option>
              <option value="tarjeta">Tarjeta</option>
            </select>
          </div>
          <div class="form-group">
            <label for="efectivo">Valor Abono:</label>
            <input type="number" name="efectivo" id="efectivo" class="form-control" min="1" required>
          </div>
          <button type="submit" class="btn btn-success">Guardar Abono</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script>
function mostrarCampoMeseros() {
    const categoria = document.getElementById('categoria').value;
    const campoMeseros = document.getElementById('campoMeseros');
    if (categoria === 'vales') {
        campoMeseros.style.display = 'block';
    } else {
        campoMeseros.style.display = 'none';
    }
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
