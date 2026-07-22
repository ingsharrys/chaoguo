<style>
    
/* Estilos generales del modal */
.modal-pedido {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
}

/* Contenedor del modal */
.modal-content-pedido {
    background-color: #222;
    color: white;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 400px;
    position: relative;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
}

/* Estilos del encabezado */
.modal-header-pedido {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Botón de cierre */
.close-btn-pedido {
    cursor: pointer;
    font-size: 24px;
}

/* Estilos del formulario */
.form-group-pedido {
    margin-bottom: 15px;
}

.form-control-pedido {
    width: 100%;
    padding: 8px;
    border-radius: 5px;
    border: none;
}

/* Estilos del botón */
.btn-pedido {
    background-color: #007bff;
    color: white;
    padding: 10px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    width: 100%;
}
.btn-pedido:hover {
    background-color: #0056b3;
}

    
</style>

<!-- Botón para abrir el modal -->
<button id="openModalBtn-pedido">Abrir Modal</button>

<!-- Modal -->
<div id="orderFormModal-pedido" class="modal-pedido">
    <div class="modal-content-pedido">
        <div class="modal-header-pedido">
            <h5 class="modal-title-pedido">Detalles del pedido</h5>
            <span class="close-btn-pedido">&times;</span>
        </div>
        <div class="modal-body-pedido">
            <form id="orderForm-pedido">
                <input type="hidden" id="tipo_solicitud-pedido" name="tipo_solicitud" value="<?php echo $tipo_solicitud; ?>">
                <div class="form-group-pedido">
                    <label for="customerName-pedido">Nombre</label>
                    <input type="text" value="<?php echo htmlspecialchars($nombreCliente); ?>" class="form-control-pedido" id="customerName-pedido" required>
                </div>
                <div class="form-group-pedido">
                    <label for="customerPhone-pedido">Teléfono</label>
                    <input type="tel" class="form-control-pedido" value="<?php echo htmlspecialchars($celular); ?>" id="customerPhone-pedido" required>
                </div>
                <?php if ($tipo_solicitud != 51): ?>
                <div class="form-group-pedido">
                    <label for="customerAddress-pedido">Dirección</label>
                    <input type="text" value="<?php echo htmlspecialchars($direccionCliente); ?>" class="form-control-pedido" id="customerAddress-pedido" required>
                </div>
                <div class="form-group-pedido">
                    <label for="customerBarrio-pedido">Barrio</label>
                    <input type="text" value="<?php echo htmlspecialchars($barrioCliente); ?>" class="form-control-pedido" id="customerBarrio-pedido" required>
                </div>
                <?php endif; ?>
                <div class="form-group-pedido">
                    <label for="orderComments-pedido">Comentarios del pedido</label>
                    <textarea class="form-control-pedido" id="comments-pedido" name="comments-pedido" rows="3"></textarea>
                </div>
                <div class="form-group-pedido form-check-pedido">
                    <input type="checkbox" class="form-check-input-pedido" id="electronicInvoice-pedido">
                    <label class="form-check-label-pedido" for="electronicInvoice-pedido">¿Desea factura electrónica?</label>
                </div>
                <div id="invoiceDetails-pedido" style="display: none;">
                    <div class="form-group-pedido">
                        <label for="customerEmail-pedido">Email</label>
                        <input type="email" value="<?php echo ($emailCliente !== 'sincorreo') ? htmlspecialchars($emailCliente) : ''; ?>" class="form-control-pedido" id="customerEmail-pedido">
                    </div>
                    <div class="form-group-pedido">
                        <label for="customerId-pedido">Número de cédula</label>
                        <input type="text" class="form-control-pedido" id="customerId-pedido">
                    </div>
                </div>
                <div id="selectedProductsContainer-pedido"></div>
                <button type="submit" class="btn-pedido">Enviar pedido</button>
            </form>
        </div>
    </div>
</div>






<script>
    
 document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("orderFormModal-pedido");
    const openModalBtn = document.getElementById("openModalBtn-pedido");
    const closeModalBtn = document.querySelector(".close-btn-pedido");

    // Función para abrir el modal
    openModalBtn.addEventListener("click", function() {
        modal.style.display = "flex";
    });

    // Función para cerrar el modal
    closeModalBtn.addEventListener("click", function() {
        modal.style.display = "none";
    });

    // Cerrar el modal si el usuario hace clic fuera del contenido
    window.addEventListener("click", function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });
});


    
</script>






