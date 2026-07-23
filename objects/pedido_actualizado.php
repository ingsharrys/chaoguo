<?php
class Pedido {
    private $conn;
    private $table_name = "pedidos";

    // Sólo las propiedades que sí existen en la tabla `pedidos`
    public $id_pedido;       // (AUTO_INCREMENT en la DB)
    public $id_produ;        // mapeado a columna `id_pro`
    public $cantidad;        // mapeado a columna `cantidad`
    public $fecha;           // mapeado a columna `fecha`
    public $numero_pedido;   // mapeado a columna `numero_pedido`
    public $tipo_solicitud;  // mapeado a columna `tipo_solicitud`
    public $detalle;         // mapeado a columna `detalle`
    public $tipo_producto;   // mapeado a columna `tipo_producto`
    public $mesa;            // mapeado a columna `mesa`
    public $mesero;          // mapeado a columna `mesero`
    public $id_cliente = 1;  // mapeado a columna `id_cliente` (requerido por el panel admin)
    public $producto = '';   // mapeado a columna `producto` (nombre, requerido por el panel admin)
    public $prefijos = '';   // mapeado a columna `prefijos`
    public $estado = 'nuevo';        // mapeado a columna `estado`
    public $estado_boton = 'nuevo';  // mapeado a columna `estado_boton`

    // Atributo para almacenar el último error
    private $last_error;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * create()
     * Inserta un nuevo producto en la tabla `pedidos`.
     * Columns: id_pro, cantidad, fecha, numero_pedido,
     *          tipo_solicitud, detalle, tipo_producto, mesa, mesero
     */
    public function create() {
        date_default_timezone_set('America/Bogota');
        $this->fecha = date('Y-m-d H:i:s');  // Genera la fecha actual

        // Insert con las columnas reales de tu tabla `pedidos`.
        // Incluye id_cliente, producto, prefijos, estado y estado_boton:
        // sin ellas el panel admin no muestra el pedido.
        $query = "
            INSERT INTO {$this->table_name}
            (id_cliente, id_pro, producto, prefijos, cantidad, fecha, numero_pedido,
             tipo_solicitud, detalle, tipo_producto, mesa, mesero, estado, estado_boton)
            VALUES
            (:id_cliente, :id_pro, :producto, :prefijos, :cantidad, :fecha, :numero_pedido,
             :tipo_solicitud, :detalle, :tipo_producto, :mesa, :mesero, :estado, :estado_boton)
        ";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->id_produ       = htmlspecialchars(strip_tags($this->id_produ));
        $this->cantidad       = htmlspecialchars(strip_tags($this->cantidad));
        $this->numero_pedido  = htmlspecialchars(strip_tags($this->numero_pedido));
        $this->tipo_solicitud = htmlspecialchars(strip_tags($this->tipo_solicitud));
        $this->detalle        = htmlspecialchars(strip_tags($this->detalle));
        $this->tipo_producto  = htmlspecialchars(strip_tags($this->tipo_producto));
        $this->mesa           = htmlspecialchars(strip_tags($this->mesa));
        /* mesero es columna numérica: cadena vacía o null deben guardarse
           como NULL, nunca como '' (el modo estricto de MySQL lo rechaza) */
        $this->mesero         = ($this->mesero === '' || $this->mesero === null) ? null : (int) $this->mesero;
        $this->id_cliente     = htmlspecialchars(strip_tags($this->id_cliente ?: 1));
        $this->producto       = htmlspecialchars(strip_tags($this->producto ?? ''));
        $this->prefijos       = htmlspecialchars(strip_tags($this->prefijos ?? ''));
        $this->estado         = htmlspecialchars(strip_tags($this->estado ?: 'nuevo'));
        $this->estado_boton   = htmlspecialchars(strip_tags($this->estado_boton ?: 'nuevo'));

        // Asignar parámetros
        $stmt->bindParam(":id_cliente",     $this->id_cliente);
        $stmt->bindParam(":id_pro",         $this->id_produ);
        $stmt->bindParam(":producto",       $this->producto);
        $stmt->bindParam(":prefijos",       $this->prefijos);
        $stmt->bindParam(":cantidad",       $this->cantidad);
        $stmt->bindParam(":fecha",          $this->fecha);
        $stmt->bindParam(":numero_pedido",  $this->numero_pedido);
        $stmt->bindParam(":tipo_solicitud", $this->tipo_solicitud);
        $stmt->bindParam(":detalle",        $this->detalle);
        $stmt->bindParam(":tipo_producto",  $this->tipo_producto);
        $stmt->bindParam(":mesa",           $this->mesa);
        $stmt->bindParam(":mesero",         $this->mesero);
        $stmt->bindParam(":estado",         $this->estado);
        $stmt->bindParam(":estado_boton",   $this->estado_boton);

        // Ejecutar
        if($stmt->execute()){
            return true;
        }

        // Si falla, registrar el error
        $this->last_error = $stmt->errorInfo();
        return false;
    }

    /**
     * updateProduct($producto, $numero_pedido)
     * Actualiza un producto existente en `pedidos`, identificando por (id_pro, numero_pedido).
     * Actualiza sólo: cantidad, detalle, tipo_producto
     * (Ajusta si quieres actualizar mesa, mesero, etc.)
     */
    public function updateProduct($producto, $numero_pedido) {
        /* El WHERE incluye tipo_producto: un mismo id_pro puede estar en el
           pedido con varios tipos (Grande, Pequeño...) y solo debe
           actualizarse la fila del tipo editado. */
        $query = "
            UPDATE {$this->table_name}
            SET
                cantidad       = :cantidad,
                detalle        = :detalle,
                fecha          = NOW()  -- opcional, si quieres actualizar fecha
            WHERE id_pro        = :id_pro
              AND numero_pedido = :numero_pedido
              AND tipo_producto = :tipo_producto
        ";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $producto->cantidad      = htmlspecialchars(strip_tags($producto->cantidad));
        $producto->detalle       = htmlspecialchars(strip_tags($producto->detalle));
        $producto->tipo_producto = htmlspecialchars(strip_tags($producto->tipo_prod));
        $producto->id_pro        = htmlspecialchars(strip_tags($producto->id_pro));
        $numero_pedido           = htmlspecialchars(strip_tags($numero_pedido));

        // Asignar parámetros
        $stmt->bindParam(":cantidad",      $producto->cantidad);
        $stmt->bindParam(":detalle",       $producto->detalle);
        $stmt->bindParam(":tipo_producto", $producto->tipo_prod);
        $stmt->bindParam(":id_pro",        $producto->id_pro);
        $stmt->bindParam(":numero_pedido", $numero_pedido);

        // Ejecutar
        if($stmt->execute()){
            return true;
        }

        $this->last_error = $stmt->errorInfo();
        return false;
    }

    /**
     * checkIfProductExists($id_pro, $numero_pedido)
     * Retorna true si existe un registro en `pedidos` con (id_pro, numero_pedido).
     */
    public function checkIfProductExists($id_pro, $numero_pedido, $tipo_producto = null) {
        $query = "
            SELECT COUNT(*) AS total
            FROM {$this->table_name}
            WHERE id_pro = :id_pro
              AND numero_pedido = :numero_pedido
        ";
        if ($tipo_producto !== null && $tipo_producto !== '') {
            $query .= " AND tipo_producto = :tipo_producto";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_pro", $id_pro);
        $stmt->bindParam(":numero_pedido", $numero_pedido);
        if ($tipo_producto !== null && $tipo_producto !== '') {
            $stmt->bindParam(":tipo_producto", $tipo_producto);
        }
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row['total'] > 0;
    }

    /**
     * createComment($numero_pedido, $comentario)
     * Inserta un comentario en la tabla `comentarios` (ajusta si tu tabla es distinta).
     */
    public function createComment($numero_pedido, $comentario) {
        $query = "
            INSERT INTO comentarios
            SET
                id_pedido   = :id_pedido,
                comentario  = :comentario
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_pedido",  $numero_pedido);
        $stmt->bindParam(":comentario", htmlspecialchars(strip_tags($comentario)));

        if($stmt->execute()){
            return true;
        }

        $this->last_error = $stmt->errorInfo();
        return false;
    }

    /**
     * getLastError()
     * Devuelve el último error capturado (array con [SQLSTATE, error_code, error_message]).
     */
    public function getLastError() {
        return $this->last_error;
    }
}
