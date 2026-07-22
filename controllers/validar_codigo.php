<?php
require_once '../config/database.php';
require_once '../helpers/Session.php';

Session::start();

if (isset($_POST['codigo'])) {
    $codigo = $_POST['codigo'];

    $database = new Database();
    $conn = $database->getConnection();

    $query = "SELECT id_mese, nombre_mese, cargo FROM meseros WHERE cod_mese = :codigo LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
    $stmt->execute();

    // Verificar si se encontró un resultado
    if ($stmt->rowCount() > 0) {
        // Obtener los datos del mesero
        $mesero = $stmt->fetch(PDO::FETCH_ASSOC);
        $nombreCajero = $mesero['nombre_mese'];
        $rol = $mesero['cargo'] ?? 'usuario';
        $idmese = $mesero['id_mese'] ?? 'idcolabor';

        // Guardar el cajero y el rol en la sesión
        Session::set('usuario', [
            'cajero' => $nombreCajero,
            'rol' => $rol,
            'id_mese' => $idmese
        ]);
        Session::set('cajero', $nombreCajero);

        // Verificar si el código es 4587
        if ($codigo == '4587') {
            // Crear una sesión especial para el código '4587'
            Session::set('registro_acceso', true);
            echo json_encode(['status' => 'success', 'message' => "Bienvenido(a) $nombreCajero. Tienes acceso al registro."]);
        } else {
            echo json_encode(['status' => 'success', 'message' => "Bienvenido(a) $nombreCajero."]);
        }
    } else {
        // Si el código no es válido, enviar una respuesta de error
        echo json_encode(['status' => 'error', 'message' => 'Código incorrecto']);
    }
}

/*
require_once '../config/database.php';
require_once '../helpers/Session.php';

// Iniciar sesión
Session::start();

// Verificar si se ha enviado un código
if (isset($_POST['codigo'])) {
    $codigo = $_POST['codigo'];

    // Validar el código y asignar el nombre correspondiente
    switch ($codigo) {
        
        //DOMICILIOS
        
        case '4578':
            // Guardar el nombre "Deyanira" en la sesión
            
            Session::set('usuario', [
                'cajero' => 'Domicilio1',
                'rol' => 'admin'
            ]);
            Session::set('cajero', 'Domicilio1');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Domicilio 1']);
            break;
        
        case '7423':
            // Guardar el nombre "Deyanira" en la sesión
            Session::set('cajero', 'Domicilio2');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Domicilio 2']);
            break;
            
        case '5241':
            // Guardar el nombre "Deyanira" en la sesión
            Session::set('cajero', 'Domicilio3');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Domicilio 3']);
            break;
            
        case '6385':
            // Guardar el nombre "Deyanira" en la sesión
            Session::set('cajero', 'Domicilio4');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Domicilio 4']);
            break;
            
        case '3283':
            // Guardar el nombre "Deyanira" en la sesión
            Session::set('cajero', 'Domicilio5');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Domicilio 5']);
            break;
        
        case '4194':
            // Guardar el nombre "Deyanira" en la sesión
            Session::set('cajero', 'Domicilio6');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Domicilio 6']);
            break;
        
        case '5831':
            // Guardar el nombre "Deyanira" en la sesión
            Session::set('cajero', 'Domicilio7');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Domicilio 7']);
            break;
        
        case '1560':
            // Guardar el nombre "Deyanira" en la sesión
            Session::set('cajero', 'Domicilio8');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Domicilio 8']);
            break;
        
        
        //CAJEROS

        case '8523':
            // Guardar el nombre "Majo" en la sesión
            Session::set('cajero', 'Majo');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Majo']);
            break;

        case '6574':
            // Guardar el nombre "Catalina" en la sesión
            Session::set('cajero', 'Catalina');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Catalina']);
            break;
        
        case '2006':
            // Guardar el nombre "Catalina" en la sesión
            Session::set('cajero', 'Santiago');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Santiago']);
            break;
            
        case '1521':
            // Guardar el nombre "Majo" en la sesión
            Session::set('cajero', 'turnos');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Majo']);
            break;
            
        case '8745':
            // Guardar el nombre "Majo" en la sesión
            Session::set('cajero', 'Manuela');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Manuela']);
            break;    
            
        case '4587':
            // Guardar el nombre "Deyanira" en la sesión
            Session::set('cajero', 'Deyanira');
            echo json_encode(['status' => 'success', 'message' => 'Bienvenida Deyanira']);
            break;

        default:
            // Si el código no es válido, enviar una respuesta de error
            echo json_encode(['status' => 'error', 'message' => 'Código incorrecto']);
            break;
    }
}*/

?>
