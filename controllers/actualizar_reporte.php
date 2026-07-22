<?php
class ReporteController {
    private $database;
    private $conn;

    public function __construct() {
        require_once '../config/database.php';
        $this->database = new Database();
        $this->conn = $this->database->getConnection();
        date_default_timezone_set('America/Bogota');
    }

    public function getReporteData() {
        // Get parameters from POST or default values
        $fechaActual = isset($_POST['fecha_seleccionada']) && !empty($_POST['fecha_seleccionada']) 
            ? $_POST['fecha_seleccionada'] 
            : date('Y-m-d');
        
        $cajeroSeleccionado = isset($_POST['cajero']) && !empty($_POST['cajero']) 
            ? $_POST['cajero'] 
            : 'consolidado';
        
        $bancoSeleccionado = isset($_POST['banco']) ? $_POST['banco'] : '';
        $tipoSolicitudSeleccionado = isset($_POST['tipo_solicitud']) ? $_POST['tipo_solicitud'] : '';

        // Get list of cajeros
        $cajerosDb = $this->getCajeros();

        // Get data for each payment method
        $metodosPago = [
            'consolidado', 
            'efectivo', 
            'transferencia', 
            'tarjeta', 
            'efectivo_transferencia', 
            'tarjeta_efectivo', 
            'devolucion'
        ];

        // Obtener todos los turnos primero (usando el método consolidado)
        $todosLosTurnos = $this->filtrarTurnosPorFechaYMetodoPago(
            'consolidado', 
            $fechaActual, 
            $cajeroSeleccionado, 
            $bancoSeleccionado, 
            $tipoSolicitudSeleccionado
        );

        // Calcular totales usando todos los turnos
        $totales = $this->calcularTotales($todosLosTurnos);

        // Luego obtener los turnos filtrados por método para la visualización
        $turnosPorMetodo = [];
        foreach ($metodosPago as $metodo) {
            $turnosPorMetodo[$metodo] = $this->filtrarTurnosPorFechaYMetodoPago(
                $metodo, 
                $fechaActual, 
                $cajeroSeleccionado, 
                $bancoSeleccionado, 
                $tipoSolicitudSeleccionado
            );
        }

        return [
            'fechaActual' => $fechaActual,
            'cajeroSeleccionado' => $cajeroSeleccionado,
            'bancoSeleccionado' => $bancoSeleccionado,
            'tipoSolicitudSeleccionado' => $tipoSolicitudSeleccionado,
            'cajerosDb' => $cajerosDb,
            'metodosPago' => $metodosPago,
            'turnosPorMetodo' => $turnosPorMetodo,
            'totales' => $totales
        ];
    }

    private function getCajeros() {
        $cajerosDb = [];
        try {
            $queryMeseros = "SELECT id_mese, nombre_mese FROM meseros WHERE cargo = 'cajero'";
            $stmtMeseros = $this->conn->prepare($queryMeseros);
            $stmtMeseros->execute();
            while($row = $stmtMeseros->fetch(PDO::FETCH_ASSOC)) {
                $cajerosDb[] = $row; // Ahora contiene tanto el ID como el nombre
            }
        } catch (Exception $e) {
            error_log("Error consultando meseros: " . $e->getMessage());
            return [];
        }
        return $cajerosDb;
    }

    private function filtrarTurnosPorFechaYMetodoPago($metodoPago, $fechaActual, $cajeroSeleccionado, $bancoSeleccionado, $tipoSolicitudSeleccionado) {
        $queryTurnos = "
            SELECT 
                t.id_pedido, 
                t.turno, 
                t.fecha, 
                t.tipo_solicitud, 
                t.estado, 
                t.id_cliente,
                c.m_pago, 
                m.nombre_mese as cajero,
                c.banco,  
                c.costo,
                c.efectivo
            FROM turnero t
            LEFT JOIN caja c ON c.id_pedidoc = t.id_pedido
            LEFT JOIN meseros m ON m.id_mese = c.id_cajero
            WHERE DATE(t.fecha) = :fechaActual
        ";
    
        $params = [':fechaActual' => $fechaActual];
    
        if ($metodoPago !== 'consolidado') {
            $queryTurnos .= " AND c.m_pago = :metodo_pago";
            $params[':metodo_pago'] = $metodoPago;
        }
    
        if ($cajeroSeleccionado !== 'consolidado') {
            $queryTurnos .= " AND c.id_cajero = :cajeroSeleccionado";
            $params[':cajeroSeleccionado'] = $cajeroSeleccionado;
        }
    
        if (!empty($bancoSeleccionado)) {
            $queryTurnos .= " AND c.banco = :bancoSeleccionado";
            $params[':bancoSeleccionado'] = $bancoSeleccionado;
        }
    
        if (!empty($tipoSolicitudSeleccionado)) {
            $queryTurnos .= " AND t.tipo_solicitud = :tipoSolicitudSeleccionado";
            $params[':tipoSolicitudSeleccionado'] = $tipoSolicitudSeleccionado;
        }
    
        $queryTurnos .= " ORDER BY t.fecha ASC";
    
        try {
            $stmt = $this->conn->prepare($queryTurnos);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en la consulta: " . $e->getMessage());
            return [];
        }
    }

    private function calcularTotales($turnos) {
        $totales = [
            'totalRecibidoEfectivo' => 0,
            'totalEfectivo' => 0,
            'total_efectivo_abonos' => 0,
            'total_nomina' => 0,
            'totalRecibidoTarjeta' => 0,
            'totalTarjeta' => 0,
            'total_tarjeta_abonos' => 0,
            'totalRecibidoTransferencia' => 0,
            'totalTransferencia' => 0,
            'total_transferencia_abonos' => 0,
            'totalDevolucion' => 0
        ];

        $procesados = [];

        foreach ($turnos as $registro) {
            $idPedido = $registro['id_pedido'] ?? null;

            // Verificar si el pedido ya fue procesado
            if (isset($procesados[$idPedido]) || empty($idPedido)) {
                continue;
            }

            $procesados[$idPedido] = true;
            $costo = floatval($registro['costo'] ?? 0);
            $efectivo = floatval($registro['efectivo'] ?? 0);
            $metodoPago = $registro['m_pago'] ?? '';

            switch ($metodoPago) {
                case 'efectivo':
                    $totales['totalEfectivo'] += $costo;
                    $totales['totalRecibidoEfectivo'] += $costo;
                    break;

                case 'efectivo_transferencia':
                    $totales['totalRecibidoEfectivo'] += $efectivo;
                    $totales['totalRecibidoTransferencia'] += ($costo - $efectivo);
                    break;

                case 'tarjeta_efectivo':
                    $totales['totalRecibidoEfectivo'] += $efectivo;
                    $totales['totalRecibidoTarjeta'] += ($costo - $efectivo);
                    break;

                case 'tarjeta':
                    $totales['totalTarjeta'] += $costo;
                    $totales['totalRecibidoTarjeta'] += $costo;
                    break;

                case 'transferencia':
                    $totales['totalTransferencia'] += $costo;
                    $totales['totalRecibidoTransferencia'] += $costo;
                    break;

                case 'devolucion':
                    $totales['totalDevolucion'] += $costo;
                    break;
            }
        }

        return $totales;
    }
}