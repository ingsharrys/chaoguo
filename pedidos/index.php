<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Chao Guo Restaurante</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f4f4f9;
            color: #333;
            overflow: hidden;
        }

        /* Fondo animado de bambú */
        .bamboo-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: url('https://heiyubai.datarie.info/path/to/images/bamboo.png') repeat-x;
            animation: bamboo-move 10s linear infinite;
        }

        @keyframes bamboo-move {
            0% {
                background-position: 0 0;
            }
            100% {
                background-position: -1000px 0; /* Ajusta según el tamaño de la imagen */
            }
        }

        .container {
            text-align: center;
            max-width: 600px;
            width: 90%;
        }

        img {
            max-width: 200px;
            width: 100%;
            height: auto;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 1.5rem;
            margin-bottom: 30px;
            color: #b00a0f;
        }

        .button-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
        }

        a {
            display: inline-block;
            padding: 15px;
            font-size: 1.2rem;
            text-align: center;
            text-decoration: none;
            color: white;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.3s ease;
            transform: scale(0);
            opacity: 0;
        }

        .btn-domicilio {
            background-color: #b00a0f;
        }

        .btn-domicilio:hover {
            background-color: #9c080d;
            animation: bounce 0.5s;
        }

        .btn-recoger {
            background-color: #ffcc00;
            color: #333;
        }

        .btn-recoger:hover {
            background-color: #e6b800;
            animation: bounce 0.5s;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        @media (max-width: 600px) {
            h1 {
                font-size: 1.2rem;
            }

            a {
                font-size: 1rem;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="bamboo-background"></div>

<div class="container">
    <img src="https://heiyubai.datarie.info/path/to/images/logo-Heiyubai.jpg" alt="Heiyubai Logo">
    <h1>Bienvenido a Chao Guo</h1>
    <div class="button-container">
        <a href="#" id="btn-domicilio" class="btn-domicilio">Domicilio</a>
        <a href="#" id="btn-recoger" class="btn-recoger">Recoger</a>
    </div>
</div>

<!-- Modal para pedir el número de teléfono -->
<div id="phoneModal" class="modal">
    <div class="modal-content">
        <h2>Ingresa tu número de teléfono</h2>
        <input type="tel" id="phoneNumber" placeholder="Número de teléfono" style="padding: 10px; font-size: 16px;">
        <br><br>
        <button id="savePhoneNumber" style="padding: 10px 20px; background-color: #b00a0f; color: white; border: none; cursor: pointer;">Guardar</button>
    </div>
</div>

<script>
    // Obtener el número del GET
    const urlParams = new URLSearchParams(window.location.search);
    let phone = urlParams.get('phone');

    // Función para limpiar el número de teléfono y eliminar espacios
    function cleanPhoneNumber(phone) {
        if (phone) {
            // Remover caracteres de país y cualquier espacio
            let cleanedPhone = phone.replace('%2B57%20', '').replace(/%20/g, '').replace(/\s+/g, ''); // Remover %2B57%20 y espacios
            return cleanedPhone.slice(0, 15); // Retorna los primeros 10 dígitos del número
        }
        return null;
    }

    // Limpiar el número de teléfono si existe
    phone = cleanPhoneNumber(phone);

    // Elementos de los botones
    const btnDomicilio = document.getElementById('btn-domicilio');
    const btnRecoger = document.getElementById('btn-recoger');

    // Actualizar las URLs de los botones con el número de teléfono
    function updateButtonLinks(phone) {
        if (phone) {
            
            btnDomicilio.href = `/menu/?route=pedidos&pedido=wp&numero=${phone}`;
            btnRecoger.href = `/menu/?route=pedidos&pedido=call&numero=${phone}`;
        }
    }

    // Si no hay número, mostrar modal para pedirlo
    if (!phone) {
        const modal = document.getElementById('phoneModal');
        const savePhoneBtn = document.getElementById('savePhoneNumber');

        // Mostrar el modal
        modal.style.display = 'block';

        // Guardar el número de teléfono y actualizar las URLs
        savePhoneBtn.onclick = function() {
            let phoneNumber = document.getElementById('phoneNumber').value.replace(/\s+/g, ''); // Eliminar espacios
            if (phoneNumber && phoneNumber.length === 10) {
                updateButtonLinks(phoneNumber);
                modal.style.display = 'none';
            } else {
                alert('Por favor, ingresa un número de teléfono válido.');
            }
        }
    } else {
        // Si hay número de teléfono, actualiza los enlaces directamente
        updateButtonLinks(phone);
    }

    // Animación de entrada para los botones
    document.addEventListener('DOMContentLoaded', () => {
        const buttons = document.querySelectorAll('a');
        buttons.forEach((button, index) => {
            setTimeout(() => {
                button.style.transform = 'scale(1)';
                button.style.opacity = '1';
            }, index * 300); // Retraso entre botones
        });
    });
</script>

</body>
</html>
