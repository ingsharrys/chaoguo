<?php
class TotalCalculator {
    private $conn;
    private $fecha_actual;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->fecha_actual = date('Y-m-d');
    }

    public function calculateTotals($cajero = null) {
        $totales = [
            'efectivo' => [
                'ventas' => 0,
                'abonos' => 0,
                'nomina' => 0,
                'total' => 0
            ],
            'tarjeta' => [
                'ventas' => 0,
                'abonos' => 0,
                'total' => 0
            ],
            'transferencia' => [
                'ventas' => 0,
                'abonos' => 0,
                'total' => 0
            ],
            'gastos' => 0,
            'devoluciones' => 0
        ];

        // 1. Calcular ventas del día por método de pago
        $params = [':fecha_actual' => $this->fecha_actual];
        $cajeroCond = "";
        
        if ($cajero) {
            $cajeroCond = " AND c.cajero = :cajero";
            $params[':cajero'] = $cajero;
        }

        $query = "
            SELECT 
                c.m_pago,
                c.costo,
                c.efectivo
            FROM caja c
            WHERE DATE(c.fecha_caja) = :fecha_actual
            $cajeroCond
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ventas as $venta) {
            $costo = floatval($venta['costo']);
            $efectivo = floatval($venta['efectivo']);

            switch ($venta['m_pago']) {
                case 'efectivo':
                    $totales['efectivo']['ventas'] += $costo;
                    break;
                case 'tarjeta':
                    $totales['tarjeta']['ventas'] += $costo;
                    break;
                case 'transferencia':
                    $totales['transferencia']['ventas'] += $costo;
                    break;
                case 'efectivo_transferencia':
                    $totales['efectivo']['ventas'] += $efectivo;
                    $totales['transferencia']['ventas'] += ($costo - $efectivo);
                    break;
                case 'tarjeta_efectivo':
                    $totales['efectivo']['ventas'] += $efectivo;
                    $totales['tarjeta']['ventas'] += ($costo - $efectivo);
                    break;
                case 'devolucion':
                    $totales['devoluciones'] += $costo;
                    break;
            }
        }

        // 2. Calcular abonos
        $queryAbonos = "
            SELECT 
                m_pagocr,
                SUM(CAST(efectivo AS DECIMAL(10,2))) as total
            FROM abono_credito 
            WHERE DATE(fecha_abono) = :fecha_actual
            GROUP BY m_pagocr
        ";

        $stmt = $this->conn->prepare($queryAbonos);
        $stmt->execute([':fecha_actual' => $this->fecha_actual]);
        $abonos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($abonos as $abono) {
            switch ($abono['m_pagocr']) {
                case 'efectivo':
                case 'credito':
                    $totales['efectivo']['abonos'] += floatval($abono['total']);
                    break;
                case 'tarjeta':
                    $totales['tarjeta']['abonos'] += floatval($abono['total']);
                    break;
                case 'transferencia':
                    $totales['transferencia']['abonos'] += floatval($abono['total']);
                    break;
            }
        }

        // 3. Calcular nómina y gastos
        $queryNomina = "SELECT COALESCE(SUM(cantidad), 0) as total FROM abono_nomina WHERE DATE(fecha) = :fecha_actual";
        $stmt = $this->conn->prepare($queryNomina);
        $stmt->execute([':fecha_actual' => $this->fecha_actual]);
        $totales['efectivo']['nomina'] = floatval($stmt->fetchColumn());

        $queryGastos = "SELECT COALESCE(SUM(monto), 0) as total FROM gastos WHERE fecha = :fecha_actual";
        $stmt = $this->conn->prepare($queryGastos);
        $stmt->execute([':fecha_actual' => $this->fecha_actual]);
        $totales['gastos'] = floatval($stmt->fetchColumn());

        // 4. Calcular totales finales
        $totales['efectivo']['total'] = 
            $totales['efectivo']['ventas'] + 
            $totales['efectivo']['abonos'] + 
            $totales['efectivo']['nomina'];

        $totales['tarjeta']['total'] = 
            $totales['tarjeta']['ventas'] + 
            $totales['tarjeta']['abonos'];

        $totales['transferencia']['total'] = 
            $totales['transferencia']['ventas'] + 
            $totales['transferencia']['abonos'];

        return $totales;
    }
}