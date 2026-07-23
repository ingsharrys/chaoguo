
<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <form action="../menu/" method="get" onsubmit="openPopupWindow(this); return false;">
                <input type="hidden" name="route" value="pedidos">
                <input type="hidden" name="pedido" value="wp">
                <div class="form-group">
                    <label for="orderNumberInput">Número del Celular:</label>
                    <input type="number" class="form-control" id="orderNumberInput" name="numero" placeholder="Ingrese el número del celular" required>
                </div>
                <button type="submit" class="btn btn-primary">Registrar pedido Domicilio</button>
            </form>
            <h3>Domicilios</h3>
            <div id="turnos-container" class="row"></div>
        </div>
    </div>
</div>

<!-- Campo oculto para almacenar tipoSolicitud -->
<input type="hidden" id="tipoSolicitud" value="50">

<script type="text/javascript" src="/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>
<script src="../public/js/script.js?cache=DFRG4434"></script>
<script src="../public/js/impresion.js?cache=v1"></script>
<!--<script src="../public/js/domicilios.js?cache=ttugj"></script>-->


<script>
 /*   // Llamar a la función para cargar los datos al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        setInterval(() => {
            // Obtener tipoSolicitud dinámicamente desde el campo oculto
            const tipoSolicitud = document.getElementById('tipoSolicitud').value;
            cargarDatosTurnos(tipoSolicitud);
        }, 5000);
    });
*/
</script>

<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="myModalLabel">Detalles del Pedido</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>

          
        </button>
      </div>
      <div class="modal-body" id="modal-content">
        <!-- El contenido del modal se cargará dinámicamente -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" >Cerrar</button>
      </div>
    </div>
  </div>
</div>




