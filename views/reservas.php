<?php 
// Obtener reservas y detalles de clientes en orden descendente
$query = "
    SELECT r.idreserva, r.evento, r.invita, r.fecha, r.fechareser, r.is_new, c.cliente 
    FROM reservas r
    JOIN clientes c ON r.id_client = c.id
    ORDER BY r.id_r DESC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para obtener el tipo de evento
function getTipoEvento($evento) {
    switch ($evento) {
        case 1: return "Sin decoración";
        case 2: return "Con decoración sencilla";
        case 3: return "Con decoración festón";
        case 4: return "Con rostidecoración";
        default: return "Desconocido";
    }
}

// Marcar todas las reservas como vistas
$updateQuery = "UPDATE reservas SET is_new = 0";
$conn->query($updateQuery);
?>

<div class="container mt-5">
    <h1>Reservas</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nombre del Cliente</th>
                <th>Fecha de Registro</th>
                <th>Fecha de Reserva</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservas as $reserva): ?>
            <tr class="<?php echo $reserva['is_new'] ? 'table-warning' : ''; ?>">
                <td><?php echo htmlspecialchars($reserva['cliente']); ?></td>
                <td><?php echo htmlspecialchars($reserva['fecha']); ?></td>
                <td><?php echo htmlspecialchars($reserva['fechareser']); ?></td>
                <td>
                    <button class="btn btn-info" data-toggle="modal" data-target="#detalleReservaModal" 
                            data-id="<?php echo htmlspecialchars($reserva['idreserva']); ?>"
                            data-cliente="<?php echo htmlspecialchars($reserva['cliente']); ?>"
                            data-evento="<?php echo htmlspecialchars(getTipoEvento($reserva['evento'])); ?>"
                            data-invita="<?php echo htmlspecialchars($reserva['invita']); ?>"
                            data-fecha="<?php echo htmlspecialchars($reserva['fecha']); ?>"
                            data-fechareser="<?php echo htmlspecialchars($reserva['fechareser']); ?>">
                        Ver Detalles
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal para mostrar detalles de la reserva -->
<div class="modal fade" id="detalleReservaModal" tabindex="-1" role="dialog" aria-labelledby="detalleReservaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detalleReservaModalLabel">Detalles de la Reserva</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Cliente:</strong> <span id="modal-cliente"></span></p>
                <p><strong>Evento:</strong> <span id="modal-evento"></span></p>
                <p><strong>Invita:</strong> <span id="modal-invita"></span></p>
                <p><strong>Fecha de Registro:</strong> <span id="modal-fecha"></span></p>
                <p><strong>Fecha de Reserva:</strong> <span id="modal-fechareser"></span></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#detalleReservaModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var cliente = button.data('cliente');
        var evento = button.data('evento');
        var invita = button.data('invita');
        var fecha = button.data('fecha');
        var fechareser = button.data('fechareser');

        var modal = $(this);
        modal.find('#modal-cliente').text(cliente);
        modal.find('#modal-evento').text(evento);
        modal.find('#modal-invita').text(invita);
        modal.find('#modal-fecha').text(fecha);
        modal.find('#modal-fechareser').text(fechareser);
    });
});
</script>
