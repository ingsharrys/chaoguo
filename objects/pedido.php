<?php
class Pedido {
    private $conn;
    private $table_name = "pedidos";

    public $id_pedido;
    public $id_cliente;
    public $id_produ;
    public $producto;
    public $prefijos;
    public $cantidad;
    public $fecha;
    public $numero_pedido;
    public $estado;
    public $id_mesero;
    public $comentario;
    public $tipo_solicitud;
    public $estado_boton;
    public $detalle;
    public $tipo_producto;
    public $mesa; // Añadir el campo mesa
    private $last_error;

    public function __construct($db) {
        $this->conn = $db;
    }

    function create() {
        // Establecer la zona horaria de Colombia
        date_default_timezone_set('America/Bogota');

        $query = "INSERT INTO " . $this->table_name . "
                SET
                    id_cliente=:id_cliente, id_pro=:id_pro, producto=:producto, prefijos=:prefijos, cantidad=:cantidad, fecha=:fecha, numero_pedido=:numero_pedido, estado=:estado, tipo_solicitud=:tipo_solicitud, estado_boton='nuevo', detalle=:detalle, tipo_producto=:tipo_producto, mesa=:mesa, mesero=:id_mesero";

        $stmt = $this->conn->prepare($query);

        // Obtener la fecha y hora actual en la zona horaria de Colombia
        $this->fecha = date('Y-m-d H:i:s');

        $this->id_cliente = htmlspecialchars(strip_tags($this->id_cliente));
        $this->id_produ = htmlspecialchars(strip_tags($this->id_produ));
        $this->producto = htmlspecialchars(strip_tags($this->producto));
        $this->prefijos = htmlspecialchars(strip_tags($this->prefijos));
        $this->cantidad = htmlspecialchars(strip_tags($this->cantidad));
        $this->numero_pedido = htmlspecialchars(strip_tags($this->numero_pedido));
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        $this->tipo_solicitud = htmlspecialchars(strip_tags($this->tipo_solicitud));
        $this->detalle = htmlspecialchars(strip_tags($this->detalle));
        $this->tipo_producto = htmlspecialchars(strip_tags($this->tipo_producto));
        $this->mesa = htmlspecialchars(strip_tags($this->mesa));
        $this->id_mesero = htmlspecialchars(strip_tags($this->id_mesero));

        $stmt->bindParam(":id_cliente", $this->id_cliente);
        $stmt->bindParam(":id_pro", $this->id_produ);
        $stmt->bindParam(":producto", $this->producto);
        $stmt->bindParam(":prefijos", $this->prefijos);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":numero_pedido", $this->numero_pedido);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":tipo_solicitud", $this->tipo_solicitud);
        $stmt->bindParam(":detalle", $this->detalle);
        $stmt->bindParam(":tipo_producto", $this->tipo_producto);
        $stmt->bindParam(":mesa", $this->mesa);
        $stmt->bindParam(":id_mesero", $this->id_mesero);

        if($stmt->execute()){
            return true;
        }

        $this->last_error = $stmt->errorInfo();
        return false;
    }

    function createComment($numero_pedido, $comentario) {
        $query = "INSERT INTO comentarios SET id_pedido=:id_pedido, comentario=:comentario";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_pedido", $numero_pedido);
        $stmt->bindParam(":comentario", htmlspecialchars(strip_tags($comentario)));

        if($stmt->execute()){
            return true;
        }

        $this->last_error = $stmt->errorInfo();
        return false;
    }

    public function getLastError() {
        return $this->last_error;
    }
}
?>
