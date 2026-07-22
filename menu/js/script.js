// Saber si hay pedidos pendientes

$(document).ready(function() {

    function escapeSelector(selector) {
        return selector.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
    }

    // Función para actualizar el estado de los botones
    function updateButtons() {
        const hasPendingOrders = pedidosPendientes.length > 0; 
        let pedidoEstado = hasPendingOrders ? pedidosPendientes[pedidosPendientes.length - 1].estado : null;
        if (hasPendingOrders && pedidoEstado !== 'entregado') {
            $('#makeOrderButton').hide(); 
            $('#pedidoExistenteButton').show();
        } else {
            // Lógica existente para manejar la visibilidad de botones según los productos seleccionados
            const selectedProducts = $('.quantity-input').filter(function () {
                return $(this).val() > 0;
            });
            if (selectedProducts.length > 0) {
                $('#selectProductsButton').hide();
                $('#makeOrderButton').show();
            } else {
                $('#selectProductsButton').show();
                $('#makeOrderButton').hide();
            }
        }
    }

    // Función para aumentar la cantidad
    function aumentarCantidad($input, $checkbox, $productSelected) {
        $input.val(parseInt($input.val()) + 1);
        if ($input.val() > 0) {
            $checkbox.prop('checked', true);
            $productSelected.addClass('show');
        }
        updateButtons();
    }

    // Cargar datos desde localStorage
    const customerName = localStorage.getItem('customerName') || '';
    const customerPhone = localStorage.getItem('customerPhone') || '';
    const customerAddress = localStorage.getItem('customerAddress') || '';
    const customerBarrio = localStorage.getItem('customerBarrio') || '';
    const customerEmail = localStorage.getItem('customerEmail') || '';
    const customerId = localStorage.getItem('customerId') || '';

    // Rellenar formulario con datos desde localStorage
    if (customerName) $('#customerName').val(customerName);
    if (customerPhone) $('#customerPhone').val(customerPhone);
    if (customerAddress) $('#customerAddress').val(customerAddress);
    if (customerBarrio) $('#customerBarrio').val(customerBarrio);
    if (customerEmail && customerEmail !== 'sincorreo') $('#customerEmail').val(customerEmail);
    if (customerId && customerId !== '0') $('#customerId').val(customerId);
    if (customerEmail || customerId) {
        $('#electronicInvoice').prop('checked', false);
    }

    // Mostrar/ocultar campos de factura electrónica
    $('#electronicInvoice').change(function() {
        if ($(this).is(':checked')) {
            $('#invoiceDetails').show();
        } else {
            $('#invoiceDetails').hide();
        }
    });

    // Botón -
    $('.btn-minus').click(function() {
        var $input = $(this).siblings('.quantity-input');
        var value = parseInt($input.val());
        if (value > 0) {
            $input.val(value - 1);
        }
        var $checkbox = $(this).closest('.card-body').find('.product-checkbox');
        var $productSelected = $(this).closest('.card').find('.product-selected');
        if ($input.val() == 0) {
            $checkbox.prop('checked', false);
            $productSelected.removeClass('show');
        }
        updateButtons();
    });

    // Botón +
    $('.btn-plus').click(function() {
        var $input = $(this).siblings('.quantity-input');
        var $checkbox = $(this).closest('.card-body').find('.product-checkbox');
        var $productSelected = $(this).closest('.card').find('.product-selected');
        aumentarCantidad($input, $checkbox, $productSelected);
    });

    // Aumentar cantidad al hacer clic en la imagen
    $('.product-image').click(function() {
        var $cardBody = $(this).closest('.card-body');
        var $input = $cardBody.find('.quantity-input');
        var $checkbox = $cardBody.find('.product-checkbox');
        var $productSelected = $(this).closest('.card').find('.product-selected');
        aumentarCantidad($input, $checkbox, $productSelected);
    });

    // Inicializar estado de botones
    updateButtons();

    // Al hacer clic en 'Hacer pedido'
    // Al hacer clic en 'Hacer pedido'
$('#makeOrderButton').click(function() {
    const selectedProducts = [];

    $('.quantity-input').each(function() {
        const quantity = parseInt($(this).val());
        if (quantity > 0) {
            const productId = $(this).data('id');
            const productName = $(this).data('product-name');
            const productPrice = $(this).data('price');
            const productType = $(this).data('product-type');
            const productCard = $(this).closest('.product-card');
            const productPrefix = productCard.data('prefix');

            // ✅ Capturar correctamente el valor de tcomida
            let tcomida = $(this).data('tcomida');  // 🔥 Captura el data-tcomida directamente

            // 🔍 Si `tcomida` no se encuentra en el input, intenta obtenerlo desde `product-card`
            if (!tcomida) {
                tcomida = productCard.data('tcomida') || "Desconocido";
            }

            console.log(`Producto: ${productName}, Tipo Comida: ${tcomida}`); // 🔍 Depuración

            selectedProducts.push({
                id: productId,
                name: productName,
                price: productPrice,
                type: productType,
                quantity: quantity,
                tcomida: tcomida, // ✅ Ahora se toma correctamente como un número o texto
                prefix: productPrefix
            });
        }
    });

    console.log("Productos seleccionados:", selectedProducts); // 🔍 Revisar en consola



 

        let productListHtml = '';
        selectedProducts.forEach(product => {
            // << USAMOS TEMPLATE STRINGS >>
            
            productListHtml += `
                <div class="form-group" style="background: #4b4b4b;padding: 2%;border-radius: 10px;">
                    <label style="color:#fff">- ${product.name} - ${product.type} (Cantidad: ${product.quantity})</label>
                    ${[2].includes(product.tcomida) ? `
                            <div class="form-check">
                                <input class="form-check-input option-radio" type="radio" name="option-${product.id}-${product.type}" id="option-${product.id}-${product.type}-arroz" value="arroz" required>
                                <label class="form-check-label" for="option-${product.id}-${product.type}-arroz">Arroz</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input option-radio" type="radio" name="option-${product.id}-${product.type}" id="option-${product.id}-${product.type}-papa" value="papa" required>
                                <label class="form-check-label" for="option-${product.id}-${product.type}-papa">Papa</label>
                            </div>
                            <div id="suboptions-${product.id}-${product.type}" class="suboptions" style="display:none;">
                                <div class="form-check">
                                    <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-amarillo" value="amarillo">
                                    <label class="form-check-label" for="suboption-${product.id}-${product.type}-amarillo">Amarillo</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-cafe" value="cafe">
                                    <label class="form-check-label" for="suboption-${product.id}-${product.type}-cafe">Café</label>
                                </div>
                            </div>
                        ` : ''
                    }
                    ${
                        [1].includes(product.tcomida) ? `
                            <div class="form-check">
                                <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-amarillo" value="amarillo" required>
                                <label class="form-check-label" for="suboption-${product.id}-${product.type}-amarillo">Amarillo ${product.tcomida}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-cafe" value="cafe" required>
                                <label class="form-check-label" for="suboption-${product.id}-${product.type}-cafe">Café</label>
                            </div>
                        ` : ''
                    }
                </div>
            `;
        });

        $('#selectedProductsContainer').html(productListHtml);

        // Mostrar el modal
        $('#orderFormModal').modal('show');

        // Manejo de suboptions
        $('input[type=radio][name^="option-"]').change(function() {
            const [_, id, type] = $(this).attr('name').split('-');
            if (this.value === 'arroz') {
                $(`#suboptions-${id}-${type}`).show();
                $(`#suboptions-${id}-${type} .suboption-radio`).attr('required', true);
            } else {
                $(`#suboptions-${id}-${type}`).hide();
                $(`#suboptions-${id}-${type} .suboption-radio`).removeAttr('required');
                $(`#suboptions-${id}-${type} .suboption-radio`).prop('checked', false);
            }
        });
    });

    // Submit del formulario de pedido
    $('#orderForm').submit(function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Enviando...');

        // Obtén todos los datos
        const tipoSolicitud = $('#tipo_solicitud').val();
        const customerName = $('#customerName').val();
        const customerPhone = $('#customerPhone').val();
        const customerAddress = $('#customerAddress').val();
        const customerBarrio = $('#customerBarrio').val();
        const customerEmail = $('#customerEmail').val() || 'sincorreo';
        const customerId = $('#customerId').val() || '0';
        const comments = $('#comments').val();

        // Productos seleccionados
        const selectedProducts = [];
        $('.quantity-input').each(function() {
            const quantity = parseInt($(this).val());
            if (quantity > 0) {
                const productId = $(this).data('id');
                const productName = $(this).data('product-name');
                const productPrice = $(this).data('price');
                const productType = $(this).data('product-type');
                const escapedProductType = escapeSelector(productType);
                const productCard = $(this).closest('.product-card');
                const productPrefix = productCard.data('prefix');

                // <<< CORREGIDO >>>
                const productOption = $(`input[name="option-${productId}-${escapedProductType}"]:checked`).val() || null;
                const productSubOption = $(`input[name="suboption-${productId}-${escapedProductType}"]:checked`).val() || null;

                selectedProducts.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    type: productType,
                    quantity: quantity,
                    prefix: productPrefix,
                    option: productOption,
                    suboption: productSubOption
                });
            }
        });

        // Petición AJAX
       $.post('index.php?route=pedido-store', {
    name: customerName,
    phone: customerPhone,
    address: customerAddress,
    barrio: customerBarrio,
    email: customerEmail,
    id: customerId,
    products: selectedProducts,
    tipo_solicitud: tipoSolicitud,
    comments: comments
}).done(function(response) {
            console.log("Respuesta del servidor:", response);
            const res = JSON.parse(response);
            if (res.status === 'success') {
                // Limpiar inputs
                $('.quantity-input').val(0);
                $('.product-checkbox').prop('checked', false);
                $('.product-selected').removeClass('show');

                // Cerrar modal
                $('#orderFormModal').modal('hide');

                // Mostrar modal de "Pedido enviado"
                $('#orderSentModal').modal('show');
                $('#orderNumber').text(res.order_number);
                $('#turnoNumber').text(res.turno);

                // Recargar la página tras 2 seg
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                console.error(res.message);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
            submitButton.prop('disabled', false).text('Enviar pedido');
        });
    });

    // Ver detalles del producto
    $('.btn-details').click(function() {
        const description = $(this).data('description');
        const image = $(this).data('image');
        $('#product-description').text(description);
        $('#product-image-modal').attr('src', image);
        $('#descriptionModal').modal('show');
    });

    // Filtrar categoría
    $('.filter-btn').click(function() {
        let category = $(this).data('category');
        console.log("Category clicked: " + category);
        if (category === 'all') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $(`.product-card[data-category="${category}"]`).show();
        }
    });

    $('#filterCarousel .carousel-item .filter-btn').click(function() {
        let category = $(this).data('category');
        if (category === 'all') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $(`.product-card[data-category="${category}"]`).show();
        }
    });

    // 🔧 BÚSQUEDA - ARREGLADO
    // Busca en .form-check-label donde está el nombre del producto
    $('#productSearch').on('keyup', function() {
        let searchText = $(this).val().toLowerCase();
        console.log('🔍 Buscando:', searchText); // Debug
        
        if (searchText === '') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $('.product-card').each(function() {
                // Buscar en .form-check-label (donde está el nombre del producto)
                let productName = $(this).find('.form-check-label').text().toLowerCase();
                console.log('  Producto encontrado:', productName); // Debug
                if (productName.includes(searchText)) {
                    $(this).show();
                }
            });
        }
    });

    // Animación en el botón + (opcional)
    let $button = $('.btn-plus');
    $button.addClass('grow-shrink-animation');
    setTimeout(function() {
        $button.removeClass('grow-shrink-animation');
    }, 10000); 
});