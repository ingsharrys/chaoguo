<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>La Rosticería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            background-color: #000;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .container-custom {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            text-align: center;
        }
        .logo {
            width: 150px;
            height: auto;
            margin: 0 auto 20px;
        }
        .text-custom {
            font-size: 1.2em;
            margin: 20px 0;
        }
        .price {
            font-size: 10vw;
            color: yellow;
            font-weight: bold;
            margin: 20px 0;
        }
        .btn-wompi {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container-custom">
    <?php
    // Mostrar todos los errores de PHP (para depuración)
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once '../config/database.php';

    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener parámetros desde la URL
    if (isset($_GET['pedi']) && isset($_GET['total']) && isset($_GET['tipo'])) {
        $numero_pedido = filter_var($_GET['pedi'], FILTER_SANITIZE_NUMBER_INT);
        $total = filter_var($_GET['total'], FILTER_VALIDATE_FLOAT);
        $tipopago = $_GET['tipo'];

       // echo "Parámetros recibidos: pedi = $numero_pedido, total = $total, tipo = $tipopago<br>";

        // Validar que el número de pedido sea un entero y el total sea un número
        if ($numero_pedido && $total !== false) {
            // Determinar la consulta SQL en función del tipo de pago
            if ($tipopago == 'pedido') {
                $sql = "SELECT p.numero_pedido, p.id_cliente, c.cliente, c.email, c.celular, c.direccion 
                        FROM pedidos p 
                        JOIN clientes c ON p.id_cliente = c.id 
                        WHERE p.numero_pedido = :numero_pedido";
                $reference = $numero_pedido; // Utiliza el número de pedido como referencia
            } elseif ($tipopago == 'reserva') {
            //    echo "Entró en la condición de reserva<br>";
                $sql = "SELECT r.idreserva, r.id_client, c.cliente, c.email, c.celular, c.direccion 
                        FROM reservas r 
                        JOIN clientes c ON r.id_client = c.id 
                        WHERE r.idreserva = :numero_pedido";
                $reference = $numero_pedido; // Utiliza el id de reserva como referencia
            } else {
                echo "<p>Tipo de pago no válido.</p>";
                exit;
            }

         //   echo "Consulta SQL: $sql<br>";

            // Preparar y ejecutar la consulta
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar si se encontraron resultados
            if ($result) {
              //  echo "Resultado de la consulta: ";
               // var_dump($result);
                ?>
                <div class="text-center">
                    <img src="https://rosticeria.datarie.info/path/to/images/logorosti.webp" class="img-fluid logo" alt="La Rosticería Logo">
                    <p class="text-custom"><?php echo $result["cliente"]; ?>, el total a pagar es:</p>
                    <p class="price">$<?php echo number_format($total, 0, ',', '.'); ?></p>
                </div>
                <div class="btn-wompi">
                    <?php
                    // Datos para el formulario de Wompi
                    $email = $result["email"];
                    $nombre = $result["cliente"];
                    $telefono = $result["celular"];
                    $direccion = $result["direccion"];
                    $total_cents = $total * 100; // Convertir a centavos

                    // Datos de Wompi (ejemplo de cómo generar la firma de integridad, debes reemplazar con tus datos reales)
                    //$public_key = "pub_test_k9uHcQszFkvmxGxUEENZLLokkrQrJy78";
                    $public_key = "pub_prod_FHyf0hof633lWxjOostvAtf4HSszSUhx";
                    //$integrity_key = "test_integrity_8fBrvOVXCvi6rbzNIFuRm5NEKyrjLZnZ"; // Reemplaza con tu clave de integridad real
                    $integrity_key = "prod_integrity_WzGqa8OdFoIwZbRkkctBaiwNeA1pHbmj";
                   
                    $cadena_concatenada = $reference.$total_cents."COP".$integrity_key;
                    $signature =  hash ("sha256", $cadena_concatenada);
                    echo "<form>
                          <script
                            src='https://checkout.wompi.co/widget.js'
                            data-render='button'
                            data-public-key='$public_key'
                            data-currency='COP'
                            data-amount-in-cents='$total_cents'
                            data-reference='$reference'
                            data-signature:integrity='$signature'
                            data-redirect-url='https://transaction-redirect.wompi.co/check'
                            data-customer-data:email='$email'
                            data-customer-data:full-name='$nombre'
                            data-customer-data:phone-number='$telefono'
                            data-customer-data:phone-number-prefix='+57'
                            data-shipping-address:address-line-1='$direccion'
                            data-shipping-address:country='CO'
                            data-shipping-address:city='Bogota'
                            data-shipping-address:phone-number='$telefono'
                            data-shipping-address:region='Cundinamarca'
                            data-shipping-address:name='$nombre'
                          ></script>
                        </form>";
                    ?>
                </div>
                <?php
            } else {
                echo "<p>No se encontraron registros con ese número.</p>";
            }
        } else {
            echo "<p>El número de referencia o el total no son válidos.</p>";
        }
    } else {
        echo "<p>No se proporcionó el número de referencia o el total.</p>";
    }

    // Cerrar la conexión
    $conn = null;
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
