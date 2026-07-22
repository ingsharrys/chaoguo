<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Cliente;
use App\Models\Pedido;

class PedidoController
{
    public function store()
    {
        // 1. Conectar
        $database = new Database();
        $db       = $database->getConnection();

        // 2. Instanciar modelos
        $clienteModel = new Cliente($db);
        $pedidoModel  = new Pedido($db);

        // 3. Recibir datos (similares a guardar_pedido.php)
        $name          = $_POST['name']   ?? '';
        $phone         = $_POST['phone']  ?? '';
        $address       = $_POST['address'] ?? '';
        $barrio        = $_POST['barrio'] ?? '';
        $email         = $_POST['email']  ?? 'sincorreo';
        $cedula        = $_POST['id']     ?? '0';
        $products      = $_POST['products'] ?? [];
        $tipoSolicitud = $_POST['tipo_solicitud'] ?? 1;
        $comments      = $_POST['comments'] ?? '';

        // 4. Verificar si existe cliente
        $existing = $clienteModel->getClienteByCelular($phone);
        if ($existing) {
            // actualizar
            $clienteModel->updateCliente($existing['id'], [
                'name'    => $name,
                'email'   => $email,
                'address' => $address,
                'barrio'  => $barrio,
                'cedula'  => $cedula
            ]);
            $clientId = $existing['id'];
        } else {
            // crear
            $clientId = $clienteModel->createCliente([
                'name'    => $name,
                'phone'   => $phone,
                'email'   => $email,
                'address' => $address,
                'barrio'  => $barrio,
                'cedula'  => $cedula
            ]);
        }

        // 5. Llamar a createPedido en el modelo Pedido
        $result = $pedidoModel->createPedido([
            'tipo_solicitud' => $tipoSolicitud,
            'products'       => $products,
            'comments'       => $comments
        ], $clientId);

        // 6. Responder con JSON
        echo json_encode($result);
    }
}
