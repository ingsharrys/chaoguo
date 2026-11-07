<?php
namespace App\Models;

use PDO;
use PDOException;

class Pedido
{
    private PDO    $conn;
    private string $table = 'pedidos';

    /*──────────────────────────────  ctor  ─────────────────────────────*/
    public function __construct(PDO $db)
    {
        /* 1) zona horaria global (PHP) */
        date_default_timezone_set('America/Bogota');

        /* 2) conexión */
        $this->conn = $db;
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /* 3) misma zona para la sesión MySQL */
        $this->conn->exec("SET time_zone = '-05:00'");   // o 'America/Bogota' si está cargada
    }

    /*───────────────────  Pedidos pendientes por cliente  ─────────────*/
    public function getPedidosPendientes(int $idCliente, string $fecha): array
    {
        $sql = "
            SELECT p.id_pedido, p.cantidad, p.fecha,
                   t.estado, t.turno, t.tipo_solicitud,
                   pro.nombre, pro.tcomida
            FROM   pedidos  p
            JOIN   turnero  t ON p.numero_pedido = t.id_pedido
            JOIN   productos pro ON p.id_pro = pro.id_pro
            WHERE  t.id_cliente = ?
              AND  t.fecha >= ?
              AND  t.fecha < ? + INTERVAL 1 DAY
        ";
        $st = $this->conn->prepare($sql);
        $st->execute([$idCliente, $fecha, $fecha]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /*────────────────────────────  Crear pedido  ──────────────────────*/
    public function createPedido(array $data, int $clientId): array
    {
        try {
            $this->conn->beginTransaction();

            /* 1. número de pedido: contador por fila, SIN table‑lock */
            $this->conn->exec("
                UPDATE consecutivos
                SET valor = LAST_INSERT_ID(valor+1)
                WHERE nombre = 'num_pedido'
            ");
            $orderNumber = (int)$this->conn->lastInsertId();

            /* 2. registrar turno (usa id_pedido que acabamos de generar) */
            $tipoSol    = $data['tipo_solicitud'] ?? 1;
            $nuevoTurno = $this->assignTurno($orderNumber, $tipoSol, $clientId);

            /* 3. ítems del pedido — se llenan también id_cliente, producto,
               prefijos y estado: el panel los usa y si quedan vacíos el
               pedido se muestra mal o se cruza con otro producto. */
            $stmtCat = $this->conn->prepare("
                SELECT nombre, prefijo FROM productos WHERE id_pro = :id LIMIT 1
            ");
            $stmtP = $this->conn->prepare("
                INSERT INTO pedidos
                (id_cliente, id_pro, producto, prefijos, cantidad, fecha,
                 numero_pedido, tipo_solicitud, detalle, tipo_producto,
                 estado, estado_boton)
                VALUES
                (:cli, :id_pro, :producto, :prefijos, :cant, NOW(),
                 :num, :ts, :det, :tipo, 'nuevo', 'nuevo')
            ");

            foreach ($data['products'] ?? [] as $p) {
                if (($p['quantity'] ?? 0) <= 0) {
                    throw new \Exception("Cantidad 0 en producto {$p['id']}", 422);
                }

                $detalle = trim(($p['option'] ?? '').' '.($p['suboption'] ?? '')) ?: 'Sindetalle';

                $stmtCat->execute([':id' => $p['id']]);
                $catInfo = $stmtCat->fetch(PDO::FETCH_ASSOC) ?: [];

                $stmtP->execute([
                    ':cli'      => $clientId,
                    ':id_pro'   => $p['id'],
                    ':producto' => $catInfo['nombre']  ?? '',
                    ':prefijos' => $catInfo['prefijo'] ?? '',
                    ':cant'     => $p['quantity'],
                    ':num'      => $orderNumber,
                    ':ts'       => $tipoSol,
                    ':det'      => $detalle,
                    ':tipo'     => $p['type']
                ]);
            }

            /* 4. comentario opcional */
            if (!empty($data['comments'])) {
                $this->conn->prepare("
                    INSERT INTO comentarios (id_pedido, comentario)
                    VALUES (:id, :com)
                ")->execute([
                    ':id'  => $orderNumber,
                    ':com' => $data['comments']
                ]);
            }

            $this->conn->commit();

            return [
                'status'       => 'success',
                'order_number' => $orderNumber,
                'turno'        => $nuevoTurno
            ];

        } catch (PDOException|\Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /*──────────────────────────  Helpers privados  ────────────────────*/
    private function assignTurno(int $orderNumber, int $tipoSol, int $clientId): int
    {
        /* siguiente turno del día para ese tipo: contador atómico por
           día+tipo (turnos_contador). Sustituye al SELECT MAX(turno)
           FOR UPDATE que bloqueaba turnero y serializaba los pedidos. */
        $this->conn->prepare("
            INSERT INTO turnos_contador (fecha, tipo, valor)
            VALUES (CURDATE(), :ts, LAST_INSERT_ID(1))
            ON DUPLICATE KEY UPDATE valor = LAST_INSERT_ID(valor + 1)
        ")->execute([':ts' => $tipoSol]);
        $turno = (int)$this->conn->lastInsertId();

        /* insertar turno ligado al pedido */
        $this->conn->prepare("
            INSERT INTO turnero
            (id_pedido, turno, fecha, tipo_solicitud, estado, id_cliente)
            VALUES
            (:id, :turn, NOW(), :ts, 'nuevo', :cli)
        ")->execute([
            ':id'   => $orderNumber,
            ':turn' => $turno,
            ':ts'   => $tipoSol,
            ':cli'  => $clientId
        ]);

        return $turno;
    }
}
