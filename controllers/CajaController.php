<?php
require_once '../models/PedidoModel.php';

class CajaController {
    private $model;

    public function __construct($conn) {
        $this->model = new PedidoModel($conn);
    }

    public function mostrarDetallesPedido($numero_pedido) {
        $detalles_pedido = $this->model->obtenerDetallesPedido($numero_pedido);
        $costo_domicilio = $this->model->obtenerCostoDomicilio($numero_pedido);
        $pago_existente_data = $this->model->verificarPago($numero_pedido);

        $pago_existente = $pago_existente_data['count'] > 0;
        $monto_pagado = $pago_existente_data['costo'] ?? 0;
        $metodo_pagado = $pago_existente_data['m_pago'] ?? '';

        $abonos = [];
        $abonosTotal = 0;
        $idCredito = null;

        if ($metodo_pagado === 'credito') {
            $idCredito = $this->obtenerIdCredito($numero_pedido);
            if ($idCredito) {
                $abonos = $this->model->obtenerAbonos($idCredito);
                foreach ($abonos as $ab) {
                    $abonosTotal += (float)$ab['efectivo'];
                }
            }
        }

        // Calcular totales
        $total_productos = 0;
        foreach ($detalles_pedido as $detalle) {
            $total_productos += (float)$detalle['cantidad'] * (float)$detalle['precio_producto'];
        }

        $total_a_pagar = $total_productos + $costo_domicilio;

        // Pasar los datos a la vista
        require_once '../views/pedido_view.php';
    }

    private function obtenerIdCredito($numero_pedido) {
        $query_credito = "
            SELECT idcr
            FROM creditos
            WHERE m_pedidocr = :numero_pedido
            LIMIT 1
        ";
        $stmt_credito = $this->conn->prepare($query_credito);
        $stmt_credito->bindParam(':numero_pedido', $numero_pedido, PDO::PARAM_INT);
        $stmt_credito->execute();
        $rowCredito = $stmt_credito->fetch(PDO::FETCH_ASSOC);
        return $rowCredito ? $rowCredito['idcr'] : null;
    }
}
?>