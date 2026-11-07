<?php
/**
 * Guard de autenticación para controllers AJAX y scripts del panel.
 * Incluir al INICIO de cualquier endpoint que lea o modifique datos del
 * negocio y que solo deba usar el personal logueado:
 *     require_once __DIR__ . '/../helpers/auth_guard.php';
 *
 * NO incluir en los canales públicos/máquina: api/ (app de mesas),
 * menu/ (menú QR de clientes) ni webhooks de pago o bot — esos se
 * protegen con firma o token propio, no con sesión.
 */
require_once __DIR__ . '/Session.php';

Session::start();

if (!Session::get('user_id') && !Session::get('cajero')) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error'   => 'No autorizado. Inicia sesión en el panel.'
    ]);
    exit;
}
