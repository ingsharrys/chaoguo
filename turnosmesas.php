<?php
require_once 'config/database.php';

// Configurar la zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Crear una instancia de la base de datos y obtener la conexión
$db = new Database();
$conn = $db->getConnection();

// Consulta SQL que une las tablas y trae todas las mesas, ordenando las mesas y productos de manera consistente
$query = "
    SELECT 
        m.idm AS mesa_id, 
        m.numero_mesa, 
        m.estado AS estado_mesa, 
        p.cantidad, 
        p.prefijos, 
        p.detalle, 
        p.tipo_producto, 
        p.id_pro,  
        p.mesero AS mesero_id, 
        me.nombre_mese, 
        c.comentario,
        p.fecha  -- Agregamos el campo fecha para el cronómetro
    FROM mesas m
    LEFT JOIN pedidos p ON m.id_pedido = p.numero_pedido
    LEFT JOIN meseros me ON p.mesero = me.id_mese
    LEFT JOIN comentarios c ON p.numero_pedido = c.id_pedido
    ORDER BY 
        FIELD(m.estado, 'en_cocina') DESC,  
        m.numero_mesa ASC, p.id_pro ASC";  // Ordenar por id_pro para mantener el orden de los productos

$stmt = $conn->prepare($query);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar pedidos por mesa
$mesas = [];
foreach ($resultados as $resultado) {
    $numero_mesa = $resultado['numero_mesa'];
    if (!isset($mesas[$numero_mesa])) {
        $mesas[$numero_mesa] = [
            'mesa_id' => $resultado['mesa_id'],
            'numero_mesa' => $resultado['numero_mesa'],
            'estado_mesa' => $resultado['estado_mesa'],
            'nombre_mese' => $resultado['nombre_mese'],
            'comentario' => $resultado['comentario'],
            'pedidos' => []
        ];
    }

    // Añadir cada pedido a la mesa correspondiente solo si la mesa está en "en_cocina"
    // Y si el id_pro NO está en el rango de 52 a 96
    if ($resultado['estado_mesa'] === 'en_cocina' && ($resultado['id_pro'] < 52 || $resultado['id_pro'] > 96)) {
        $mesas[$numero_mesa]['pedidos'][] = [
            'cantidad' => $resultado['cantidad'],
            'prefijos' => $resultado['prefijos'],
            'detalle' => $resultado['detalle'],
            'tipo_producto' => $resultado['tipo_producto'],
            'fecha' => $resultado['fecha']  // Añadimos el campo fecha aquí
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Mesas</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .btn-estado {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start; /* Alineación a la izquierda */
            text-align: left; /* Alineación a la izquierda */
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            word-wrap: break-word;
        }

        /* Estilo para las mesas en cocina */
        .btn-en-cocina {
            background-color: yellow;
            color: black;
        }

        /* Estilo para las mesas en otros estados */
        .btn-otro-estado {
            background-color: #ccc; /* Color gris para otros estados */
            color: #666;
        }

        .container {
            max-width: 100%;
        }

        h2 {
            font-size: 1.8rem;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .pedido-item {
            text-align: left;
            font-size: 0.9rem;
            margin: 0; /* Eliminar márgenes entre textos */
            padding: 5px 0;
        }

        .pedido-item p {
            margin: 0; /* Eliminar márgenes entre textos */
            padding: 0; /* Sin padding para alineación exacta */
        }

        /* Línea divisora entre productos */
        .separator {
            border-top: 1px solid #ddd;
            margin: 10px 0; /* Espaciado entre productos */
        }

        .empty-state {
            font-style: italic;
            color: #999;
        }

        /* Estilo para cada producto */
        .producto-item {
            padding: 5px 0;
            border-bottom: 1px solid lightgray; /* Línea divisora entre productos */
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row" id="mesas-container">
        <?php foreach ($mesas as $mesa): ?>
            <div class="col-md-2 mb-3">
                <!-- Botón de la mesa -->
                <button class="btn btn-estado <?php echo $mesa['estado_mesa'] === 'en_cocina' ? 'btn-en-cocina' : 'btn-otro-estado'; ?>" onclick="actualizarEstadoMesa(<?php echo $mesa['mesa_id']; ?>, '<?php echo $mesa['numero_mesa']; ?>')" id="button-<?php echo $mesa['mesa_id']; ?>">
                    <!-- Mostrar el número de la mesa, nombre del mesero y comentario solo una vez -->
                    <p><strong>Mesa:</strong> <?php echo $mesa['numero_mesa']; ?></p>
                    <?php if ($mesa['estado_mesa'] === 'en_cocina'): ?>
                        <p><strong>Mesero:</strong> <?php echo htmlspecialchars($mesa['nombre_mese']); ?></p>
                        <?php if (!empty($mesa['comentario'])): ?>
                            <p><strong>Comentario:</strong> <?php echo htmlspecialchars($mesa['comentario']); ?></p>
                        <?php endif; ?>

                        <!-- Mostrar los pedidos solo si está en cocina -->
                        <?php foreach ($mesa['pedidos'] as $index => $pedido): ?>
                            <div class="producto-item">
                                <p>
                                    <strong>Cantidad:</strong> <?php echo htmlspecialchars($pedido['cantidad']); ?><br>
                                    <strong> <b><?php echo htmlspecialchars($pedido['tipo_producto']); ?></b> </strong> <?php echo htmlspecialchars($pedido['prefijos']); ?>  <?php echo htmlspecialchars($pedido['detalle']); ?>
                                </p>
                                <!-- Agregar el cronómetro -->
                                <p><strong>Tiempo:</strong> <span id="cronometro-<?php echo $mesa['mesa_id']; ?>-<?php echo $index; ?>" data-fecha="<?php echo $pedido['fecha']; ?>">00:00:00</span></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Mostrar un mensaje vacío para las mesas que no están en "en_cocina" -->
                        <p class="empty-state">Esperando pedido...</p>
                    <?php endif; ?>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recargar los datos automáticamente cada 5 segundos -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
// Función para iniciar y actualizar el cronómetro
function iniciarCronometro() {
    // Encontramos todos los elementos que tienen el cronómetro y la fecha de inicio
    document.querySelectorAll('[id^="cronometro-"]').forEach(function(element) {
        const fechaInicio = new Date(element.getAttribute('data-fecha').replace(' ', 'T')); // Aseguramos el formato adecuado
        setInterval(function() {
            // Calcular la diferencia de tiempo entre la fecha actual y la fecha de inicio
            const ahora = new Date();
            const diferencia = ahora - fechaInicio;  // Diferencia en milisegundos

            // Convertir la diferencia a horas, minutos y segundos
            const horas = Math.floor(diferencia / (1000 * 60 * 60));
            const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
            const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

            // Mostrar el cronómetro en formato HH:MM:SS
            element.textContent = `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segundos.toString().padStart(2, '0')}`;
        }, 1000);  // Actualizar cada segundo
    });
}

// Llamar a la función cuando se cargue la página
document.addEventListener('DOMContentLoaded', function() {
    iniciarCronometro();
});

function actualizarMesas() {
    $.ajax({
        url: 'turnosmesas.php', // Asegúrate de que la ruta sea correcta
        method: 'GET',
        success: function(data) {
            $('#mesas-container').html($(data).find('#mesas-container').html());
            iniciarCronometro();  // Reiniciar los cronómetros después de actualizar las mesas
        },
        error: function(err) {
            console.error('Error al actualizar las mesas:', err);
        }
    });
}

// Actualizar las mesas cada 5 segundos
setInterval(actualizarMesas, 5000);

function actualizarEstadoMesa(mesaId, numeroMesa) {
    if (!confirm(`¿Estás seguro de que deseas marcar la mesa ${numeroMesa} como entregada?`)) {
        return;
    }

    $.ajax({
        url: 'actualizar_estado_mesa.php',
        method: 'POST',
        dataType: 'json',
        data: { mesa_id: mesaId },
        success: function(response) {
            if (response.success) {
                alert('La mesa ha sido marcada como entregada.');
                actualizarMesas();
            } else {
                alert('Error al actualizar la mesa: ' + (response.message || 'Respuesta inesperada'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al realizar el update:', error, 'Respuesta del servidor:', xhr.responseText);
            alert('Ocurrió un error al actualizar la mesa.');
        }
    });
}
</script>
</body>
</html>
