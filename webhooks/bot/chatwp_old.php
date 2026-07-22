<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once "../somosioticos/somosioticos_dialogflow.php";

include "config.php";
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$expectedUser = 'chatwprosti';
$expectedPass = 'Y07T5Q_7oPJ&';

credenciales($expectedUser, $expectedPass);

function generateOrderNumber() {
    $timestamp = time();
    $randomValue = rand(1000, 9999);
    return $timestamp . $randomValue;
}

function enviar_correo($destinatario, $asunto, $contenido) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dario.charry.ramos@gmail.com';
        $mail->Password = 'ktoj ncwp ujjr vxcu';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('dario.charry.ramos@gmail.com', 'La Rosticería');
        $mail->addAddress($destinatario, 'Cliente');
        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $htmlTemplate = file_get_contents('plantilla_correo.html');
        $htmlContent = str_replace(['{{contenido_pedido}}', '{{anio_actual}}'], [$contenido, date('Y')], $htmlTemplate);
        $mail->Body = $htmlContent;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar el correo: {$mail->ErrorInfo}");
        return false;
    }
}

function obtener_distancia_google_maps($direccion_origen, $direccion_destino, $api_key) {
    $direccion_origen = urlencode($direccion_origen);
    $direccion_destino = urlencode($direccion_destino);
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$direccion_origen&destinations=$direccion_destino&key=$api_key";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    if ($data['status'] == 'OK') {
        $distance = $data['rows'][0]['elements'][0]['distance']['value'];
        return $distance / 1000;
    } else {
        throw new Exception('Error al obtener la distancia: ' . $data['status']);
    }
}

function calcular_tarifa_domicilio($distancia) {
    if ($distancia <= 1) {
        return 3000;
    } elseif ($distancia > 1 && $distancia <= 5) {
        return 5000;
    } else {
        return 7000;
    }
}

function generateShortCode($length = 6) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}


function shortenUrl($longUrl) {
    global $conn;

    // Verificar si la URL ya ha sido acortada
    $sql = "SELECT short_code FROM urls WHERE long_url = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $longUrl);
    $stmt->execute();
    $stmt->bind_result($existingShortCode);
    $stmt->fetch();
    $stmt->close();

    if ($existingShortCode) {
        return $existingShortCode;
    }

    // Generar un nuevo código corto
    do {
        $shortCode = generateShortCode();
        $sql = "SELECT id FROM urls WHERE short_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $shortCode);
        $stmt->execute();
        $stmt->store_result();
    } while ($stmt->num_rows > 0);

    // Insertar la URL larga y el código corto en la base de datos
    $sql = "INSERT INTO urls (long_url, short_code) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $longUrl, $shortCode);
    $stmt->execute();
    $stmt->close();

    return $shortCode;
}




if (intent_recibido('Default Welcome Intent')) {
    
    
date_default_timezone_set('America/Bogota'); // Establecer el huso horario de Colombia

$hora_actual = date("H:i");
$dia_semana = date("N");


// Definir los horarios de apertura y cierre
$horario_apertura = "";
$horario_cierre = "";

// Lógica para determinar el horario de apertura y cierre según el día de la semana
switch ($dia_semana) {
    case 1: // Lunes
    case 2: // Martes
    case 3: // Miércoles
        $horario_apertura = "11:00";
        $horario_cierre = "15:00,21:30";
        break;
    case 4: // Jueves
    case 5: // Viernes
    case 6: // Sábado
        $horario_apertura = "08:00";
        $horario_cierre = "15:00,22:30";
        break;
    case 7: // Domingo
        $horario_apertura = "10:30";
        $horario_cierre = "21:30";
        break;
    default:
        // Si el día de la semana no está en el rango de 1 a 7, asumimos que el negocio está cerrado
        enviar_texto("✏️ El negocio está cerrado");
        exit(); // Salir del script
}

// Verificar si la hora actual está dentro del horario de apertura y cierre
$horas_cierre = explode(",", $horario_cierre);
$abierto = false;

foreach ($horas_cierre as $hora_cierre) {
    if ($hora_actual >= $horario_apertura && $hora_actual <= $hora_cierre) {
        $abierto = true;
        break;
    }
}

// Enviar el mensaje correspondiente
if ($abierto) {
if (intent_recibido('DEFAULT RESPONSE')) {
    // Obtener la plataforma de la solicitud
    $platform = origen(); // Utiliza la función origen() de tu librería para obtener la plataforma

    if ($platform === 'FACEBOOK') { // Asegúrate de que 'FACEBOOK' coincida con el formato de plataforma que recibes de tu librería
        // Respuesta para Facebook usando tarjetas de Dialogflow
        // Aquí debes agregar el código para enviar tarjetas de Dialogflow en Facebook
        // Puedes utilizar la API de Facebook Messenger para enviar mensajes estructurados o tarjetas.
        // Consulta la documentación de Dialogflow y la documentación de la API de Facebook Messenger para más detalles.
    } else {
        // Respuesta predeterminada para otras plataformas
        enviar_texto("¡Hola! 👋 Soy *Rosti*, tu asesor virtual en *La Rosticería*. 🐷

Escribe la opción con la que vas a consultar:

1️⃣ *Hacer un pedido* 🛒
2️⃣ *Hacer una reserva* 📅
3️⃣ *Ver Menú* 📜
4️⃣ *Ver nuestras sedes* 📍

_*¡Estoy listo para asistirte!*_ 😊");
    }
}

} else {
    enviar_texto("¡Hola !, Gracias por contactar a La Rosticería. Si deseas ver nuestro menú https://bit.ly/menularosticeria ordenar https://wa.me/c/573004747022  
domicilios 8667922 o whatsapp  300 474 7022 
1. Calixto  calle 7 #18-05 esquina.
Lunes, a Miercoles de 11am a 3PM Y DE 6PM  9:30 pm
Jueves a Sabado 11am a 3PM Y 6PM 10:30 pm
Domingo 10:30 am a 9:30 pm
2. Ipanema calle 8 # 43-97 diagonal a la Olimpica, lunes a viernes 12pm  a 3pm, 5:30 pm 11pm 
fin de semana 12PM a 11pm
Reservas solo ipanema 3185177709");
}

    
    
    
}


if (intent_recibido('pedidos - custom - custom')) {
    $variables = obtener_variables();
    $celu_pedi = $variables['phone-number'];
    $client_pedi = $variables['person']['name'];
   // $client_pedi = $variables['nombres'];
    $email = "sincorreo";
    $addres = "sindireccion";

    $stmt = $conn->prepare("SELECT id, cliente, direccion FROM clientes WHERE celular = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("s", $celu_pedi);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();

    if ($cliente) {
        $stmt = $conn->prepare("UPDATE clientes SET cliente = ? WHERE celular = ?");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("ss", $client_pedi, $celu_pedi);
        $stmt->execute();

        $direccion_cliente = $cliente['direccion'];
        if (empty($direccion_cliente)) {
            enviar_texto("✏️Escríbeme la *dirección* donde enviaremos tu pedido. 🏠📦");
        }elseif($direccion_cliente == "sindireccion"){
            enviar_texto("✏️Escríbeme la *dirección* donde enviaremos tu pedido. 🏠📦");
        }
        else {
            enviar_texto("Hola *$client_pedi*, tenemos esta dirección:
            
 🏠*$direccion_cliente*. 
            
 📦 Escribe *SI* para enviarte el pedido ahí. 
 
 ✏️ Si vas a cambiar de dirección, escríbemela por favor.");
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO clientes (cliente, celular, email, direccion) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("ssss", $client_pedi, $celu_pedi, $email, $addres);
        $stmt->execute();
        enviar_texto("✏️Escríbeme la *dirección* donde enviaremos tu pedido. 🏠📦");
    }
}

if (intent_recibido('pedidos - custom - custom - email')) {
    $variables = obtener_variables();
    $celu_pedi = $variables['phone-number'];
    $mail_pedi = "sincorreo";
    $productos = $variables['producto']; 
    $cantidades = $variables['cantidad'];
    $comentpedi = $variables['comentarios'];
    $client_pedi = $variables['person']['name']; 
    $direccion_pedi = isset($variables['address']) ? implode(', ', $variables['address']) : '';
    //$direccion_pedi = isset($variables['address']) ? $variables['address'] : '';
    $direccion_origen = "Calle 7 con Carrera 17, Neiva, Huila, Colombia";
    $estado = "nuevo";
    $api_key = 'AIzaSyA2ZryJyH97lyWO3D6gTA2Ny4owUKOlK18';

    if (count($productos) === count($cantidades)) {
        $conn->begin_transaction();
        try {
            $numero_pedido = generateOrderNumber();

            $stmt = $conn->prepare("SELECT id, email, direccion FROM clientes WHERE celular = ?");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param("s", $celu_pedi);
            $stmt->execute();
            $result = $stmt->get_result();
            $cliente = $result->fetch_assoc();

            if ($cliente) {
                $cliente_id = $cliente['id'];
                $variableemail = $cliente['email'];
                $direccion_actual = $cliente['direccion'];

                if (!empty($direccion_pedi) && $direccion_pedi != $direccion_actual) {
                    $stmt = $conn->prepare("UPDATE clientes SET direccion = ? WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception($conn->error);
                    }
                    $stmt->bind_param("si", $direccion_pedi, $cliente_id);
                    $stmt->execute();
                } else {
                    $direccion_pedi = $direccion_actual;
                }

                if ($variableemail == "sincorreo" && $mail_pedi != "sincorreo") {
                    $stmt = $conn->prepare("UPDATE clientes SET email = ? WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception($conn->error);
                    }
                    $stmt->bind_param("si", $mail_pedi, $cliente_id);
                    $stmt->execute();
                    $variableemail = $mail_pedi;
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO clientes (cliente, celular, email, direccion) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $stmt->bind_param("ssss", $client_pedi, $celu_pedi, $mail_pedi, $direccion_pedi);
                $stmt->execute();
                $cliente_id = $stmt->insert_id;
                $variableemail = $mail_pedi;
            }

            $stmt = $conn->prepare("INSERT INTO pedidos (id_cliente, producto, cantidad, fecha, numero_pedido, estado) VALUES (?, ?, ?, NOW(), ?, ?)");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            foreach ($productos as $index => $producto) {
                $cantidad = $cantidades[$index];
                $stmt->bind_param("isiss", $cliente_id, $producto, $cantidad, $numero_pedido, $estado);
                $stmt->execute();
            }

            $conn->commit();

            $total = 0;
            $detallePedido = "";
            $detallePedidoHtml = "
                <h2>Resumen del Pedido</h2>
                <table class='order-details'>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>";

            foreach ($productos as $index => $producto) {
                $cantidad = $cantidades[$index];
                $stmt = $conn->prepare("SELECT precio FROM productos WHERE nombre = ?");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $stmt->bind_param("s", $producto);
                $stmt->execute();
                $result = $stmt->get_result();
                $productoData = $result->fetch_assoc();

                if ($productoData) {
                    $precio = $productoData['precio'];
                    $subtotal = $precio * $cantidad;
                    $total += $subtotal;
                    $precioFormateado = number_format($precio, 0, '', ',');
                    $subtotalFormateado = number_format($subtotal, 0, '', ',');

                    $detallePedido .= "
🍽️ $cantidad $producto
💲 v/t: $$subtotalFormateado\n";
                    $detallePedidoHtml .= "
                        <tr>
                            <td>$producto</td>
                            <td>$cantidad</td>
                            <td>$$precioFormateado</td>
                            <td>$$subtotalFormateado</td>
                        </tr>";
                } else {
                    $detallePedido .= "
🍽 $cantidad $producto,
💲 No disponible\n";
                    $detallePedidoHtml .= "
                        <tr>
                            <td>$producto</td>
                            <td>$cantidad</td>
                            <td>No disponible</td>
                            <td>No disponible</td>";
                }
            }

            try {
                $direccion_pedi = $direccion_pedi . ", Neiva, Huila, Colombia";
                $distancia = obtener_distancia_google_maps($direccion_origen, $direccion_pedi, $api_key);
                $tarifadomi = calcular_tarifa_domicilio($distancia);
                $totalpedido = $tarifadomi + $total;
                $totalFormateado = number_format($totalpedido, 0, '', ',');

                $detallePedido .= "
🏍️ Domicilio: $$tarifadomi
📍 Direccion: $direccion_pedi
💲 Valor a Pagar: $$totalFormateado";
                $detallePedidoHtml .= "
                    <tr>
                        <th colspan='3'>Total</th>
                        <th>$$totalFormateado</th>
                    </tr>
                </table>
                <p>Dirección: $direccion_pedi</p>";

                // Insertar la tarifa de domicilio en la tabla domicilios
                $stmt = $conn->prepare("INSERT INTO domicilios (id_pedido, id_domi, precio,califi,coment) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $id_domi = ''; // Usar una cadena vacía para el valor de id_domi
                $calif = 0; // Usar una cadena vacía para el valor de id_domi
                $comentpedi;
                $stmt->bind_param("isiis", $numero_pedido, $id_domi, $tarifadomi,$calif,$comentpedi);
                $stmt->execute();
            } catch (Exception $e) {
                $mensaje_domicilio = "La *dirección* no tiene suficientes datos para calcular el costo del *_domicilio_* 🏡. Te *informaremos* antes de enviar tu pedido 🚚.";
                $detallePedido .= "\n $mensaje_domicilio";
                $detallePedidoHtml .= "<p>$mensaje_domicilio</p>";
            }

            $longUrl = "https://rosticeria.datarie.info/pagos/wompi.php?pedi=$numero_pedido&total=$totalpedido&tipo=pedido";
            $shortUrl = shortenUrl($longUrl);

            if ($variableemail == "sincorreo") {
                enviar_texto("🛒 Pedido confirmado, en un promedio de 40min estará llegando a tu casa resumen:\n$detallePedido\n
📧 (Opcional)Enviame tu EMAIL para informarte acerca del pedido y promociones en La Rosticería

Formas de pago

💳 Pago en línea aquí ┈➤ https://rosticeria.datarie.info/pagos/$shortUrl

💵 Efectivo: Pagas cuando recibes 

🏦 Transferencia: Ahorros Bancolombia No. 45400001800");
            } else {
                enviar_texto("🛒 Pedido confirmado, en un promedio de 40min estará llegando a tu casa resumen:\n$detallePedido\n
Formas de pago

💳 Pago en línea aquí ┈➤ https://rosticeria.datarie.info/pagos/$shortUrl

💵 Efectivo: Pagas cuando recibes 

🏦 Transferencia: Ahorros Bancolombia No. 45400001800

📧 Te enviaremos toda la información del pedido al correo $variableemail. Si quieres actualizar el correo, escríbemelo.");
            }

            if ($variableemail != "sincorreo") {
                $asunto = 'Información de tu Pedido';
                $contenido = $detallePedidoHtml;
                if (!enviar_correo($variableemail, $asunto, $contenido)) {
                    enviar_texto("❌ Error al enviar el correo. Por favor, verifica tu dirección de correo.");
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error de base de datos: " . $e->getMessage());
            enviar_texto("Error al guardar los pedidos: " . $e->getMessage());
        }
    } else {
        enviar_texto("🔍 Revisa bien tu pedido. Hay algún producto que no especificaste bien:
        Escribir:
        (Primero cantidad despues producto)
        Así: (2 Chicharrón Totiado).
        📋 Escribe de nuevo el PEDIDO.");
    }
}





if (intent_recibido('pedidos - custom - custom - email - custom')) {
    $variablesmail = obtener_variables();
    $celu_pedi = $variablesmail['celuco'];
    $mail_pedi = $variablesmail['email'];

    // Verificar si el cliente existe
    $stmt = $conn->prepare("SELECT id, direccion FROM clientes WHERE celular = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("s", $celu_pedi);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();

    if ($cliente) {
        // Actualizar el correo del cliente si ya existe
        $cliente_id = $cliente['id'];
        $direccion_pedi = $cliente['direccion']; // Obtener la dirección del cliente

        $stmt = $conn->prepare("UPDATE clientes SET email = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("si", $mail_pedi, $cliente_id);
        $stmt->execute();

        // Obtener la información del pedido
        $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id_cliente = ? ORDER BY fecha DESC LIMIT 1");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pedido = $result->fetch_assoc();

        if ($pedido) {
            // Construir el contenido del correo con todos los productos del pedido
            $detallePedido = "";
            $detallePedidoHtml = "
                <h2>Resumen del Pedido</h2>
                <table class='order-details'>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>";
            $total = 0;

            $stmt = $conn->prepare("SELECT producto, cantidad FROM pedidos WHERE numero_pedido = ?");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param("s", $pedido['numero_pedido']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($pedidoProducto = $result->fetch_assoc()) {
                $producto = $pedidoProducto['producto'];
                $cantidad = $pedidoProducto['cantidad'];

                $stmtPrecio = $conn->prepare("SELECT precio FROM productos WHERE nombre = ?");
                if (!$stmtPrecio) {
                    throw new Exception($conn->error);
                }
                $stmtPrecio->bind_param("s", $producto);
                $stmtPrecio->execute();
                $resultPrecio = $stmtPrecio->get_result();
                $productoData = $resultPrecio->fetch_assoc();

                if ($productoData) {
                    $precio = $productoData['precio'];
                    $subtotal = $precio * $cantidad;
                    $total += $subtotal;

                    // Formatear los valores como moneda sin decimales
                    $precioFormateado = number_format($precio, 0, '', ',');
                    $subtotalFormateado = number_format($subtotal, 0, '', ',');

                    $detallePedido .= "
🍽️ *$cantidad $producto* 
💲 v/t: $$subtotalFormateado\n";
                    $detallePedidoHtml .= "
                        <tr>
                            <td>$producto</td>
                            <td>$cantidad</td>
                            <td>$$precioFormateado</td>
                            <td>$$subtotalFormateado</td>
                        </tr>";
                } else {
                    $detallePedido .= "
🍽 $cantidad $producto, 
💲 No disponible\n";
                    $detallePedidoHtml .= "
                        <tr>
                            <td>$producto</td>
                            <td>$cantidad</td>
                            <td>No disponible</td>
                            <td>No disponible</td>
                        </tr>";
                }
            }

            // Calcular la tarifa de domicilio
            $tarifadomi = calcular_tarifa_domicilio($direccion_pedi);
            $totalpedido = $tarifadomi + $total;
            $totalFormateado = number_format($totalpedido, 0, '', ',');

            $detallePedido .= "\n 
🏍️ Domicilio: $$tarifadomi
💲 Valor a Pagar: $$totalFormateado";
            $detallePedidoHtml .= "
                <tr>
                    <th colspan='3'>Total</th>
                    <th>$$totalFormateado</th>
                </tr>
            </table>";

            // Enviar correo con la información del pedido
            $asunto = 'Información de tu Pedido';
            $contenido = $detallePedidoHtml . "<p>Fecha: {$pedido['fecha']}</p><p>Número de Pedido: {$pedido['numero_pedido']}</p><p>Estado: {$pedido['estado']}</p>";
            if (!enviar_correo($mail_pedi, $asunto, $contenido)) {
                enviar_texto("❌ Error al enviar el correo. Por favor, verifica tu dirección de correo.");
            } else {
                enviar_texto("📧 Tu correo ha sido registrado y enviaremos información de interés a $mail_pedi gracias por tu pedido.");
            }
        } else {
            enviar_texto("❌ No se encontró un pedido para el cliente.");
        }
    } else {
        enviar_texto("❌ No se encontró el número de celular en la base de datos.");
    }
}



//RESERVAS


if (intent_recibido('reserva - custom - custom')) {
    $variablesreserv = obtener_variables();
    $celu_reserv = $variablesreserv['phone-number'];
    $client_reserv = $variablesreserv['person']['name'];

    // Verificar si el cliente existe
    $stmt = $conn->prepare("SELECT id, email FROM clientes WHERE celular = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("s", $celu_reserv);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();

    if ($cliente) {
        // Cliente existe, actualizar el nombre
        $cliente_id = $cliente['id'];
        $stmt = $conn->prepare("UPDATE clientes SET cliente = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("si", $client_reserv, $cliente_id);
        $stmt->execute();

        // Verificar si el correo existe
        $email_cliente = $cliente['email'];
        if ($email_cliente != "sincorreo") {
            enviar_texto("Hola $client_reserv, si quieres que te enviemos información de tu reserva al correo $email_cliente escribe la palabra *SI* o si cambiaste de correo, escríbemelo.");
        } else {
            enviar_texto("✏️ Por favor ingresa tu correo electrónico para enviarte información de tu reserva.");
        }
    } else {
        // Cliente no existe, insertar nuevo cliente con correo genérico y dirección solicitando que la ingrese
        $email_generico = "sincorreo";
        $direccion_generica = "sindireccion";

        $stmt = $conn->prepare("INSERT INTO clientes (cliente, celular, email, direccion) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("ssss", $client_reserv, $celu_reserv, $email_generico, $direccion_generica);
        $stmt->execute();

        enviar_texto("✏️ Por favor ingresa tu correo electrónico donde te enviaremos información de tu reserva.");
    }
}


if (intent_recibido('reserva - custom - custom - custom')) {
    $variablesreserv = obtener_variables();
    $celu_reserv = $variablesreserv['cliente']; // Número de celular del cliente
    $evento_reserv = $variablesreserv['evento'];
    $fecha_reserv = $variablesreserv['fecha'];
    $mail_reserv = $variablesreserv['email'];
    $fecha_actual = date('Y-m-d H:i:s'); // Fecha y hora del momento del registro
    $invit_reserv = $variablesreserv['invitados'];

    try {
        // Verificar si el cliente existe
        $stmt = $conn->prepare("SELECT id, email FROM clientes WHERE celular = ?");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("s", $celu_reserv);
        $stmt->execute();
        $result = $stmt->get_result();
        $cliente = $result->fetch_assoc();

        if ($cliente) {
            // Cliente existe, obtener id_cliente
            $id_client = $cliente['id'];
            $email_cliente = $cliente['email'];

            // Actualizar el correo si es igual a "sincorreo" o si es diferente del nuevo correo proporcionado y el nuevo correo no está vacío
            if (!empty($mail_reserv) && ($email_cliente == "sincorreo" || $email_cliente != $mail_reserv)) {
                $stmt = $conn->prepare("UPDATE clientes SET email = ? WHERE id = ?");
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                $stmt->bind_param("si", $mail_reserv, $id_client);
                $stmt->execute();
            }
        } else {
            // Cliente no existe, insertar nuevo cliente
            $stmt = $conn->prepare("INSERT INTO clientes (cliente, celular, email, direccion) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $direccion = "escribe tu dirección"; // Valor por defecto para la dirección
            $stmt->bind_param("ssss", $client_reserv, $celu_reserv, $mail_reserv, $direccion);
            $stmt->execute();
            $id_client = $stmt->insert_id; // Obtener el id del nuevo cliente
        }

        // Generar un ID único para la reserva
        $idreserva = generate_unique_reservation_id();

        // Insertar la reserva en la base de datos
        $stmt = $conn->prepare("INSERT INTO reservas (id_client, evento, invita, fecha, fechareser, idreserva) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("isisss", $id_client, $evento_reserv, $invit_reserv, $fecha_actual, $fecha_reserv, $idreserva);
        $stmt->execute();

        $longUrl = "https://rosticeria.datarie.info/pagos/wompi.php?pedi=$idreserva&total=20000&tipo=reserva";
        $shortUrl = shortenUrl($longUrl);
        enviar_texto("✏️ Tu reserva ha sido registrada.
Para confirmar tu reserva, puedes hacer el pago de los $20.000 por estos medios:

💳 Pago en línea aquí ┈➤ *https://rosticeria.datarie.info/pagos/$shortUrl*

💵 Efectivo: Pagas cuando recibes

🏦 Transferencia: Ahorros Bancolombia No. 45400001800");
    } catch (Exception $e) {
        enviar_texto("Error: " . $e->getMessage());
    }
}
function generate_unique_reservation_id() {
    // Obtener el tiempo actual en segundos
    $time = time();

    // Generar un número aleatorio de 4 dígitos
    $randomNumber = mt_rand(1000, 9999);

    // Combinar el tiempo y el número aleatorio para crear un ID único
    $uniqueId = $time . $randomNumber;

    return $uniqueId;
}



?>
