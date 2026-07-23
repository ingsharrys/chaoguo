
<div class="container mt-5">
    <div class="row">
        <!-- Columna para Turnos -->
        <div class="col-md-12">
        <form action="../menu/" method="get" onsubmit="openPopupWindow(this); return false;">
                <input type="hidden" name="route" value="pedidos">
                <input type="hidden" name="pedido" value="call">
                    <div class="form-group">
                        <label for="orderNumberInput">Número del celular:</label>
                        <input type="number" class="form-control" id="orderNumberInput" name="numero" placeholder="Ingrese el número del celular" required>
                    </div>
                        <button type="submit" class="btn btn-primary">Registrar pedido Recoger</button>
            </form>
             <h3>Turnos</h3>
            <div id="turnos-container" class="row"></div>
        </div>
    </div>
</div>

<!-- Campo oculto para almacenar tipoSolicitud -->
<input type="hidden" id="tipoSolicitud" value="53">

<script type="text/javascript" src="/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>
<script src="../public/js/script.js?cache=EVV112344"></script>


<script>
/*    // Llamar a la función para cargar los datos al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        setInterval(() => {
            // Obtener tipoSolicitud dinámicamente desde el campo oculto
            const tipoSolicitud = document.getElementById('tipoSolicitud').value;
            cargarDatosTurnos(tipoSolicitud);
        }, 5000);
    });*/
</script>
