<?php
class PedidoModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerDetallesPedido($numero_pedido) {
        // Verificar si el pedido existe en la tabla turnero
        $query_verificar_turno = "SELECT COUNT(*) as count FROM turnero WHERE id_pedido = ?";
        $stmt_verificar_turno = $this->conn->prepare($query_verificar_turno);
        $stmt_verificar_turno->execute([$numero_pedido]);
        $es_turno = $stmt_verificar_turno->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if ($es_turno) {
            // Pedido de Turno
            $query_pedido = "
                SELECT 
                    pr.prefijo AS prefijo, 
                    pr.nombre AS nombre_producto, 
                    p.cantidad, 
                    p.tipo_solicitud, 
                    prp.precio AS precio_producto, 
                    p.detalle, 
                    p.tipo_producto, 
                    COALESCE(c.cliente, 'Cliente de Mesa') AS nombre_cliente, 
                    COALESCE(c.celular, 'Sin celular') AS celular_cliente
                FROM pedidos p 
                JOIN productos pr ON p.id_pro = pr.id_pro
                JOIN precios prp ON pr.id_pro = prp.idproduc
                JOIN turnero t ON p.numero_pedido = t.id_pedido
                LEFT JOIN clientes c ON t.id_cliente = c.id
                WHERE p.numero_pedido = ? 
                AND prp.tipo_prod = p.tipo_producto
            ";
        } else {
            // Pedido de Mesa
            $query_pedido = "
                SELECT 
                    pr.prefijo AS prefijo, 
                    pr.nombre AS nombre_producto, 
                    p.cantidad, 
                    p.tipo_solicitud, 
                    prp.precio AS precio_producto, 
                    p.detalle, 
                    p.tipo_producto, 
                    'Cliente de Mesa' AS nombre_cliente, 
                    'Sin celular' AS celular_cliente
                FROM pedidos p 
                JOIN productos pr ON p.id_pro = pr.id_pro
                JOIN precios prp ON pr.id_pro = prp.idproduc
                WHERE p.numero_pedido = ? 
                AND prp.tipo_prod = p.tipo_producto
            ";
        }

        $stmt_pedido = $this->conn->prepare($query_pedido);
        $stmt_pedido->bindValue(1, $numero_pedido, PDO::PARAM_STR);
        $stmt_pedido->execute();
        return $stmt_pedido->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCostoDomicilio($numero_pedido) {
        $query_domicilio = "SELECT precio FROM domicilios WHERE id_pedido = :id_pedido";
        $stmt_domicilio = $this->conn->prepare($query_domicilio);
        $stmt_domicilio->bindParam(':id_pedido', $numero_pedido);
        $stmt_domicilio->execute();
        $resultado_domicilio = $stmt_domicilio->fetch(PDO::FETCH_ASSOC);
        return $resultado_domicilio ? (float)$resultado_domicilio['precio'] : 0;
    }

    public function verificarPago($numero_pedido) {
        $query_check_pago = "
            SELECT COUNT(*) AS count, costo, m_pago 
            FROM caja 
            WHERE id_pedidoc = :id_pedidoc
        ";
        $stmt_check_pago = $this->conn->prepare($query_check_pago);
        $stmt_check_pago->bindParam(':id_pedidoc', $numero_pedido);
        $stmt_check_pago->execute();
        return $stmt_check_pago->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerAbonos($idCredito) {
        $query_abonos = "
            SELECT id, m_pagocr, efectivo, fecha_abono
            FROM abono_credito
            WHERE id_credito = :id_credito
            ORDER BY fecha_abono DESC
        ";
        $stmt_abonos = $this->conn->prepare($query_abonos);
        $stmt_abonos->bindParam(':id_credito', $idCredito, PDO::PARAM_INT);
        $stmt_abonos->execute();
        return $stmt_abonos->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>