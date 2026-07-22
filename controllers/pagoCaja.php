<?php
header('Content-Type: application/json');
ob_start(); // Iniciar el buffer de salida para evitar salidas no deseadas

// Mostrar errores de PHP (solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
session_start();

// Incluir clases necesarias
require_once '../config/database.php'; // Incluye la conexión a la base de datos

$database = new Database();
$conn = $database->getConnection();

// Verificar si se reciben los datos necesarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_pedido'])) {
    // Capturar el descuento si está presente
    $descuento = isset($_POST['descuento']) ? (float)$_POST['descuento'] : 0;

    // Capturar otros datos del formulario
    $numero_pedido = $_POST['numero_pedido'];
    $cajeros       = isset($_SESSION['cajero']) ? $_SESSION['cajero'] : 'Desconocido';
    $metodo_pago   = $_POST['m_pago'];
    $efectivo      = isset($_POST['pago']) ? (float)$_POST['pago'] : 0;  // Si no viene o está vacío, será 0
    $banco         = isset($_POST['banco']) ? $_POST['banco'] : null;
    $referencia    = isset($_POST['referencia']) ? $_POST['referencia'] : null;
    $detalle       = isset($_POST['detalle']) ? $_POST['detalle'] : null;
    $idmesero       = isset($_POST['idmeses']) ? $_POST['idmeses'] : null;
    $total_a_pagar = isset($_POST['tpago']) ? (float)$_POST['tpago'] : 0;

    // Fecha actual
    date_default_timezone_set('America/Bogota');
    $fecha_actual = date('Y-m-d');

    // Métodos de pago permitidos
    $metodosValidos = [
      'efectivo','transferencia','efectivo_transferencia','tarjeta',
      'credito','cortesia','devolucion','tarjeta_efectivo'
    ];
    if (!in_array($metodo_pago, $metodosValidos)) {
        ob_clean();
        echo json_encode([
            'status'  => 'error',
            'message' => "Método de pago no reconocido: $metodo_pago"
        ]);
        exit;
    }

    try {
        // Iniciar una transacción
        $conn->beginTransaction();

        // ======================================
        // 1) Verificar si ya existe id_pedidoc en "caja"
        // ======================================
        $check_query = "SELECT COUNT(*) FROM caja WHERE id_pedidoc = :id_pedidoc";
        $stmt_check = $conn->prepare($check_query);
        $stmt_check->bindParam(':id_pedidoc', $numero_pedido);
        $stmt_check->execute();
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            // Ya existe en caja => error
            $conn->rollBack();
            ob_clean();
            echo json_encode([
                'status'  => 'error',
                'message' => 'El número de pedido ya existe en caja'
            ]);
            exit;
        }

        // ======================================
        // 2) Insertar en "caja" (con lógica especial si es 'credito')
        // ======================================
        if ($metodo_pago === 'credito') {

            // Insertar en "caja" con efectivo=0
            $query_caja = "INSERT INTO caja (id_pedidoc, costo, m_pago, descuento, efectivo, cajero, fecha_caja, id_cajero)
                           VALUES (:id_pedidoc, :costo, :m_pago, :descuento, 0, :cajero, :fecha_actual, :id_cajero)";
            $stmt_caja = $conn->prepare($query_caja);
            $stmt_caja->bindParam(':id_pedidoc',   $numero_pedido, PDO::PARAM_INT);
            $stmt_caja->bindParam(':costo',        $total_a_pagar);  // Se guarda total, aunque se pague con crédito
            $stmt_caja->bindParam(':m_pago',       $metodo_pago);
            $stmt_caja->bindParam(':descuento',    $descuento);
            $stmt_caja->bindParam(':cajero',       $cajeros);
            $stmt_caja->bindParam(':fecha_actual', $fecha_actual);
            $stmt_caja->bindParam(':id_cajero', $idmesero);
            
            $stmt_caja->execute();

            // Después, registrar en "creditos"
            // 1) OBTENER id_cliente DESDE TURNEO USANDO EL id_pedido
            $queryIdCliente = "
                SELECT id_cliente
                FROM turnero
                WHERE id_pedido = :id_pedido
                LIMIT 1
            ";
            $stmtIdCliente = $conn->prepare($queryIdCliente);
            $stmtIdCliente->bindParam(':id_pedido', $numero_pedido, PDO::PARAM_INT);
            $stmtIdCliente->execute();
            $rowCliente = $stmtIdCliente->fetch(PDO::FETCH_ASSOC);

            if (!$rowCliente) {
                // Si el pedido NO está en turnero, buscar en la tabla pedidos si hay un cliente
                $queryBuscarCliente = "
                    SELECT id_cliente FROM pedidos WHERE numero_pedido = :numero_pedido LIMIT 1
                ";
                $stmtBuscarCliente = $conn->prepare($queryBuscarCliente);
                $stmtBuscarCliente->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
                $stmtBuscarCliente->execute();
                $rowPedidoCliente = $stmtBuscarCliente->fetch(PDO::FETCH_ASSOC);
            
                if ($rowPedidoCliente) {
                    $id_clientecr = $rowPedidoCliente['id_cliente']; // Cliente del pedido
                } else {
                    $id_clientecr = 1; // Cliente genérico si no hay cliente en pedidos
                }
            } else {
                $id_clientecr = $rowCliente['id_cliente'];
            }


            $id_clientecr = $rowCliente['id_cliente'];

            // 2) INSERTAR EN 'creditos'
            // Verificar si el cliente existe en turnero
            $queryIdCliente = "SELECT id_cliente FROM turnero WHERE id_pedido = :id_pedido LIMIT 1";
            $stmtIdCliente = $conn->prepare($queryIdCliente);
            $stmtIdCliente->bindParam(':id_pedido', $numero_pedido, PDO::PARAM_INT);
            $stmtIdCliente->execute();
            $rowCliente = $stmtIdCliente->fetch(PDO::FETCH_ASSOC);
            
            if (!$rowCliente) {
                // Si no hay cliente en turnero, asignar el cliente por defecto (ID 1)
                $id_clientecr = 1;
            } else {
                $id_clientecr = $rowCliente['id_cliente'];
            }
            
            // Insertar en la tabla creditos
            $sqlCredito = "
                INSERT INTO creditos (id_cajero, id_clientecr, m_pedidocr, fecha)
                VALUES (:id_cajero, :id_clientecr, :m_pedidocr, NOW())
            ";
            $stmtCredito = $conn->prepare($sqlCredito);
            $stmtCredito->bindParam(':id_cajero',    $cajeros,       PDO::PARAM_INT);
            $stmtCredito->bindParam(':id_clientecr', $id_clientecr,  PDO::PARAM_INT);
            $stmtCredito->bindParam(':m_pedidocr',   $numero_pedido, PDO::PARAM_INT);
            $stmtCredito->execute();

            // ID del nuevo crédito
            $idCredito = $conn->lastInsertId();

            // 3) SI EFECTIVO > 0 => registrar abono
            if ($efectivo > 0) {
                $sqlAbono = "
                    INSERT INTO abono_credito (id_credito, m_pagocr, efectivo, fecha_abono)
                    VALUES (:id_credito, :m_pagocr, :efectivo, NOW())
                ";
                $stmtAbono = $conn->prepare($sqlAbono);
                $stmtAbono->bindParam(':id_credito', $idCredito,  PDO::PARAM_INT);
                $stmtAbono->bindParam(':m_pagocr',   $metodo_pago);
                $stmtAbono->bindParam(':efectivo',   $efectivo);
                $stmtAbono->execute();
            }
        }
        else {
            // ====================
            // Métodos de pago distintos a "credito"
            // ====================
            // Construir la consulta para la tabla "caja"

            // NOTA: En este caso, sí guardamos "efectivo" real, etc.
            $query_caja = '';

            if ($metodo_pago === 'efectivo_transferencia') {
                if ($efectivo !== null && !empty($banco) && !empty($referencia)) {
                    $query_caja = "
                        INSERT INTO caja (id_pedidoc, costo, m_pago, descuento, efectivo, cajero, banco, referencia, fecha_caja, id_cajero)
                        VALUES (:id_pedidoc, :costo, :m_pago, :descuento, :efectivo, :cajero, :banco, :referencia, :fecha_actual, :id_cajero)
                    ";
                } else {
                    throw new Exception('Faltan datos para efectivo y transferencia');
                }
            }
            elseif ($metodo_pago === 'tarjeta_efectivo') {
                if ($efectivo !== null) {
                    $query_caja = "
                        INSERT INTO caja (id_pedidoc, costo, m_pago, descuento, efectivo, cajero, banco, referencia, fecha_caja, id_cajero)
                        VALUES (:id_pedidoc, :costo, :m_pago, :descuento, :efectivo, :cajero, NULL, NULL, :fecha_actual, :id_cajero)
                    ";
                } else {
                    throw new Exception('Faltan datos para tarjeta y efectivo');
                }
            }
            elseif ($metodo_pago === 'efectivo') {
                if ($efectivo !== null) {
                    $query_caja = "
                        INSERT INTO caja (id_pedidoc, costo, m_pago, descuento, efectivo, cajero, fecha_caja, id_cajero)
                        VALUES (:id_pedidoc, :costo, :m_pago, :descuento, :efectivo, :cajero, :fecha_actual, :id_cajero)
                    ";
                } else {
                    throw new Exception('Falta el monto en efectivo');
                }
            }
            elseif ($metodo_pago === 'transferencia') {
                if (!empty($banco) && !empty($referencia)) {
                    $query_caja = "
                        INSERT INTO caja (id_pedidoc, costo, m_pago, descuento, cajero, banco, referencia, fecha_caja, id_cajero)
                        VALUES (:id_pedidoc, :costo, :m_pago, :descuento, :cajero, :banco, :referencia, :fecha_actual, :id_cajero)
                    ";
                } else {
                    throw new Exception('Faltan datos para la transferencia');
                }
            }
            elseif ($metodo_pago == 'cortesia' || $metodo_pago == 'devolucion') {
                if (!empty($detalle)) {
                    error_log(">>> debug: devolucion/cortesia con detalle=[" . $detalle . "]");

                    $query_caja = "
                        INSERT INTO caja (id_pedidoc, costo, m_pago, descuento, cajero, referencia,  fecha_caja, id_cajero)
                        VALUES (:id_pedidoc, :costo, :m_pago, :descuento, :cajero, :detalle, :fecha_actual, :id_cajero)
                    ";
                } else {
                    error_log(">>> debug: devolucion/cortesia con detalle=[" . $detalle . "]");

                    throw new Exception('Faltan datos para cortesía o devolución');
                }
            }
            else {
                // 'tarjeta' u otros
                $query_caja = "
                    INSERT INTO caja (id_pedidoc, costo, m_pago, descuento, cajero, fecha_caja, id_cajero)
                    VALUES (:id_pedidoc, :costo, :m_pago, :descuento, :cajero, :fecha_actual, :id_cajero)
                ";
            }

            // Preparar e insertar
            $stmt_caja = $conn->prepare($query_caja);
            $stmt_caja->bindParam(':id_pedidoc',   $numero_pedido, PDO::PARAM_INT);
            $stmt_caja->bindParam(':costo',        $total_a_pagar);
            $stmt_caja->bindParam(':descuento',    $descuento);
            $stmt_caja->bindParam(':m_pago',       $metodo_pago);
            $stmt_caja->bindParam(':cajero',       $cajeros);
            $stmt_caja->bindParam(':fecha_actual', $fecha_actual);
            $stmt_caja->bindParam(':id_cajero', $idmesero);

            // Efectivo (si aplica)
            if (strpos($query_caja, ':efectivo') !== false) {
                $stmt_caja->bindParam(':efectivo', $efectivo);
            }
            // Banco y referencia
            if (strpos($query_caja, ':banco') !== false) {
                $stmt_caja->bindParam(':banco', $banco);
            }
             if ($metodo_pago === 'cortesia' || $metodo_pago === 'devolucion') {
                    $stmt_caja->bindParam(':detalle', $detalle);
                }
            if (strpos($query_caja, ':referencia') !== false) {
                // si "detalle" == "referencia" en tu BD, ajústalo
               
                    $stmt_caja->bindParam(':referencia', $referencia);
                
            }
            $stmt_caja->execute();
        }

        // Confirmar transacción
        $conn->commit();

        ob_clean();
        echo json_encode([
            'status'  => 'success',
            'message' => 'Pago (o crédito) registrado correctamente'
        ]);
    }
    catch (Exception $e) {
        $conn->rollBack();
        ob_clean();
        echo json_encode([
            'status'    => 'error',
            'message'   => 'Error al procesar: ' . $e->getMessage()
        ]);
    }
} else {
    ob_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Datos incompletos para procesar el pago'
    ]);
}

ob_end_flush();
