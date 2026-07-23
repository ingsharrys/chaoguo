
<div class="container mt-5">
    <div class="row">

        <!-- Columna para Turnos -->
        <div class="col-md-12">
            <!-- Select para filtrar por tipo de solicitud -->
            <div class="form-group">
                <label for="filtroTipoSolicitud">Filtrar por tipo de solicitud:</label>
                <select id="filtroTipoSolicitud" class="form-control" onchange="cargarDatosTurnos()">
                    <option value="">Todos</option>
                    <option value="50">Domicilios</option>
                    <option value="51">Turno</option>
                    <option value="52">Mesas</option>
                    <option value="53">Recoger</option>
                </select>
            </div>
            
            <!-- Selector de fecha para filtrar los turnos -->
            <div class="form-group">
                <label for="fechaSeleccionada">Seleccionar Fecha:</label>
                <input type="date" id="fechaSeleccionada" class="form-control" onchange="cargarDatosTurnos()">
            </div>

            <a href="index.php?page=creditos.php" type="button" class="btn btn-primary" >Creditos</a>
            <h3>Turnos</h3>
            <div id="turnos-container" class="row"></div>
        </div>
    </div>
</div>

<script type="text/javascript" src="/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>
<script src="../public/js/consolidado.js?cache=9f8"></script>

