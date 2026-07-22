<?php
namespace App\Models;

use PDO;

class Cliente
{
    private $conn;
    private $table_name = "clientes";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getClienteByCelular($celular)
    {
        $query = "SELECT * FROM {$this->table_name} WHERE celular = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$celular]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCliente($idCliente, array $data)
    {
        // Actualiza si existe
        $sql = "
          UPDATE {$this->table_name}
          SET cliente   = :nombre,
              email     = :email,
              direccion = :direccion,
              barrio    = :barrio,
              cedula    = :cedula
          WHERE id      = :idCliente
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':nombre'    => $data['name'],
            ':email'     => $data['email'],
            ':direccion' => $data['address'],
            ':barrio'    => $data['barrio'],
            ':cedula'    => $data['cedula'],
            ':idCliente' => $idCliente
        ]);
    }

    public function createCliente(array $data)
    {
        $sql = "
            INSERT INTO {$this->table_name} 
            (cliente, celular, email, direccion, cedula, barrio)
            VALUES
            (:nombre, :celular, :email, :direccion, :cedula, :barrio)
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':nombre'    => $data['name'],
            ':celular'   => $data['phone'],
            ':email'     => $data['email'],
            ':direccion' => $data['address'],
            ':cedula'    => $data['cedula'],
            ':barrio'    => $data['barrio']
        ]);
        return $this->conn->lastInsertId();
    }
}
