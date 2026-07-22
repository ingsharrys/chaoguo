<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;

class PedidoController
{
    public function index()
    {
        // Obtenemos los parámetros GET (corrige la URL para usar `pedido=qr`, NO `=qr`)
        $pedido   = $_GET['pedido'] ?? null;     // <<--- IMPORTANTE: aquí tomamos 'pedido' del query
        $telefono = $_GET['numero'] ?? null;

        // Conversión del teléfono, si empieza con +
        $celular  = (substr($telefono, 0, 1) === '+') ? substr($telefono, 3) : $telefono;

        /**
         * Definimos $tipo_solicitud con valor por defecto 1 si no hay coincidencia
         * Antes tenía: ( $pedido === 'call') ? 53 : null
         * Ahora usamos 1 en lugar de null, para evitar columnas vacías en la BD
         */
        $tipo_solicitud = ($pedido === 'qr')
            ? 51
            : (($pedido === 'wp')
                ? 50
                : (($pedido === 'call')
                    ? 53
                    : 1  // Valor por defecto si no coincide nada
                )
            );

        // Fecha de Colombia
        date_default_timezone_set('America/Bogota');
        $fecha_actual = date('Y-m-d');

        // Conexión a BD
        $database = new Database();
        $db = $database->getConnection();

        // Creamos instancias de los modelos
        $clienteModel = new Cliente($db);
        $pedidoModel  = new Pedido($db);
        $productoModel= new Producto($db);

        // Buscamos cliente por celular
        $cliente = $clienteModel->getClienteByCelular($celular);

        $pedidosPendientes = [];
        $nombreCliente     = '';
        $direccionCliente  = '';
        $emailCliente      = '';
        $cedulaCliente     = '';
        $barrioCliente     = '';

        if ($cliente) {
            // Si existe el cliente, obtenemos los pedidos pendientes
            $id_cliente = $cliente['id'];
            $pedidosPendientes = $pedidoModel->getPedidosPendientes($id_cliente, $fecha_actual);

            // Datos del cliente
            $nombreCliente    = $cliente['cliente'];
            $direccionCliente = $cliente['direccion'];
            $emailCliente     = $cliente['email'];
            $cedulaCliente    = $cliente['cedula'];
            $barrioCliente    = $cliente['barrio'];
        }

        // Cargamos productos
        $productos = $productoModel->getAllWithPrices();

        // Organizar productos por id_pro
        $productosOrganizados = [];
        foreach ($productos as $producto) {
            $id_pro = $producto['id_pro'];
            if (!isset($productosOrganizados[$id_pro])) {
               $productosOrganizados[$id_pro] = [
                'id_pro'    => $producto['id_pro'],
                'nombre'    => $producto['nombre'],
                'prefijo'   => $producto['prefijo'],
                'img'       => $producto['img'],
                'descript'  => $producto['descript'],
                'cat'       => $producto['cat'],
                'tcomida'   => $producto['tcomida'],  // Asegurar que tcomida esté aquí
                'precios'   => [],
            ];

            }
            $productosOrganizados[$id_pro]['precios'][] = [
                'tipo_prod'   => $producto['tipo_prod'],
                'precio_tipo' => $producto['precio_tipo'],
            ];
        }

        // Día de la semana
        $dia_semana = date('N');

        // Preparar datos para la vista
        $data = [
            'tipo_solicitud'       => $tipo_solicitud,  // <<-- Llevará 51,50,53 ó 1
            'celular'              => $celular,
            'pedidosPendientes'    => $pedidosPendientes,
            'nombreCliente'        => $nombreCliente,
            'direccionCliente'     => $direccionCliente,
            'emailCliente'         => $emailCliente,
            'cedulaCliente'        => $cedulaCliente,
            'barrioCliente'        => $barrioCliente,
            'productosOrganizados' => $productosOrganizados,
            'dia_semana'           => $dia_semana,
            'pedido'               => $pedido // Para uso en la vista
        ];

        // Renderizamos la vista
        $this->renderView('pedidos.view.php', $data);
    }



    // Método para guardar el pedido (inserción) y devolver JSON:
    public function store()
    {
        // 1. Conexión a la BD
        $database = new Database();
        $db = $database->getConnection();

        // 2. Instanciar modelos
        $clienteModel = new Cliente($db);
        $pedidoModel  = new Pedido($db);
        // (No necesitas Producto aquí para insertar pedidos, a menos que lo requieras por otra lógica)

        // 3. Recibir datos de $_POST
        //    (Mismo nombre de campos que en tu JS: name, phone, address, barrio, email, etc.)
        $name          = $_POST['name']            ?? '';
        $phone         = $_POST['phone']           ?? '';
        $address       = $_POST['address']         ?? '';
        $barrio        = $_POST['barrio']          ?? '';
        $email         = $_POST['email']           ?? 'sincorreo';
        $cedula        = $_POST['id']              ?? '0';
        $tipo_solicitud= $_POST['tipo_solicitud']  ?? 1;
        $products      = $_POST['products']        ?? []; 
        $comments      = $_POST['comments']        ?? '';

        try {
            // 4. Verificar si el cliente existe por su teléfono
            $existe = $clienteModel->getClienteByCelular($phone);
            if ($existe) {
                // Actualizar cliente
                $clienteModel->updateCliente($existe['id'], [
                    'name'    => $name,
                    'email'   => $email,
                    'address' => $address,
                    'barrio'  => $barrio,
                    'cedula'  => $cedula
                ]);
                $clientId = $existe['id'];
            } else {
                // Crear cliente
                $clientId = $clienteModel->createCliente([
                    'name'    => $name,
                    'phone'   => $phone,
                    'email'   => $email,
                    'address' => $address,
                    'barrio'  => $barrio,
                    'cedula'  => $cedula
                ]);
            }

            // 5. Insertar pedido (productos, turno, comentarios...)
            //    Para eso, define un método createPedido() en tu modelo Pedido
            $dataPedido = [
                
                'tipo_solicitud' => $tipo_solicitud,
                'products'       => $products,
                'comments'       => $comments
            ];

            // Llamamos a createPedido del modelo Pedido
            $result = $pedidoModel->createPedido($dataPedido, $clientId);

            // 6. Responder en JSON
            echo json_encode($result);

        } catch (\PDOException $e) {
            // En caso de error de la base de datos
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function renderView($viewName, $data = [])
    {
        extract($data);
        require_once __DIR__ . '/../Views/' . $viewName;
    }
}





