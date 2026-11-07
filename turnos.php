<?php
require_once 'config/database.php';

// Configurar la zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Crear una instancia de la base de datos y obtener la conexi贸n
$db = new Database();
$conn = $db->getConnection();

// Obtener la fecha actual en formato Y-m-d
$fecha_actual = date('Y-m-d');
$fecha_fin    = date('Y-m-d', strtotime($fecha_actual . ' +1 day'));

// Obtener los pedidos de la fecha actual.
// Rango de fechas (no DATE(t.fecha)=...) para usar idx_turnero_fecha_tipo;
// EXISTS en vez de JOIN a pedidos (no multiplica filas: ya no hace falta
// DISTINCT) y pagado via EXISTS a caja (usa idx_caja_pedido). Esta página
// se refresca sola todo el día en la pantalla de cocina: cada consulta
// debe ser puntual, no un escaneo del histórico.
$query = "
  SELECT t.id_t, t.id_pedido, t.turno, t.fecha, t.tipo_solicitud, t.estado,
       c.cliente, c.celular, c.email, c.direccion,
       EXISTS(SELECT 1 FROM caja ca WHERE ca.id_pedidoc = t.id_pedido) AS pagado
FROM turnero t
JOIN clientes c ON t.id_cliente = c.id
WHERE t.fecha >= :fecha_ini AND t.fecha < :fecha_fin
  AND t.tipo_solicitud IN (51, 53)
  AND EXISTS (SELECT 1 FROM pedidos p WHERE p.numero_pedido = t.id_pedido)
ORDER BY t.tipo_solicitud, t.turno ASC,
         FIELD(t.estado, 'nuevo', 'en_cocina', 'entregado'), t.fecha DESC

";

$stmt = $conn->prepare($query);
$stmt->bindParam(':fecha_ini', $fecha_actual);
$stmt->bindParam(':fecha_fin', $fecha_fin);
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separar los turnos en dos arrays
$turnos = [];
$call   = [];

foreach ($pedidos as $pedido) {
    if ($pedido['tipo_solicitud'] == 51) {
        $turnos[] = $pedido;
    } elseif ($pedido['tipo_solicitud'] == 53) {
        $call[] = $pedido;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Turnos</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    .container {
      max-width: 100%;
    }
    h2 {
      font-size: 1.8rem;
      text-align: center;
      margin-bottom: 20px;
      font-weight: bold;
    }

    .row { margin-bottom: 20px; }

    /* Botones de estado */
    .btn-estado {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      height: auto;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      word-wrap: break-word;
      white-space: normal; /* Permite que el texto pase a la siguiente l铆nea */
    }
    .btn-nuevo {
      background-color: red;
      color: white;
    }
    .btn-en-cocina {
      background-color: yellow;
      color: black;
    }
    .btn-entregado {
      background-color: green;
      color: white;
    }

    /* Ajuste para el contenido */
    .btn-estado p {
      font-size: 1.2rem;
      margin: 0;
      padding: 2px;
    }
    .btn-estado span {
      font-size: 1rem;
      margin: 0;
      padding: 2px;
    }
    .btn-estado small {
      font-size: 0.9rem;
      margin: 0;
    }

    .cronometro {
      font-size: 1rem;
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="container mt-5">
  <div class="row">
    <!-- Columna para los Turnos -->
    <div class="col-lg-7 col-md-12">
      <h2>Turnos</h2>
      <div class="row" id="turnos-container">
      <?php if (count($turnos) > 0): ?>
        <?php foreach ($turnos as $pedido): ?>
          <?php if (!($pedido['estado'] === 'entregado' && $pedido['pagado'] == 1)): ?>
            <div class="col-md-4 mb-3">
              <button 
                class="btn btn-estado
                  <?php 
                    echo $pedido['estado'] === 'entregado' ? 'btn-entregado' :
                         ($pedido['estado'] === 'en_cocina' ? 'btn-en-cocina' : 'btn-nuevo'); 
                  ?>"
                data-id="<?php echo $pedido['id_pedido']; ?>" 
                data-fecha="<?php echo $pedido['fecha']; ?>" 
                id="button-<?php echo $pedido['id_pedido']; ?>">

                <p style="font-size:1.6rem;"># <?php echo $pedido['turno']; ?></p>
                <p><?php echo $pedido['cliente']; ?></p> <!-- Nombre del cliente -->
                <?php if ($pedido['estado'] !== 'entregado'): ?>
                  <span class="cronometro" data-fecha="<?php echo $pedido['fecha']; ?>" id="cronometro-<?php echo $pedido['id_pedido']; ?>">
                    00:00:00
                  </span>
                <?php else: ?>
                  <small>Entregado</small>
                <?php endif; ?>
                
                <?php if ($pedido['estado'] === 'en_cocina'): ?>
                  <small>En cocina</small>
                <?php endif; ?>
              </button>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay turnos disponibles.</p>
      <?php endif; ?>
      </div>
    </div>

    <!-- Columna para los Call -->
    <div class="col-lg-5 col-md-12">
      <h2>Call</h2>
      <div class="row" id="call-container">
      <?php if (count($call) > 0): ?>
        <?php foreach ($call as $pedido): ?>
          <?php if (!($pedido['estado'] === 'entregado' && $pedido['pagado'] == 1)): ?>
            <div class="col-md-6 mb-3">
              <button 
                class="btn btn-estado
                  <?php 
                    echo $pedido['estado'] === 'entregado' ? 'btn-entregado' :
                         ($pedido['estado'] === 'en_cocina' ? 'btn-en-cocina' : 'btn-nuevo'); 
                  ?>"
                data-id="<?php echo $pedido['id_pedido']; ?>" 
                data-fecha="<?php echo $pedido['fecha']; ?>" 
                id="button-<?php echo $pedido['id_pedido']; ?>">

                <p style="font-size:1.6rem;"># <?php echo $pedido['turno']; ?></p>
                <p><?php echo $pedido['cliente']; ?></p> 
                <?php if ($pedido['estado'] !== 'entregado'): ?>
                  <span class="cronometro" data-fecha="<?php echo $pedido['fecha']; ?>" id="cronometro-<?php echo $pedido['id_pedido']; ?>">
                    00:00:00
                  </span>
                <?php else: ?>
                  <small>Entregado</small>
                <?php endif; ?>

                <?php if ($pedido['estado'] === 'en_cocina'): ?>
                  <small>En cocina</small>
                <?php endif; ?>
              </button>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay llamadas disponibles.</p>
      <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function actualizarTurnos() {
        $.ajax({
            url: 'turnos.php',
            method: 'GET',
            success: function(data) {
                // Vaciar contenedores
                $('#turnos-container').empty();
                $('#call-container').empty();

                // Extraer HTML parcial
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');

                // Reemplazar contenido
                const turnosHTML = doc.querySelector('#turnos-container');
                const callHTML   = doc.querySelector('#call-container');

                if (turnosHTML) {
                    $('#turnos-container').html(turnosHTML.innerHTML);
                }
                if (callHTML) {
                    $('#call-container').html(callHTML.innerHTML);
                }
                // Los cronómetros los actualiza el intervalo global de 1 s
            }
        });
    }

    /* Un ÚNICO cronómetro global de 1 s para TODOS los turnos.
       Antes cada refresco creaba un setInterval nuevo por elemento sin
       limpiar los anteriores: miles de timers acumulados al día que
       degradaban la pestaña de cocina hasta "ponerse lenta". */
    setInterval(function() {
        document.querySelectorAll('.cronometro').forEach(function(element) {
            const fechaPedido = new Date(element.getAttribute('data-fecha'));
            const diff  = new Date() - fechaPedido; // en milisegundos

            const horas    = Math.floor(diff / 3600000);
            const minutos  = Math.floor((diff % 3600000) / 60000);
            const segundos = Math.floor((diff % 60000) / 1000);

            element.innerText = `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
        });
    }, 1000);

    // Actualizar cada 15 seg (antes 10: menos carga al servidor sin
    // perder frescura en cocina)
    setInterval(actualizarTurnos, 15000);
});
</script>
</body>
</html>
