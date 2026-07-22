document.addEventListener('DOMContentLoaded', function () {
    const modalContainer = document.getElementById('modalContainer');

    if (modalContainer) {
        modalContainer.innerHTML = `
            <div class="modal fade" id="codigoModal" tabindex="-1" aria-labelledby="codigoModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="codigoModalLabel">Ingrese el código</h5>
                        </div>
                        <div class="modal-body">
                            <form id="codigoForm">
                                <div class="mb-3">
                                    <label for="codigoInput" class="form-label">Código</label>
                                    <input type="password" class="form-control" id="codigoInput" required>
                                    <div id="errorMensaje" class="form-text text-danger" style="display: none;">Código incorrecto, inténtelo de nuevo.</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Validar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Inicializar y mostrar el modal usando la API de Bootstrap 5
        const modalEl = document.getElementById('codigoModal');
        const myModal = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
        });
        myModal.show();

        // Validación del formulario
        const form = document.getElementById('codigoForm');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const codigo = document.getElementById('codigoInput').value;

            fetch('../controllers/validar_codigo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `codigo=${encodeURIComponent(codigo)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    myModal.hide();
                    location.reload();
                } else {
                    document.getElementById('errorMensaje').style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Error al validar código:', err);
            });
        });
    }
});
