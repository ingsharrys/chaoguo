<?php
/**
 * DIAGNÓSTICO DEL LOGIN Y LA BASE DE DATOS - ChaoGuo
 *
 * Ejecutar desde la terminal del cPanel, dentro de la carpeta del proyecto:
 *     php diagnostico.php
 *
 * IMPORTANTE: borrar este archivo cuando termine el diagnóstico:
 *     rm diagnostico.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "==============================================\n";
echo " DIAGNOSTICO ChaoGuo - " . date('Y-m-d H:i:s') . "\n";
echo "==============================================\n\n";

echo "[1] Version de PHP: " . PHP_VERSION . "\n\n";

/* ── 2. Conexión a la base de datos ── */
echo "[2] Conexion a la base de datos...\n";
if (!file_exists(__DIR__ . '/config/database.php')) {
    exit("    ERROR: no existe config/database.php en esta carpeta. ¿Estas en la carpeta correcta?\n");
}
require_once __DIR__ . '/config/database.php';

try {
    $db = (new Database())->getConnection();
    if (!$db) exit("    ERROR: getConnection() devolvio null. Revisa credenciales en config/database.php\n");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $nombreBD = $db->query("SELECT DATABASE()")->fetchColumn();
    echo "    OK - Conectado a la base de datos: '$nombreBD'\n\n";
} catch (Throwable $e) {
    exit("    ERROR de conexion: " . $e->getMessage() . "\n    Revisa config/database.php\n");
}

/* ── 3. Tablas necesarias ── */
echo "[3] Tablas necesarias...\n";
foreach (['users', 'turnero', 'pedidos', 'mesas', 'clientes', 'consecutivos'] as $tabla) {
    $existe = $db->query("SHOW TABLES LIKE '$tabla'")->fetchColumn();
    echo "    " . ($existe ? "OK    " : "FALTA ") . $tabla . "\n";
}
echo "\n";

/* ── 4. Columnas nuevas de pedidos ── */
echo "[4] Columnas de la tabla pedidos...\n";
$cols = $db->query("SHOW COLUMNS FROM pedidos")->fetchAll(PDO::FETCH_COLUMN);
foreach (['id_cliente', 'producto', 'prefijos', 'estado', 'estado_boton'] as $col) {
    echo "    " . (in_array($col, $cols) ? "OK    " : "FALTA ") . $col . "\n";
}
$extra = $db->query("SHOW COLUMNS FROM pedidos LIKE 'id_pedido'")->fetch(PDO::FETCH_ASSOC);
echo "    id_pedido Extra: '" . ($extra['Extra'] ?? '?') . "'"
   . (strpos($extra['Extra'] ?? '', 'auto_increment') !== false ? " (OK)" : " (FALTA auto_increment!)") . "\n\n";

/* ── 5. Usuarios registrados ── */
echo "[5] Usuarios en la tabla users...\n";
try {
    $usuarios = $db->query("SELECT id, username, email, LEFT(password,7) AS hash_inicio FROM users")->fetchAll(PDO::FETCH_ASSOC);
    if (!$usuarios) {
        echo "    ERROR: la tabla users esta VACIA. Ejecuta la seccion 2 de database/migracion_app_nueva.sql\n\n";
    } else {
        foreach ($usuarios as $u) {
            $tipoHash = (strpos($u['hash_inicio'], '$2y$') === 0) ? 'bcrypt OK' : 'FORMATO RARO: ' . $u['hash_inicio'];
            echo "    id={$u['id']}  email={$u['email']}  hash={$tipoHash}\n";
        }
        echo "\n";
    }
} catch (Throwable $e) {
    echo "    ERROR: " . $e->getMessage() . "\n\n";
}

/* ── 6. Prueba real de la contraseña temporal ── */
echo "[6] Prueba de login con dario.charry.ramos@gmail.com / ChaoGuo2026* ...\n";
try {
    $stmt = $db->prepare("SELECT password FROM users WHERE email = :e LIMIT 1");
    $stmt->execute([':e' => 'dario.charry.ramos@gmail.com']);
    $hash = $stmt->fetchColumn();
    if (!$hash) {
        echo "    ERROR: no existe usuario con ese email en ESTA base de datos ('$nombreBD').\n\n";
    } else {
        echo "    " . (password_verify('ChaoGuo2026*', $hash)
            ? "OK - La contraseña ChaoGuo2026* SI corresponde al hash guardado.\n\n"
            : "ERROR: el hash guardado NO corresponde a ChaoGuo2026*.\n\n");
    }
} catch (Throwable $e) {
    echo "    ERROR: " . $e->getMessage() . "\n\n";
}

/* ── 7. Version del AuthController desplegado ── */
echo "[7] AuthController desplegado en el servidor...\n";
$auth = file_get_contents(__DIR__ . '/controllers/AuthController.php');
if (strpos($auth, 'RECAPTCHA_ENABLED') === false) {
    echo "    ERROR: es la version VIEJA (reCAPTCHA obligatorio bloquea el login).\n";
    echo "    El servidor NO tiene los archivos nuevos: revisa que el git checkout haya funcionado.\n\n";
} elseif (preg_match('/RECAPTCHA_ENABLED\s*=\s*false/', $auth)) {
    echo "    OK - Version nueva con reCAPTCHA desactivado.\n\n";
} else {
    echo "    AVISO: version nueva pero con reCAPTCHA ACTIVADO.\n\n";
}

/* ── 8. Ultimas lineas del log de errores PHP (si existe) ── */
echo "[8] Logs de errores encontrados...\n";
foreach ([__DIR__ . '/error_log', __DIR__ . '/views/error_log', __DIR__ . '/controllers/error_log'] as $log) {
    if (file_exists($log)) {
        $mb = round(filesize($log) / 1048576, 1);
        echo "    --- $log ({$mb} MB, ultimas lineas) ---\n";
        /* leer solo el final del archivo para no agotar la memoria */
        $fh = fopen($log, 'r');
        fseek($fh, max(0, filesize($log) - 2048));
        $cola = stream_get_contents($fh);
        fclose($fh);
        $lineas = explode("\n", trim($cola));
        foreach (array_slice($lineas, -5) as $l) echo "    $l\n";
        echo "\n";
        if ($mb > 50) echo "    AVISO: este log pesa {$mb} MB - conviene borrarlo con: rm $log\n\n";
    }
}
/* ── 9. Prueba real de registro de pedido (con ROLLBACK: no deja rastro) ── */
echo "[9] Prueba real de registro de pedido (igual que la app, se deshace al final)...\n";
try {
    $db->beginTransaction();

    $db->exec("UPDATE consecutivos SET valor = LAST_INSERT_ID(valor + 1) WHERE nombre = 'num_pedido'");
    $np = (int) $db->lastInsertId();
    if (!$np) throw new Exception("consecutivos no devolvio numero de pedido (¿falta la fila 'num_pedido'?)");

    $db->prepare("INSERT INTO turnero (id_pedido, turno, fecha, tipo_solicitud, estado, id_cliente)
                  VALUES (:np, 999, NOW(), 52, 'nuevo', 1)")->execute([':np' => $np]);

    $prod = $db->query("SELECT id_pro, nombre, prefijo FROM productos LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$prod) throw new Exception('la tabla productos esta vacia');

    $db->prepare("INSERT INTO pedidos
        (id_cliente, id_pro, producto, prefijos, cantidad, numero_pedido,
         tipo_solicitud, detalle, tipo_producto, mesa, mesero, estado, estado_boton, fecha)
        VALUES (1, :id_pro, :producto, :prefijos, 1, :np, 52, '', 'PRUEBA', 1, NULL, 'nuevo', 'nuevo', NOW())")
       ->execute([':id_pro' => $prod['id_pro'], ':producto' => $prod['nombre'],
                  ':prefijos' => $prod['prefijo'], ':np' => $np]);

    $db->rollBack();
    echo "    OK - EL REGISTRO DE PEDIDOS FUNCIONA (pedido de prueba #$np creado y deshecho).\n\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "    *** ERROR EXACTO AL REGISTRAR PEDIDO ***\n    " . $e->getMessage() . "\n\n";
}

echo "\n==============================================\n";
echo " FIN - Envia toda esta salida para analizarla.\n";
echo " Luego borra este archivo:  rm diagnostico.php\n";
echo "==============================================\n";
