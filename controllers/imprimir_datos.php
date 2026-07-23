<?php
/**
 * Datos COMPLETOS de un pedido para impresión de tickets.
 * Devuelve encabezado (turno, tipo, cliente, teléfono, dirección, mesero),
 * productos con cantidad/tipo/detalle/precio, comentario, domicilio y total.
 * Uso: GET ../controllers/imprimir_datos.php?numero_pedido=123
 */
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Bogota');

try {
    $np = isset($_GET['numero_pedido']) ? (int) $_GET['numero_pedido'] : 0;
    if (!$np) throw new Exception('numero_pedido requerido');

    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* ── Encabezado: turno, tipo de solicitud y cliente ── */
    $stmt = $db->prepare("
        SELECT t.turno, t.tipo_solicitud, t.fecha,
               c.cliente, c.celular, c.direccion, c.barrio
        FROM turnero t
        LEFT JOIN clientes c ON c.id = t.id_cliente
        WHERE t.id_pedido = :np
        LIMIT 1");
    $stmt->execute([':np' => $np]);
    $cab = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    /* ── Datos base desde pedidos (mesa, mesero, fecha de respaldo) ── */
    $stmt = $db->prepare("
        SELECT p.mesa, p.fecha, p.tipo_solicitud, m.nombre_mese
        FROM pedidos p
        LEFT JOIN meseros m ON m.id_mese = p.mesero
        WHERE p.numero_pedido = :np
        LIMIT 1");
    $stmt->execute([':np' => $np]);
    $base = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!$cab && !$base) throw new Exception("Pedido #$np no encontrado");

    /* ── Productos con precio (LEFT JOIN: nunca se pierde una línea) ──
       El nombre sale de pedidos.producto y, si esa columna está vacía
       (pedidos guardados por rutas antiguas), se toma directamente del
       catálogo de productos por su código: el ticket SIEMPRE lleva nombre. */
    $stmt = $db->prepare("
        SELECT COALESCE(NULLIF(TRIM(p.producto), ''), cat.nombre, 'Producto') AS producto,
               COALESCE(NULLIF(TRIM(p.prefijos), ''), cat.prefijo, '')        AS prefijos,
               p.tipo_producto, p.cantidad, p.detalle,
               COALESCE(pr.precio, 0)              AS precio,
               p.cantidad * COALESCE(pr.precio, 0) AS subtotal
        FROM pedidos p
        LEFT JOIN productos cat ON cat.id_pro = p.id_pro
        LEFT JOIN precios pr    ON pr.idproduc = p.id_pro
                               AND pr.tipo_prod = p.tipo_producto
        WHERE p.numero_pedido = :np
        ORDER BY p.id_pedido");
    $stmt->execute([':np' => $np]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ── Comentarios ── */
    $stmt = $db->prepare("SELECT GROUP_CONCAT(comentario SEPARATOR ' | ') FROM comentarios WHERE id_pedido = :np");
    $stmt->execute([':np' => $np]);
    $comentario = $stmt->fetchColumn() ?: '';

    /* ── Costo de domicilio ── */
    $stmt = $db->prepare("SELECT COALESCE(precio, 0) FROM domicilios WHERE id_pedido = :np LIMIT 1");
    $stmt->execute([':np' => $np]);
    $costo_domicilio = (float) ($stmt->fetchColumn() ?: 0);

    /* ── Pago (si ya pasó por caja) ── */
    $stmt = $db->prepare("SELECT m_pago FROM caja WHERE id_pedidoc = :np LIMIT 1");
    $stmt->execute([':np' => $np]);
    $m_pago = $stmt->fetchColumn() ?: null;

    $subtotal = 0;
    foreach ($productos as $p) { $subtotal += (float) $p['subtotal']; }

    echo json_encode([
        'success'         => true,
        'numero_pedido'   => $np,
        'turno'           => $cab['turno'] ?? null,
        'tipo_solicitud'  => (int) ($cab['tipo_solicitud'] ?? $base['tipo_solicitud'] ?? 0),
        'fecha'           => $cab['fecha'] ?? $base['fecha'] ?? null,
        'mesa'            => $base['mesa'] ?? null,
        'mesero'          => $base['nombre_mese'] ?? null,
        'cliente'         => $cab['cliente'] ?? null,
        'celular'         => $cab['celular'] ?? null,
        'direccion'       => $cab['direccion'] ?? null,
        'barrio'          => $cab['barrio'] ?? null,
        'productos'       => $productos,
        'comentario'      => $comentario,
        'costo_domicilio' => $costo_domicilio,
        'subtotal'        => $subtotal,
        'total'           => $subtotal + $costo_domicilio,
        'pagado'          => $m_pago !== null,
        'metodo_pago'     => $m_pago
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
