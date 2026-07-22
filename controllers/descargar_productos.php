<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '/home/rie/vendor/autoload.php';
require_once '../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$database = new Database();
$conn = $database->getConnection();

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$valor = isset($_GET['valor']) ? $_GET['valor'] : '';
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Mapear los tipos a sus valores de tipo_solicitud
$tipoSolicitudMap = [
    'domicilios' => 50,
    'turnos' => 51,
    'mesas' => 52,
    'recoger' => 53
];

// Mapear los valores de tipo_solicitud a sus descripciones
$tipoVentaMap = [
    50 => 'Domicilios',
    51 => 'Turnos',
    52 => 'Mesas',
    53 => 'Recoger'
];

if ($tipo && ($tipo == 'dia' || isset($tipoSolicitudMap[$tipo])) && $valor) {
    // Si es consolidado, incluimos todos los tipos de solicitudes
    if ($tipo == 'dia') {
        $queryProductos = "
        SELECT p.numero_pedido, p.fecha, pr.nombre AS producto, p.cantidad, prp.precio, 
               (p.cantidad * prp.precio) AS total, p.tipo_solicitud, p.mesero, c.m_pago, 
               c.efectivo, c.cajero, m.nombre_mese, cl.cliente AS nombre_cliente
        FROM pedidos p
        JOIN productos pr ON p.producto = pr.nombre
        JOIN precios prp ON pr.id_pro = prp.idproduc AND p.tipo_producto = prp.tipo_prod
        LEFT JOIN meseros m ON p.mesero = m.id_mese
        LEFT JOIN caja c ON p.numero_pedido = c.id_pedidoc
        LEFT JOIN clientes cl ON p.id_cliente = cl.id
        WHERE DATE(p.fecha) = :valor AND p.tipo_solicitud IN (50, 51, 52, 53)
        ORDER BY p.numero_pedido ASC, p.fecha ASC";
        
        $stmtProductos = $conn->prepare($queryProductos);
        $stmtProductos->bindParam(':valor', $valor);
    } else {
        // Para los otros tipos, usamos el filtro por tipo_solicitud específico
        $tipoSolicitud = $tipoSolicitudMap[$tipo];

        $queryProductos = "
        SELECT p.numero_pedido, p.fecha, pr.nombre AS producto, p.cantidad, prp.precio, 
               (p.cantidad * prp.precio) AS total, p.tipo_solicitud, p.mesero, c.m_pago, 
               c.efectivo, c.cajero, m.nombre_mese, cl.cliente AS nombre_cliente, 
               d.precio AS costo_domicilio, domi.repartidor AS nombre_repartidor
        FROM pedidos p
        JOIN productos pr ON p.producto = pr.nombre
        JOIN precios prp ON pr.id_pro = prp.idproduc AND p.tipo_producto = prp.tipo_prod
        LEFT JOIN meseros m ON p.mesero = m.id_mese
        LEFT JOIN caja c ON p.numero_pedido = c.id_pedidoc
        LEFT JOIN clientes cl ON p.id_cliente = cl.id
        LEFT JOIN domicilios d ON p.numero_pedido = d.id_pedido
        LEFT JOIN domiciliarios domi ON d.id_domi = domi.id_e
        WHERE DATE(p.fecha) = :valor AND p.tipo_solicitud = :tipo_solicitud
        ORDER BY p.numero_pedido ASC, p.fecha ASC";

        $stmtProductos = $conn->prepare($queryProductos);
        $stmtProductos->bindParam(':valor', $valor);
        $stmtProductos->bindParam(':tipo_solicitud', $tipoSolicitud);
    }

    $stmtProductos->execute();
    $productosVendidos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

    // Crear un nuevo archivo de Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Productos Vendidos');

    // Agregar encabezados
    $sheet->setCellValue('A1', 'Fecha');
    $sheet->setCellValue('B1', 'Producto');
    $sheet->setCellValue('C1', 'Cantidad');
    $sheet->setCellValue('D1', 'Precio Unitario');
    $sheet->setCellValue('E1', 'Total');
    $sheet->setCellValue('F1', 'Tipo de Venta');
    if ($tipo == 'domicilios') {
        $sheet->setCellValue('G1', 'Costo Domicilio');
        $sheet->setCellValue('H1', 'Total + Domicilio');
        $sheet->setCellValue('I1', 'Repartidor');  // Nueva columna para Domiciliarios
    }
    $sheet->setCellValue($tipo == 'domicilios' ? 'J1' : 'G1', 'Despachado');
    $sheet->setCellValue($tipo == 'domicilios' ? 'K1' : 'H1', 'Mesero');
    $sheet->setCellValue($tipo == 'domicilios' ? 'L1' : 'I1', 'Método de Pago');
    $sheet->setCellValue($tipo == 'domicilios' ? 'M1' : 'J1', 'Efectivo');
    $sheet->setCellValue($tipo == 'domicilios' ? 'N1' : 'K1', 'Cliente');
    $sheet->setCellValue($tipo == 'domicilios' ? 'O1' : 'L1', 'Cajero');

    // Agregar datos de productos vendidos
    $row = 2;
    $currentPedido = null;
    $startRow = null;
    $clienteActual = null;
    $efectivoActual = null;

    foreach ($productosVendidos as $producto) {
        if ($producto['numero_pedido'] !== $currentPedido) {
            if ($currentPedido !== null && $row - $startRow > 1) {
                $sheet->mergeCells(($tipo == 'domicilios' ? "M" : "K") . "$startRow:" . ($tipo == 'domicilios' ? "M" : "K") . ($row - 1));
                $sheet->mergeCells(($tipo == 'domicilios' ? "L" : "J") . "$startRow:" . ($tipo == 'domicilios' ? "L" : "J") . ($row - 1));  // Combinar la columna Efectivo
            }

            $currentPedido = $producto['numero_pedido'];
            $clienteActual = $producto['nombre_cliente'];
            $efectivoActual = $producto['efectivo'];
            $startRow = $row;
        }

        // Calcular el total con el costo del domicilio solo si es un domicilio
        $totalConDomicilio = $tipo == 'domicilios' ? $producto['total'] + (float)$producto['costo_domicilio'] : $producto['total'];

        // Obtener el tipo de venta basado en tipo_solicitud
        $tipoVenta = isset($tipoVentaMap[$producto['tipo_solicitud']]) ? $tipoVentaMap[$producto['tipo_solicitud']] : 'N/A';

        // Colocar los valores en el Excel
        $sheet->setCellValue('A' . $row, $producto['fecha']);
        $sheet->setCellValue('B' . $row, $producto['producto']);
        $sheet->setCellValue('C' . $row, $producto['cantidad']);
        $sheet->setCellValue('D' . $row, $producto['precio']);
        $sheet->setCellValue('E' . $row, $producto['total']);
        $sheet->setCellValue('F' . $row, $tipoVenta);
        
        if ($tipo == 'domicilios') {
            $sheet->setCellValue('G' . $row, $producto['costo_domicilio']); 
            $sheet->setCellValue('H' . $row, $totalConDomicilio); 
            $sheet->setCellValue('I' . $row, $producto['nombre_repartidor']);  // Repartidor
        }

        $sheet->setCellValue(($tipo == 'domicilios' ? 'J' : 'G') . $row, ucfirst($tipo));
        $sheet->setCellValue(($tipo == 'domicilios' ? 'K' : 'H') . $row, $producto['nombre_mese']);
        $sheet->setCellValue(($tipo == 'domicilios' ? 'L' : 'I') . $row, $producto['m_pago']);
        $sheet->setCellValue(($tipo == 'domicilios' ? 'M' : 'J') . $row, $efectivoActual);
        $sheet->setCellValue(($tipo == 'domicilios' ? 'N' : 'K') . $row, $clienteActual);
        $sheet->setCellValue(($tipo == 'domicilios' ? 'O' : 'L') . $row, $producto['cajero']);

        $row++;
    }

    // Combinar las celdas del último cliente si hay varias filas
    if ($currentPedido !== null && $row - $startRow > 1) {
        $sheet->mergeCells(($tipo == 'domicilios' ? "M" : "K") . "$startRow:" . ($tipo == 'domicilios' ? "M" : "K") . ($row - 1));
        $sheet->mergeCells(($tipo == 'domicilios' ? "L" : "J") . "$startRow:" . ($tipo == 'domicilios' ? "L" : "J") . ($row - 1));
    }

    // Configurar encabezados para la descarga del archivo
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="productos_vendidos_' . $tipo . '_' . $valor . '.xlsx"');
    header('Cache-Control: max-age=0');

    // Crear el archivo Excel y enviarlo al navegador
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} else {
    echo "Datos incompletos.";
}
?>
