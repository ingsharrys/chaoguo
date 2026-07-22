<?php
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $id_gasto = $_POST['id_gasto'];
    $id_mesero = $_POST['id_mesero'];
    $cantidad = $_POST['abono'];

    // Validar que el abono no sea mayor al faltante
    $query_faltante = "SELECT (g.monto - COALESCE(SUM(a.cantidad), 0)) AS faltante 
                       FROM gastos g
                       LEFT JOIN abono_nomina a ON g.id = a.id_gasto
                       WHERE g.id = :id_gasto
                       GROUP BY g.id";
    
    $stmt_faltante = $conn->prepare($query_faltante);
    $stmt_faltante->bindParam(':id_gasto', $id_gasto, PDO::PARAM_INT);
    $stmt_faltante->execute();
    $resultado = $stmt_faltante->fetch(PDO::FETCH_ASSOC);
    $faltante = $resultado['faltante'];

    if ($cantidad > $faltante) {
        echo "Error: No puedes abonar más de $" . number_format($faltante, 0, '', ',');
        exit;
    }

    // Insertar el abono en la base de datos
    $query_insert = "INSERT INTO abono_nomina (id_gasto, id_mesero, cantidad, fecha) VALUES (:id_gasto, :id_mesero, :cantidad, NOW())";
    
    $stmt_insert = $conn->prepare($query_insert);
    $stmt_insert->bindParam(':id_gasto', $id_gasto, PDO::PARAM_INT);
    $stmt_insert->bindParam(':id_mesero', $id_mesero, PDO::PARAM_INT);
    $stmt_insert->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
    
    if ($stmt_insert->execute()) {
        // Verificar si el gasto ya se ha cubierto completamente
        $query_verificar_faltante = "SELECT (g.monto - COALESCE(SUM(a.cantidad), 0)) AS faltante 
                                      FROM gastos g
                                      LEFT JOIN abono_nomina a ON g.id = a.id_gasto
                                      WHERE g.id = :id_gasto
                                      GROUP BY g.id";
        
        $stmt_verificar_faltante = $conn->prepare($query_verificar_faltante);
        $stmt_verificar_faltante->bindParam(':id_gasto', $id_gasto, PDO::PARAM_INT);
        $stmt_verificar_faltante->execute();
        $resultado = $stmt_verificar_faltante->fetch(PDO::FETCH_ASSOC);
        $faltante_actual = $resultado['faltante'];
    
        // Si el faltante es 0, actualizar el estado del gasto a '1'
        if ($faltante_actual == 0) {
            $query_actualizar_estado = "UPDATE gastos SET estado = 1 WHERE id = :id_gasto";
            $stmt_actualizar_estado = $conn->prepare($query_actualizar_estado);
            $stmt_actualizar_estado->bindParam(':id_gasto', $id_gasto, PDO::PARAM_INT);
            $stmt_actualizar_estado->execute();
        }
    
        header("Location: ../public/index.php?page=gastos.php");
        exit;
    } else {
        echo "Error al registrar el abono.";
    }
}

?>