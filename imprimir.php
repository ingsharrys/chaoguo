<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba QZ Tray</title>
    <script type="text/javascript" src="qz-tray.js"></script>
</head>
<body>
    <button id="printBtn">Imprimir</button>
    <script>
        // Verifica si ya hay una conexión activa
        function ensureConnection() {
            if (!qz.websocket.isActive()) {
                return qz.websocket.connect();
            } else {
                return Promise.resolve(); // Ya hay una conexión activa, no hagas nada
            }
        }

        document.getElementById("printBtn").addEventListener("click", function() {
            ensureConnection().then(() => {
                console.log("Conectado a QZ Tray");

                var config = qz.configs.create("POS-80C");

                qz.print(config, [{
                    type: 'raw',
                    format: 'plain',
                    data: "Prueba de impresión QZ Tray\n\n"
                }]).then(() => console.log("Impresión completada")).catch(err => console.error("Error al imprimir:", err));
            }).catch(err => console.error("Error al conectar a QZ Tray:", err));
        });
    </script>
</body>
</html>
