<style>
    .btn btn-secondary btn-block{width:100% !important;}
</style>
<div class="container mt-5">
    <div class="row">
        <!-- Columna para Mesas -->
        <div class="col-md-12">
            <h3>Mesas</h3>
            <div id="mesas-container" class="row"></div>
        </div>

        <!-- Columna para Turnos -->
        <div class="col-md-12">
            <br>
            <form action="../menu/" method="get" onsubmit="openPopupWindow(this); return false;">
                <input type="hidden" name="route" value="pedidos">
                <input type="hidden" name="pedido" value="qr">
                
                <div class="form-group">
                    <label for="orderNumberInput">Número del celular:</label>
                    <input type="number" class="form-control" id="orderNumberInput" name="numero" placeholder="Ingrese el número del celular" required>
                </div>
                <button type="submit" class="btn btn-primary">Registrar pedido Turno</button>
            </form>
            <h3>Turnos</h3>
            <div id="turnos-container" class="row"></div>
        </div>
    </div>
</div>

<!-- Campo oculto para almacenar tipoSolicitud -->
<input type="hidden" id="tipoSolicitud" value="51">
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

<script type="text/javascript" src="/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>
<script src="../public/js/script_old.js?cache=iui"></script>
<script src="../public/js/impresion.js?cache=v3"></script>



<!-- Modal MESAS -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="myModalLabel">Detalles del Pedido </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="modal-content">
        <!-- Contenido del modal será generado dinámicamente -->
      </div>
      <div class="modal-footer">
        <!-- Botón de estado dinámico -->
        <button type="button" class="btn btn-primary" id="boton-estado" style="display:none;"></button>
        <!-- Formulario para pagar -->

        <!-- Botón deshabilitado para pedidos pagados -->
        <button type="button" class="btn btn-success" id="boton-pagar" style="display:none;" disabled>Pagado</button>
        <!-- Formulario para editar -->
        
        <!-- Botón para imprimir -->
     
      </div>
    </div>
  </div>
</div>
