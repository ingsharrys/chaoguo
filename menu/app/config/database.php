<?php
namespace App\Config;

use PDO;

/**
 * Las credenciales de la base de datos viven ÚNICAMENTE en
 * config/database.php (raíz del proyecto), que NO está en el repositorio
 * (está en .gitignore y solo existe en el servidor).
 *
 * Antes este archivo tenía la contraseña de producción escrita y publicada
 * en GitHub — esa contraseña quedó expuesta y DEBE rotarse en cPanel.
 */
require_once __DIR__ . '/../../../config/database.php';

class Database
{
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = (new \Database())->getConnection();
            if ($this->conn instanceof PDO) {
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                /* Si MySQL está saturado, mejor fallar a los 5 s que dejar
                   el worker PHP colgado esperando indefinidamente. */
                $this->conn->setAttribute(PDO::ATTR_TIMEOUT, 5);
            }
        } catch (\Exception $exception) {
            error_log('[menu/db] ' . $exception->getMessage());
            http_response_code(500);
            // Nunca mostrar el detalle del error (puede contener credenciales)
            echo json_encode(['status' => 'error', 'message' => 'Error de conexión.']);
            exit;
        }
        return $this->conn;
    }
}
