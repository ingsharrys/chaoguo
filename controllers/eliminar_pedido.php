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

    // 3. Determinar el tipo de solicitud (si existe en turnero)
    $queryTipo = "SELECT tipo_solicitud
                  FROM turnero
                  WHERE id_pedido = :numero_pedido
                  LIMIT 1";
    $stmtTipo = $conn->prepare($queryTipo);
    $stmtTipo->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
    $stmtTipo->execute();
    $rowTipo = $stmtTipo->fetch(PDO::FETCH_ASSOC);
    $tipoSolicitud = $rowTipo ? (int)$rowTipo['tipo_solicitud'] : 0;

    /* Eliminación COMPLETA y atómica: todo dentro de una transacción.
       Si algo falla, no se borra nada a medias. */
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->beginTransaction();

    // 4. Si es domicilio (50), eliminar el registro de domicilios
    if ($tipoSolicitud === 50) {
        $conn->prepare("DELETE FROM domicilios WHERE id_pedido = :np")
             ->execute([':np' => $numero_pedido]);
    }

    // 5. Eliminar comentarios del pedido
    $conn->prepare("DELETE FROM comentarios WHERE id_pedido = :np")
         ->execute([':np' => $numero_pedido]);

    // 6. Eliminar TODOS los productos del pedido, sin importar el tipo
    //    de solicitud (mesas app = 52, restaurante = 51, domicilios = 50,
    //    llamadas = 53). El filtro anterior IN (50,51) dejaba huérfanos
    //    los pedidos de la app de mesas.
    $conn->prepare("DELETE FROM pedidos WHERE numero_pedido = :np")
         ->execute([':np' => $numero_pedido]);

    // 7. Eliminar el turno
    $conn->prepare("DELETE FROM turnero WHERE id_pedido = :np")
         ->execute([':np' => $numero_pedido]);

    // 8. Liberar la(s) mesa(s) que apuntaban a este pedido:
    //    estado vacío e id_pedido en NULL (igual que liberar_mesa.php)
    $conn->prepare("UPDATE mesas SET estado = '', id_pedido = NULL, fecha = NOW()
                    WHERE id_pedido = :np")
         ->execute([':np' => $numero_pedido]);

    $conn->commit();

    // Éxito
    echo json_encode([
        'success' => true,
        'message' => 'Pedido eliminado por completo (productos, comentarios, turno) y mesa liberada.'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
