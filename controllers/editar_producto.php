<?php
require_once '../config/database.php';
header('Content-Type: application/json');

/*
 * EDITAR PRODUCTO
 * Reglas de la base de datos que este controlador respeta:
 *  - El trigger `no_delete_precios` PROHÍBE borrar filas de precios
 *    (protege el histórico de ventas). Por eso las variantes existentes
 *    nunca se eliminan: se actualiza su precio o se agregan nuevas.
 *  - Todo se ejecuta dentro de una transacción: si algo falla, el
 *    producto no queda editado a medias.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método de solicitud no permitido.']);
    exit;
}

/* ── 1. Validación de entrada ── */
$id_pro    = filter_var($_POST['id_pro'] ?? null, FILTER_VALIDATE_INT);
$nombre    = trim($_POST['nombre'] ?? '');
$prefijo   = trim($_POST['prefijo'] ?? '');
$cat       = filter_var($_POST['cat'] ?? null, FILTER_VALIDATE_INT);
$descript  = trim($_POST['descript'] ?? '');
$ti_comida = filter_var($_POST['tcomida'] ?? 0, FILTER_VALIDATE_INT);
$precios   = $_POST['precios'] ?? [];
$tipos     = $_POST['tipos'] ?? [];

if (!$id_pro)        { echo json_encode(['status' => 'error', 'message' => 'ID de producto no válido.']); exit; }
if ($nombre === '')  { echo json_encode(['status' => 'error', 'message' => 'El nombre del producto es obligatorio.']); exit; }
if ($cat === false || $cat === null) { echo json_encode(['status' => 'error', 'message' => 'La categoría no es válida.']); exit; }
if (!is_array($tipos) || !is_array($precios) || count($tipos) !== count($precios) || count($tipos) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Debe enviar al menos una variante con su precio.']); exit;
}

/* Normalizar variantes: tipo sin espacios sobrantes, precio numérico > 0 */
$variantes = [];
foreach ($tipos as $i => $tipo) {
    $tipo   = trim((string) $tipo);
    $precio = filter_var($precios[$i], FILTER_VALIDATE_FLOAT);
    if ($tipo === '') {
        echo json_encode(['status' => 'error', 'message' => 'Hay una variante sin nombre.']); exit;
    }
    if ($precio === false || $precio <= 0) {
        echo json_encode(['status' => 'error', 'message' => "El precio de la variante \"$tipo\" no es válido."]); exit;
    }
    $variantes[$tipo] = $precio;   // si el formulario repite un tipo, gana el último
}

/* ── 2. Subir la imagen nueva (si la hay) antes de tocar la BD ── */
$imgName = null;
if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
    $img = $_FILES['img'];

    $extension = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
    $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif'];
    if (!in_array($extension, $permitidas)) {
        echo json_encode(['status' => 'error', 'message' => 'Formato de imagen no permitido (usa JPG, PNG o WEBP).']);
        exit;
    }

    $filename = pathinfo($img['name'], PATHINFO_FILENAME);
    $filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
    $filename = preg_replace("/[^a-zA-Z0-9\.\-\_]/", "", $filename);
    $imgName  = 'producto-' . $filename . '-' . uniqid() . '.' . $extension;

    if (!move_uploaded_file($img['tmp_name'], '../path/to/productos/' . $imgName)) {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar la imagen.']);
        exit;
    }
}

try {
    $conn = (new Database())->getConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* Imagen anterior (para borrarla del disco SOLO si todo sale bien) */
    $stmt = $conn->prepare("SELECT img FROM productos WHERE id_pro = :id");
    $stmt->execute([':id' => $id_pro]);
    $actual = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$actual) {
        echo json_encode(['status' => 'error', 'message' => 'El producto no existe.']);
        exit;
    }
    $imgAnterior = $actual['img'];

    /* ── 3. Transacción: producto + variantes, todo o nada ── */
    $conn->beginTransaction();

    $query = "UPDATE productos
              SET nombre = :nombre, prefijo = :prefijo, cat = :cat,
                  descript = :descript, tcomida = :tcomida"
           . ($imgName !== null ? ", img = :img" : "")
           . " WHERE id_pro = :id_pro";
    $params = [
        ':nombre' => $nombre, ':prefijo' => $prefijo, ':cat' => $cat,
        ':descript' => $descript, ':tcomida' => (int) $ti_comida, ':id_pro' => $id_pro
    ];
    if ($imgName !== null) { $params[':img'] = $imgName; }
    $conn->prepare($query)->execute($params);

    /* Variantes existentes en la BD */
    $stmt = $conn->prepare("SELECT tipo_prod FROM precios WHERE idproduc = :id");
    $stmt->execute([':id' => $id_pro]);
    $existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmtUpd = $conn->prepare("UPDATE precios SET precio = :precio WHERE idproduc = :id AND tipo_prod = :tipo");
    $stmtIns = $conn->prepare("INSERT INTO precios (idproduc, tipo_prod, precio) VALUES (:id, :tipo, :precio)");

    foreach ($variantes as $tipo => $precio) {
        if (in_array($tipo, $existentes, true)) {
            $stmtUpd->execute([':precio' => $precio, ':id' => $id_pro, ':tipo' => $tipo]);
        } else {
            $stmtIns->execute([':id' => $id_pro, ':tipo' => $tipo, ':precio' => $precio]);
        }
    }

    /* Variantes que el formulario omitió: NO se pueden borrar (regla del
       negocio: el trigger de la BD protege el histórico de ventas). Se
       conservan tal cual y se informa con claridad. */
    $omitidas = array_diff($existentes, array_keys($variantes));

    $conn->commit();

    /* ── 4. Borrar la imagen anterior del disco (solo tras el commit) ── */
    if ($imgName !== null && $imgAnterior) {
        $ruta = '../path/to/productos/' . $imgAnterior;
        if (is_file($ruta)) { @unlink($ruta); }
    }

    $mensaje = 'Producto editado con éxito.';
    if (!empty($omitidas)) {
        $mensaje .= ' Aviso: las variantes "' . implode('", "', $omitidas)
                  . '" no se pueden eliminar porque el sistema protege el histórico de precios y ventas; conservaron su precio anterior.';
    }
    echo json_encode(['status' => 'success', 'message' => $mensaje]);

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) { $conn->rollBack(); }
    /* Si la BD falló y habíamos subido imagen nueva, retirarla del disco */
    if ($imgName !== null) {
        $ruta = '../path/to/productos/' . $imgName;
        if (is_file($ruta)) { @unlink($ruta); }
    }
    echo json_encode(['status' => 'error', 'message' => 'Error al editar el producto: ' . $e->getMessage()]);
}
