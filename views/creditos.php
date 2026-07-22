<?php
// Mostrar errores de PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../helpers/Session.php';

Session::start();

$database = new Database();
$conn = $database->getConnection();

// Consulta para obtener la información de creditos junto con el nombre de cliente
$query = "
    SELECT 
        c.idcr,
        c.id_clientecr,
        c.fecha,
        cli.cliente AS nombre_cliente,
        cj.referencia
    FROM creditos c
    LEFT JOIN clientes cli ON c.id_clientecr = cli.id
    LEFT JOIN caja cj ON c.m_pedidocr = cj.id_pedidoc 
    ORDER BY c.id_clientecr, c.fecha
";
$stmt = $conn->prepare($query);
$stmt->execute();

// Agrupar los créditos por cliente
$creditosPorCliente = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $idCliente = $row['id_clientecr'];
    $nombre    = $row['nombre_cliente'] ?: 'Cliente Desconocido';
    $referencia_credito = $row['referencia'];

    if (!isset($creditosPorCliente[$idCliente])) {
        $creditosPorCliente[$idCliente] = [
            'nombre'   => $nombre,
            'referencia'   => $referencia_credito,
            'creditos' => []
        ];
    }
    // Añadimos este crédito al array
    $creditosPorCliente[$idCliente]['creditos'][] = [
        'idcr'  => $row['idcr'],
        'fecha' => $row['fecha']
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Créditos por Cliente</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<style>
        .modal-dialog {
        max-width: 90% !important;
        margin: 1.75rem auto;
    }
</style>
<body>

<div class="container mt-5">
    <h3>Listado de Créditos</h3>
    <?php if (!empty($creditosPorCliente)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Referencia</th>
                    <th>Créditos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($creditosPorCliente as $idCliente => $data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($data['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($data['referencia']); ?></td>
                        <td>
                            <!-- Un solo botón "Ver Créditos" o "Abonar Créditos" por cliente -->
                            <button
                                class="btn btn-info"
                                onclick="verCreditosCliente(<?php echo (int)$idCliente; ?>)">
                                Ver Créditos
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay créditos registrados.</p>
    <?php endif; ?>
</div>

<!-- Modal para ver/abonar TODOS los créditos de un cliente -->
<div class="modal fade" id="modalCreditosCliente" tabindex="-1" role="dialog" aria-labelledby="modalCreditosClienteLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" id="modalCreditosClienteContent">
      <!-- Contenido dinámico via JS -->
    </div>
  </div>
</div>

<!-- Bootstrap JS, jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.min.js"></script>

<script>
// 1) Al hacer clic en "Ver Créditos", llamamos a esta función
function verCreditosCliente(idCliente) {
    // Hacemos un fetch a un endpoint que devuelva TODOS los créditos de ese cliente,
    // Incluyendo "costo" para cada crédito y su array de abonos
    fetch(`../controllers/obtener_creditos_cliente.php?idCliente=${idCliente}`)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // data.creditos => array con [ { idcr, fecha, costo, abonos: [ ... ] }, ... ]
            const creditos = data.creditos || [];

            let html = `
                <div class="modal-header">
                    <h5 class="modal-title">Créditos de ${data.nombreCliente}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                      <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
            `;

            if (creditos.length === 0) {
                html += `<p>Este cliente no tiene créditos.</p>`;
            } else {
                html += `
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th></th>
                      <th>ID Crédito</th>
                      <th>Fecha</th>
                      <th>Costo</th>
                      <th>Abonos Existentes</th>
                      <th>Saldo</th>
                      <th>Nuevo Abono</th>
                    </tr>
                  </thead>
                  <tbody>
                `;

                // Recorremos cada crédito
                creditos.forEach((cr, index) => {
                    // cr.abonos => array con { fecha_abono, m_pagocr, efectivo }
                    // cr.costo => el costo de este crédito
                    let abonosHTML = '';
                    let sumaAbonos = 0;

                    if (cr.abonos && cr.abonos.length > 0) {
                        abonosHTML += '<ul>';
                        cr.abonos.forEach(ab => {
                            abonosHTML += `
                              <li>
                                ${ab.fecha_abono} - Modo: ${ab.m_pagocr} - Valor: ${ab.efectivo}
                              </li>
                            `;
                            sumaAbonos += parseFloat(ab.efectivo || 0);
                        });
                        abonosHTML += '</ul>';
                    } else {
                        abonosHTML = '<p>Sin abonos</p>';
                    }

                    // Calcular saldo
                    const costoCredito = parseFloat(cr.costo || 0);
                    const saldo = costoCredito - sumaAbonos;

                    // Para abonar: agregamos un checkbox + select + input number
                    // Cada fila se identifica con cr.idcr
                    html += `
                    <tr>
                        <td>
                           <input type="checkbox" class="chk-abono" data-idcr="${cr.idcr}">
                        </td>
                        <td>${cr.idcr}</td>
                        <td>${cr.fecha}</td>
                        <td>$${costoCredito.toLocaleString('es-CO')}</td>
                        <td>${abonosHTML}</td>
                        <td>$${saldo.toLocaleString('es-CO')}</td>
                        <td>
                           <select class="form-control sel-metodo" data-idcr="${cr.idcr}">
                             <option value="efectivo">Efectivo</option>
                             <option value="transferencia">Transferencia</option>
                             <option value="tarjeta">Tarjeta</option>
                           </select>
                           <input type="number" class="form-control mt-2 inp-valor" data-idcr="${cr.idcr}" placeholder="Valor Abono">
                        </td>
                    </tr>
                    `;
                });

                html += `
                  </tbody>
                </table>
                <button class="btn btn-primary" onclick="guardarAbonosCliente(${idCliente})">
                  Guardar Abonos
                </button>
                `;
            }

            html += `</div>`; // fin .modal-body
            html += `
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Cerrar
                </button>
            </div>
            `;

            // Inyectar el contenido en #modalCreditosClienteContent
            document.getElementById('modalCreditosClienteContent').innerHTML = html;

            // Mostrar el modal
            $('#modalCreditosCliente').modal('show');

        } else {
            alert(data.message || 'Error al obtener créditos del cliente.');
        }
    })
    .catch(err => {
        console.error('Error al obtener créditos del cliente:', err);
        alert('Error al procesar la solicitud.');
    });
}

// 2) Función para GUARDAR abonos seleccionados
function guardarAbonosCliente(idCliente) {
    // Recorremos la tabla, buscamos cada fila con checkbox "chk-abono" marcado
    // Y leemos su select "sel-metodo" y su input "inp-valor"
    const abonosAProcesar = [];
    const checkboxes = document.querySelectorAll('.chk-abono');

    checkboxes.forEach(chk => {
        if (chk.checked) {
            const idcr = chk.getAttribute('data-idcr');
            // Buscamos el select y el input de esa misma fila
            const sel = document.querySelector(`.sel-metodo[data-idcr="${idcr}"]`);
            const inp = document.querySelector(`.inp-valor[data-idcr="${idcr}"]`);

            const metodo = sel ? sel.value : '';
            const valor  = inp ? parseFloat(inp.value || '0') : 0;

            if (metodo && valor > 0) {
                // Agregamos a la lista de abonos
                abonosAProcesar.push({
                    id_credito: idcr,
                    m_pagocr:   metodo,
                    efectivo:   valor
                });
            }
        }
    });

    if (abonosAProcesar.length === 0) {
        alert('No has seleccionado ningún crédito o no has ingresado valores válidos.');
        return;
    }

    // Enviamos todos los abonos al endpoint que los inserta (ej. abonar_credito_cliente.php)
    fetch('../controllers/abonar_credito_cliente.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ abonos: abonosAProcesar })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Abonos guardados exitosamente.');
            // Cerrar el modal y refrescar la vista, si deseas
            $('#modalCreditosCliente').modal('hide');
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
</script>

</body>
</html>
