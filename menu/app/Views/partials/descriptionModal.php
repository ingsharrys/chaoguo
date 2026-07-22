<style>
    .btn-close {
  background-color: transparent;
  border: none;
  font-size: 1.2rem;
  opacity: 0.8;
}
.btn-close:hover {
  opacity: 1;
}

</style>


<!-- Modal para la descripción del producto -->
<div class="modal fade" id="descriptionModal" tabindex="-1" role="dialog" aria-labelledby="descriptionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 rounded-4 shadow-lg" style="background: #ffffff;">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title text-dark fw-bold" id="descriptionModalLabel">Descripción del Producto</h5>
        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Cerrar" style="background-color: #f0f0f0;"></button>
      </div>
      <div class="modal-body pt-3 pb-4 px-4">
        <p id="product-description" class="text-secondary fs-6 m-0"></p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-dark rounded-pill px-4" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
