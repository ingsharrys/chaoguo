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

$expectedUser = 'chatwheiyubai';
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
        $horario_apertura = "08:00";
        $horario_cierre = "13:00,21:30";
        break;
    case 4: // Jueves
    case 5: // Viernes
    case 6: // Sábado
        $horario_apertura = "08:00";
        $horario_cierre = "15:00,22:30";
        break;
    case 7: // Domingo
        $horario_apertura = "11:30";
        $horario_cierre = "23:30";
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
        enviar_texto("🎉 ¡Hola! Bienvenid@ a Heiyubai, tu restaurante chino favorito. ¿En qué podemos ayudarte hoy? 

1️⃣  Hacer un Pedido 🥡
2️⃣  Nuestra sede 📍
3️⃣  Menú ⚠️

Por favor, selecciona una opción para continuar.");
    }
}

} else {
    enviar_texto("¡Hola !, Gracias por contactar a Heiyubai. Si deseas ver nuestro menú https://heiyubai.datarie.info/menu/
Cra 8Bis # 35 – 25
Atención de Lunes a Domingo de 10 am – 10 pm");
}

    
    
    
}

if (intent_recibido('preguntas')) {
    $variables = obtener_variables();
    $cantidades = $variables['cantidades'];
    $productos = $variables['productos'];
     enviar_texto("el $productos - $descripcion ");
}


function ProductosConPrecios($productos){
    global $conn;
    
    $sql = "SELECT p.id_pro, p.nombre, pr.precio , pr.tipo_prod
            FROM productos p 
            JOIN precios pr ON p.id_pro = pr.idproduc 
            WHERE p.nombre LIKE ?";
    $stmt = $conn->prepare($sql);
    
    $like_productos = '%' . $productos . '%';
    $stmt->bind_param("s", $like_productos);
    $stmt->execute();
    $result = $stmt->get_result();
    $articulos = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $articulos[] = $row;
        }
    }
    
    $stmt->close();
    
    return $articulos;
}

if (intent_recibido('pregunta precios')) {
    $variables = obtener_variables();
    $productos = $variables['productos'];
    
    if (!empty($productos)) {
        $resultado = ProductosConPrecios($productos);
        
        if (count($resultado) > 0) {
            $response = "Estos son los tipos de $productos que manejamos en heiyuai--";
            foreach ($resultado as $articulo) {
                $response .= $articulo['nombre'] . " = " .  " Tamaño ". $articulo['tipo_prod'] . " ,   " . " Su valor es de : " ."$ ". $articulo['precio'] .   "; ";
            }
        } else {
            $response = "No se encontraron productos de $productos.";
        }
        
        enviar_texto($response);
    } else {
        enviar_texto("No se ha especificado un producto válido.");
    }
}



if (intent_recibido('pedido directo')) {
    $variables = obtener_variables();

    $cantidades = $variables['cantidades'];
    $productos = $variables['productos'];
     enviar_texto("Gracias por pedir $cantidades - $productos");
}

if (intent_recibido('orden pedido')) {
    $variables = obtener_variables();

    $numpedido = $variables['number'];
    $direccion_origen = "Carrera 8Bis # 35 – 25, Neiva, Huila, Colombia";
    $estado = "nuevo";
    $api_key = 'AIzaSyA2ZryJyH97lyWO3D6gTA2Ny4owUKOlK18'; // Asegúrate de tener tu API key correcta

    // Verificar que se recibió un número de pedido
    if (empty($numpedido)) {
        enviar_texto("❌ No se recibió un número de pedido válido.");
        return;
    }

    try {
        $conn->begin_transaction();

        // Registrar inicio de transacción
        error_log("Iniciando transacción para el pedido: " . $numpedido);

        // Buscar los datos del pedido en la tabla `pedidos` y `productos`
        $query = "SELECT p.producto, p.cantidad, pr.precio, c.cliente, c.celular, c.direccion, c.email, pr.cat 
                  FROM pedidos p 
                  JOIN clientes c ON p.id_cliente = c.id 
                  JOIN productos pr ON p.producto = pr.nombre 
                  WHERE p.numero_pedido = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("s", $numpedido);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $total = 0;
            $costoAdicionalEmpaque = 0;
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

            $mensajeEmpaque = "";
            while ($row = $result->fetch_assoc()) {
                $producto = $row['producto'];
                $cantidad = $row['cantidad'];
                $precio = $row['precio'];
                $cliente = $row['cliente'];
                $celular = $row['celular'];
                $direccion_pedi = $row['direccion'];
                $email = $row['email'];
                $cat = $row['cat'];

                $subtotal = $precio * $cantidad;
                $total += $subtotal;

                if ($cat == 5) {
                    $costoEmpaque = 1000 * $cantidad;
                    $costoAdicionalEmpaque += $costoEmpaque;
                    $subtotal += $costoEmpaque;
                    $mensajeEmpaque = "Nota: Los empaques de almuerzo tienen un costo de $1.000 por producto, que se suma al costo.\n";
                }

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
            }

            $direccion_pedi .= ", Neiva, Huila, Colombia";
            $distancia = obtener_distancia_google_maps($direccion_origen, $direccion_pedi, $api_key);
            $tarifadomi = calcular_tarifa_domicilio($distancia);
            $totalpedido = $tarifadomi + $total + $costoAdicionalEmpaque;
            $totalFormateado = number_format($totalpedido, 0, '', ',');

            $detallePedido .= "
🏍️ Domicilio: $$tarifadomi
📍 Direccion: $direccion_pedi
💲 Valor a Pagar: $$totalFormateado";
            $detallePedidoHtml .= "
                <tr>
                    <th colspan='3'>Costo adicional de empaque</th>
                    <th>$$costoAdicionalEmpaque</th>
                </tr>
                <tr>
                    <th colspan='3'>Total</th>
                    <th>$$totalFormateado</th>
                </tr>
            </table>
            <p>Dirección: $direccion_pedi</p>";

            // Insertar la tarifa de domicilio en la tabla domicilios
            $stmt = $conn->prepare("INSERT INTO domicilios (id_pedido, id_domi, precio, califi, coment) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $id_domi = ''; // Usar una cadena vacía para el valor de id_domi
            $calif = 0; // Usar 0 como valor predeterminado para calificación
            $comentpedi = ''; // Usar una cadena vacía para comentario
            $stmt->bind_param("isiis", $numpedido, $id_domi, $tarifadomi, $calif, $comentpedi);
            $stmt->execute();

            $longUrl = "https://larosticerianeiva.com/pagos/wompi.php?pedi=$numpedido&total=$totalpedido&tipo=pedido";
            $shortUrl = shortenUrl($longUrl);

            if ($email == "sincorreo") {
                enviar_texto("🛒 Pedido #$numpedido confirmado, en un promedio de 40min estará llegando a tu casa resumen:\n$detallePedido\n $mensajeEmpaque\n
📧 (Opcional)Enviame tu EMAIL para informarte acerca del pedido y promociones en La Rosticería

Formas de pago

💳 Pago en línea aquí ┈➤ $shortUrl

💵 Efectivo: Pagas cuando recibes

🏦 Transferencia: Ahorros Bancolombia No. 45400001800");
            } else {
                enviar_texto("🛒 Pedido #$numpedido confirmado, en un promedio de 40min estará llegando a tu casa resumen:\n$detallePedido\n $mensajeEmpaque\n
Formas de pago

💳 Pago en línea aquí ┈➤ $shortUrl

💵 Efectivo: Pagas cuando recibes

🏦 Transferencia: Ahorros Bancolombia No. 45400001800

📧 Te enviaremos toda la información del pedido al correo $email. Si quieres actualizar el correo, escríbemelo.");
            }

            if ($email != "sincorreo") {
                $asunto = 'Información de tu Pedido #' . $numpedido;
                $contenido = $detallePedidoHtml;
                if (!enviar_correo($email, $asunto, $contenido)) {
                    enviar_texto("❌ Error al enviar el correo. Por favor, verifica tu dirección de correo.");
                }
            }

            // Confirmar la transacción
            $conn->commit();
        } else {
            error_log("No se encontró ningún pedido con el número: " . $numpedido);
            enviar_texto("🔍 No se encontró ningún pedido con el número $numpedido.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error de base de datos: " . $e->getMessage());
        enviar_texto("Error al guardar los pedidos: " . $e->getMessage());
    }
} 

if (intent_recibido('orden pedido')) {
    $variables = obtener_variables();

    $numpedido = $variables['number'];
    $direccion_origen = "Carrera 8Bis # 35 – 25, Neiva, Huila, Colombia";
    $estado = "nuevo";
    $api_key = 'AIzaSyA2ZryJyH97lyWO3D6gTA2Ny4owUKOlK18'; // Asegúrate de tener tu API key correcta

    // Verificar que se recibió un número de pedido
    if (empty($numpedido)) {
        enviar_texto("❌ No se recibió un número de pedido válido.");
        return;
    }

    try {
        $conn->begin_transaction();

        // Registrar inicio de transacción
        error_log("Iniciando transacción para el pedido: " . $numpedido);

        // Buscar los datos del pedido en la tabla `pedidos` y `productos`
        $query = "SELECT p.producto, p.cantidad, prp.precio, c.cliente, c.celular, c.direccion, c.email, pr.cat 
                  FROM pedidos p 
                  JOIN clientes c ON p.id_cliente = c.id 
                  JOIN productos pr ON p.producto = pr.nombre 
                  JOIN precios prp ON pr.id_pro = prp.idproduc 
                  WHERE p.numero_pedido = ? AND prp.tipo_prod = p.tipo_producto";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("s", $numpedido);
        $stmt->execute();
        $result = $stmt->get_result();

        // Añadir registro de log para depuración
        error_log("Número de filas encontradas: " . $result->num_rows);

        if ($result->num_rows > 0) {
            $total = 0;
            $costoAdicionalEmpaque = 0;
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

            $mensajeEmpaque = "";
            while ($row = $result->fetch_assoc()) {
                $producto = $row['producto'];
                $cantidad = $row['cantidad'];
                $precio = $row['precio'];
                $cliente = $row['cliente'];
                $celular = $row['celular'];
                $direccion_pedi = $row['direccion'];
                $email = $row['email'];
                $cat = $row['cat'];

                $subtotal = $precio * $cantidad;
                $total += $subtotal;

                if ($cat == 5) {
                    $costoEmpaque = 1000 * $cantidad;
                    $costoAdicionalEmpaque += $costoEmpaque;
                    $subtotal += $costoEmpaque;
                    $mensajeEmpaque = "Nota: Los empaques de almuerzo tienen un costo de $1.000 por producto, que se suma al costo.\n";
                }

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
            }

            $direccion_pedi .= ", Neiva, Huila, Colombia";
            $distancia = obtener_distancia_google_maps($direccion_origen, $direccion_pedi, $api_key);
            $tarifadomi = calcular_tarifa_domicilio($distancia);
            $totalpedido = $tarifadomi + $total + $costoAdicionalEmpaque;
            $totalFormateado = number_format($totalpedido, 0, '', ',');

            $detallePedido .= "
🏍️ Domicilio: $$tarifadomi
📍 Direccion: $direccion_pedi
💲 Valor a Pagar: $$totalFormateado";
            $detallePedidoHtml .= "
                <tr>
                    <th colspan='3'>Costo adicional de empaque</th>
                    <th>$$costoAdicionalEmpaque</th>
                </tr>
                <tr>
                    <th colspan='3'>Total</th>
                    <th>$$totalFormateado</th>
                </tr>
            </table>
            <p>Dirección: $direccion_pedi</p>";

            // Insertar la tarifa de domicilio en la tabla domicilios
            $stmt = $conn->prepare("INSERT INTO domicilios (id_pedido, id_domi, precio, califi, coment) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $id_domi = ''; // Usar una cadena vacía para el valor de id_domi
            $calif = 0; // Usar 0 como valor predeterminado para calificación
            $comentpedi = ''; // Usar una cadena vacía para comentario
            $stmt->bind_param("isiis", $numpedido, $id_domi, $tarifadomi, $calif, $comentpedi);
            $stmt->execute();

            $longUrl = "https://larosticerianeiva.com/pagos/wompi.php?pedi=$numpedido&total=$totalpedido&tipo=pedido";
            $shortUrl = shortenUrl($longUrl);

            if ($email == "sincorreo") {
                enviar_texto("🛒 Pedido #$numpedido confirmado, en un promedio de 40min estará llegando a tu casa resumen:\n$detallePedido\n $mensajeEmpaque\n
📧 (Opcional)Enviame tu EMAIL para informarte acerca del pedido y promociones en La Rosticería

Formas de pago

💳 Pago en línea aquí ┈➤ $shortUrl

💵 Efectivo: Pagas cuando recibes

🏦 Transferencia: Ahorros Bancolombia No. 45400001800");
            } else {
                enviar_texto("🛒 Pedido #$numpedido confirmado, en un promedio de 40min estará llegando a tu casa resumen:\n$detallePedido\n $mensajeEmpaque\n
Formas de pago

💳 Pago en línea aquí ┈➤ $shortUrl

💵 Efectivo: Pagas cuando recibes

🏦 Transferencia: Ahorros Bancolombia No. 45400001800

📧 Te enviaremos toda la información del pedido al correo $email. Si quieres actualizar el correo, escríbemelo.");
            }

            if ($email != "sincorreo") {
                $asunto = 'Información de tu Pedido #' . $numpedido;
                $contenido = $detallePedidoHtml;
                if (!enviar_correo($email, $asunto, $contenido)) {
                    enviar_texto("❌ Error al enviar el correo. Por favor, verifica tu dirección de correo.");
                }
            }

            // Confirmar la transacción
            $conn->commit();
        } else {
            error_log("No se encontró ningún pedido con el número: " . $numpedido);
            enviar_texto("🔍 No se encontró ningún pedido con el número $numpedido.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error de base de datos: " . $e->getMessage());
        enviar_texto("Error al guardar los pedidos: " . $e->getMessage());
    }
}

if (intent_recibido('pedidos - yes')) {
    $variables = obtener_variables();

    $numpedido = $variables['number'];
    $direccion_origen = "Carrera 8Bis # 35 – 25, Neiva, Huila, Colombia";
    $estado = "nuevo";
    $api_key = 'AIzaSyA2ZryJyH97lyWO3D6gTA2Ny4owUKOlK18'; // Asegúrate de tener tu API key correcta

    // Verificar que se recibió un número de pedido
    if (empty($numpedido)) {
        enviar_texto("❌ No se recibió un número de pedido válido.");
        return;
    }

    try {
        $conn->begin_transaction();

        // Registrar inicio de transacción
        error_log("Iniciando transacción para el pedido: " . $numpedido);

        // Buscar los datos del pedido en la tabla `pedidos` y `productos`
        $query = "SELECT p.producto, p.cantidad, prp.precio, c.cliente, c.celular, c.direccion, c.email, pr.cat 
                  FROM pedidos p 
                  JOIN clientes c ON p.id_cliente = c.id 
                  JOIN productos pr ON p.producto = pr.nombre 
                  JOIN precios prp ON pr.id_pro = prp.idproduc 
                  WHERE p.numero_pedido = ? AND prp.tipo_prod = p.tipo_producto";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("s", $numpedido);
        $stmt->execute();
        $result = $stmt->get_result();

        // Añadir registro de log para depuración
        error_log("Número de filas encontradas: " . $result->num_rows);

        if ($result->num_rows > 0) {
            $total = 0;
            $costoAdicionalEmpaque = 0;
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

            $mensajeEmpaque = "";
            while ($row = $result->fetch_assoc()) {
                $producto = $row['producto'];
                $cantidad = $row['cantidad'];
                $precio = $row['precio'];
                $cliente = $row['cliente'];
                $celular = $row['celular'];
                $direccion_pedi = $row['direccion'];
                $email = $row['email'];
                $cat = $row['cat'];

                $subtotal = $precio * $cantidad;
                $total += $subtotal;

                if ($cat == 5) {
                    $costoEmpaque = 1000 * $cantidad;
                    $costoAdicionalEmpaque += $costoEmpaque;
                    $subtotal += $costoEmpaque;
                    $mensajeEmpaque = "Nota: Los empaques de almuerzo tienen un costo de $1.000 por producto, que se suma al costo.\n";
                }

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
            }

            $direccion_pedi .= ", Neiva, Huila, Colombia";
            $distancia = obtener_distancia_google_maps($direccion_origen, $direccion_pedi, $api_key);
            $tarifadomi = calcular_tarifa_domicilio($distancia);
            $totalpedido = $tarifadomi + $total + $costoAdicionalEmpaque;
            $totalFormateado = number_format($totalpedido, 0, '', ',');

            $detallePedido .= "
🏍️ Domicilio: $$tarifadomi
📍 Direccion: $direccion_pedi
💲 Valor a Pagar: $$totalFormateado";
            $detallePedidoHtml .= "
                <tr>
                    <th colspan='3'>Costo adicional de empaque</th>
                    <th>$$costoAdicionalEmpaque</th>
                </tr>
                <tr>
                    <th colspan='3'>Total</th>
                    <th>$$totalFormateado</th>
                </tr>
            </table>
            <p>Dirección: $direccion_pedi</p>";

            // Insertar la tarifa de domicilio en la tabla domicilios
            $stmt = $conn->prepare("INSERT INTO domicilios (id_pedido, id_domi, precio, califi, coment) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $id_domi = ''; // Usar una cadena vacía para el valor de id_domi
            $calif = 0; // Usar 0 como valor predeterminado para calificación
            $comentpedi = ''; // Usar una cadena vacía para comentario
            $stmt->bind_param("isiis", $numpedido, $id_domi, $tarifadomi, $calif, $comentpedi);
            $stmt->execute();

            $longUrl = "https://larosticerianeiva.com/pagos/wompi.php?pedi=$numpedido&total=$totalpedido&tipo=pedido";
            $shortUrl = shortenUrl($longUrl);

            if ($email == "sincorreo") {
                enviar_texto("🛒 Pedido #$numpedido confirmado, en un promedio de 40min estará llegando a tu casa resumen:\n$detallePedido\n $mensajeEmpaque\n
📧 (Opcional)Enviame tu EMAIL para informarte acerca del pedido y promociones en La Rosticería

Formas de pago

💳 Pago en línea aquí ┈➤ $shortUrl

💵 Efectivo: Pagas cuando recibes

🏦 Transferencia: Ahorros Bancolombia No. 45400001800");
            } else {
                enviar_texto("🛒 Pedido #$numpedido confirmado, en un promedio de 40min estará llegando a tu casa resumen:\n$detallePedido\n $mensajeEmpaque\n
Formas de pago

💳 Pago en línea aquí ┈➤ $shortUrl

💵 Efectivo: Pagas cuando recibes

🏦 Transferencia: Ahorros Bancolombia No. 45400001800

📧 Te enviaremos toda la información del pedido al correo $email. Si quieres actualizar el correo, escríbemelo.");
            }

            if ($email != "sincorreo") {
                $asunto = 'Información de tu Pedido #' . $numpedido;
                $contenido = $detallePedidoHtml;
                if (!enviar_correo($email, $asunto, $contenido)) {
                    enviar_texto("❌ Error al enviar el correo. Por favor, verifica tu dirección de correo.");
                }
            }

            // Confirmar la transacción
            $conn->commit();
        } else {
            error_log("No se encontró ningún pedido con el número: " . $numpedido);
            enviar_texto("🔍 No se encontró ningún pedido con el número $numpedido.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error de base de datos: " . $e->getMessage());
        enviar_texto("Error al guardar los pedidos: " . $e->getMessage());
    }
}
/*
function obtener_distancia_google_maps($origen, $destino, $api_key) {
    // Implementa tu lógica para obtener la distancia usando la API de Google Maps
    // Puedes usar file_get_contents o cURL para hacer la solicitud a la API
    return 5; // Retorna una distancia ficticia para pruebas
}

function calcular_tarifa_domicilio($distancia) {
    // Implementa tu lógica para calcular la tarifa de domicilio basada en la distancia
    return 5000; // Retorna una tarifa ficticia para pruebas
}

function shortenUrl($longUrl) {
    // Implementa tu lógica para acortar URLs
    return $longUrl; // Retorna la URL original para pruebas
}

function enviar_texto($mensaje) {
    // Implementa tu lógica para enviar mensajes de texto
    echo $mensaje; // Imprime el mensaje para pruebas
}

function enviar_correo($email, $asunto, $contenido) {
    // Implementa tu lógica para enviar correos electrónicos
    return true; // Retorna verdadero para pruebas
}

function intent_recibido($intent) {
    // Implementa tu lógica para verificar el intent recibido
    return true; // Retorna verdadero para pruebas
}

function obtener_variables() {
    // Implementa tu lógica para obtener las variables necesarias
    return ['number' => '123456']; // Retorna valores ficticios para pruebas
}

*/

?>
