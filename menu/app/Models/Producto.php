<?php
namespace App\Models;

use PDO;

class Producto
{
    private $conn;
    private $table_name = "productos";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAllWithPrices()
    {
        $query = "
            SELECT p.*, p.tcomida, pr.tipo_prod, pr.precio as precio_tipo 
            FROM productos p 
            LEFT JOIN precios pr ON p.id_pro = pr.idproduc
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Otros métodos relacionados con Productos...
}
