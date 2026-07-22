<!-- Modal del formulario para crear/editar pedido -->
<div class="modal fade" id="orderFormModal" tabindex="-1" role="dialog" aria-labelledby="orderFormModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="background: #000000de; border: 2px solid #5d520673;">
      <div class="modal-header">
        <h5 class="modal-title" style="color:white" id="orderFormModalLabel">Detalles del pedido</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true" style="color: white;">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="orderForm">
          <!-- input hidden para tipo_solicitud -->
          <input type="hidden" id="tipo_solicitud" name="tipo_solicitud" value="<?php echo $tipo_solicitud ?? ''; ?>">

          <div class="form-group">
            <label for="customerName" style="color:#fff">Nombre</label>
            <input type="text" value="<?php echo htmlspecialchars($nombreCliente ?? ''); ?>" class="form-control" id="customerName" required>
          </div>

          <div class="form-group">
            <label for="customerPhone" style="color:#fff">Teléfono</label>
            <input type="tel" class="form-control" value="<?php echo htmlspecialchars($celular ?? ''); ?>" id="customerPhone" required>
          </div>

          <?php if (($tipo_solicitud ?? null) != 51): ?>
            <!-- Mostrar estos campos solo si NO es tipo_solicitud = 51 -->
            <div class="form-group">
              <label for="customerAddress" style="color:#fff">Dirección</label>
              <input type="text" value="<?php echo htmlspecialchars($direccionCliente ?? ''); ?>" class="form-control" id="customerAddress" required>
            </div>
            <div class="form-group">
              <label for="customerBarrio" style="color:#fff">Barrio</label>
              <input type="text" value="<?php echo htmlspecialchars($barrioCliente ?? ''); ?>" class="form-control" id="customerBarrio" required>
            </div>
          <?php endif; ?>

          <div class="form-group">
            <label for="comments" style="color:#fff">Comentarios del pedido</label>
            <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
          </div>

          <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="electronicInvoice">
            <label class="form-check-label" for="electronicInvoice" style="color:#fff">¿Desea factura electrónica?</label>
          </div>

          <div id="invoiceDetails" style="display: none;">
            <div class="form-group">
              <label for="customerEmail" style="color:#fff">Email</label>
              <input type="email" value="<?php echo ($emailCliente ?? '') !== 'sincorreo' ? htmlspecialchars($emailCliente ?? '') : ''; ?>" class="form-control" id="customerEmail">
            </div>
            <div class="form-group">
              <label for="customerId" style="color:#fff">Número de cédula</label>
              <input type="text" class="form-control" id="customerId">
            </div>
          </div>

          <!-- Aquí se inyectan los productos seleccionados (ver script.js) -->
          <div id="selectedProductsContainer"></div>

          <button type="submit" class="btn btn-primary">Enviar pedido</button>
        </form>
      </div>
    </div>
  </div>
</div>
