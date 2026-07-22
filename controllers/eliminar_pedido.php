<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['numero_pedido']) || !isset($data['codigo_seguridad'])) {
        throw new Exception("Número de pedido o código de seguridad no proporcionado.");
    }

    $numero_pedido   = $data['numero_pedido'];
    $codigo_seguridad= $data['codigo_seguridad'];

    // 1. Conexión a la BD
    $db   = new Database();
    $conn = $db->getConnection();

    // 2. Verificar el código de seguridad
    $querySeguridad = "SELECT codigo_seguridad
                       FROM seguridad
                       WHERE codigo_seguridad = :codigo_seguridad";
    $stmtSeg = $conn->prepare($querySeguridad);
    $stmtSeg->bindParam(':codigo_seguridad', $codigo_seguridad);
    $stmtSeg->execute();

    if (!$stmtSeg->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Código de seguridad incorrecto.");
    }

    // 3. Determinar si 'turnero.tipo_solicitud' == 50
    //    Para ello, buscamos la fila en turnero
    $queryTipo = "SELECT tipo_solicitud 
                  FROM turnero
                  WHERE id_pedido = :numero_pedido
                  LIMIT 1";
    $stmtTipo = $conn->prepare($queryTipo);
    $stmtTipo->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
    $stmtTipo->execute();
    $rowTipo = $stmtTipo->fetch(PDO::FETCH_ASSOC);

    // 3a) Si no existe en turnero, podría ser un caso especial
    //     (lanzamos excepción o lo ignoramos)
    if (!$rowTipo) {
        throw new Exception("No se encontró el pedido en la tabla turnero.");
    }

    $tipoSolicitud = (int)$rowTipo['tipo_solicitud'];

    // 4. Si tipo_solicitud == 50 => Eliminar de domicilios
    if ($tipoSolicitud === 50) {
        $queryDomicilios = "DELETE FROM domicilios 
                            WHERE id_pedido = :numero_pedido";
        $stmtDom = $conn->prepare($queryDomicilios);
        $stmtDom->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
        if (!$stmtDom->execute()) {
            throw new Exception("Error al eliminar registro de domicilios.");
        }
    }

    // 5. Eliminar COMENTARIOS (tabla hija con foreign key a pedidos)
    $queryComentarios = "DELETE FROM comentarios 
                         WHERE id_pedido = :numero_pedido";
    $stmtCom = $conn->prepare($queryComentarios);
    $stmtCom->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
    if (!$stmtCom->execute()) {
        throw new Exception("Error al eliminar los comentarios del pedido.");
    }

    // 6. Eliminar PEDIDOS (tabla hija en la FK con turnero)
    $queryPedidos = "DELETE FROM pedidos
                     WHERE numero_pedido = :numero_pedido
                       AND tipo_solicitud IN (50, 51)";
    $stmtPed = $conn->prepare($queryPedidos);
    $stmtPed->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
    if (!$stmtPed->execute()) {
        throw new Exception("Error al eliminar registros de la tabla pedidos.");
    }

    // 7. Eliminar TURNERO (tabla padre)
    $queryTurnero = "DELETE FROM turnero 
                     WHERE id_pedido = :numero_pedido";
    $stmtTur = $conn->prepare($queryTurnero);
    $stmtTur->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
    if (!$stmtTur->execute()) {
        throw new Exception("Error al eliminar registro de la tabla turnero.");
    }

    // Éxito
    echo json_encode([
        'success' => true,
        'message' => 'Pedido eliminado correctamente (domicilios, comentarios, pedidos, turnero).'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
