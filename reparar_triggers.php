<?php
/**
 * REPARADOR UNIVERSAL DE DEFINERS FANTASMA - ChaoGuo
 *
 * La base de datos arrastra objetos (triggers, vistas, procedimientos)
 * firmados por usuarios MySQL de hostings anteriores que ya no existen
 * (restaurantechaog_qsevgfn, hgarzon_pablhbb, ...). MySQL bloquea su
 * ejecución con el error 1449. Este script ESCANEA TODO y regenera cada
 * objeto con el usuario actual de la aplicación, conservando su contenido.
 *
 * Uso:    php reparar_triggers.php
 * Al terminar:  rm reparar_triggers.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Base de datos: " . $db->query("SELECT DATABASE()")->fetchColumn() . "\n";
echo "Usuario actual: " . $db->query("SELECT CURRENT_USER()")->fetchColumn() . "\n\n";

/* ═══════════ 1. TRIGGERS: regenerar TODOS con el usuario actual ═══════════ */
echo "[1] TRIGGERS\n";
$triggers = $db->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC);
if (!$triggers) echo "    (no hay triggers)\n";
foreach ($triggers as $t) {
    $nombre = $t['Trigger'];
    echo "    $nombre  (definer actual: {$t['Definer']})\n";
    try {
        $db->exec("DROP TRIGGER IF EXISTS `$nombre`");
        $db->exec("CREATE TRIGGER `$nombre` {$t['Timing']} {$t['Event']} ON `{$t['Table']}` FOR EACH ROW {$t['Statement']}");
        echo "        -> OK regenerado\n";
    } catch (Throwable $e) {
        echo "        -> ERROR: " . $e->getMessage() . "\n";
    }
}

/* ═══════════ 2. VISTAS ═══════════ */
echo "\n[2] VISTAS\n";
$vistas = $db->query("SELECT TABLE_NAME, VIEW_DEFINITION
                      FROM information_schema.VIEWS
                      WHERE TABLE_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_ASSOC);
if (!$vistas) echo "    (no hay vistas)\n";
foreach ($vistas as $v) {
    $nombre = $v['TABLE_NAME'];
    echo "    $nombre\n";
    try {
        $db->exec("CREATE OR REPLACE VIEW `$nombre` AS " . $v['VIEW_DEFINITION']);
        echo "        -> OK regenerada\n";
    } catch (Throwable $e) {
        echo "        -> ERROR: " . $e->getMessage() . "\n";
    }
}

/* ═══════════ 3. PROCEDIMIENTOS Y FUNCIONES ═══════════ */
echo "\n[3] PROCEDIMIENTOS / FUNCIONES\n";
$rutinas = $db->query("SELECT ROUTINE_NAME, ROUTINE_TYPE
                       FROM information_schema.ROUTINES
                       WHERE ROUTINE_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_ASSOC);
if (!$rutinas) echo "    (no hay procedimientos ni funciones)\n";
foreach ($rutinas as $r) {
    $nombre = $r['ROUTINE_NAME'];
    $tipo   = $r['ROUTINE_TYPE']; // PROCEDURE o FUNCTION
    echo "    $tipo $nombre\n";
    try {
        $fila = $db->query("SHOW CREATE $tipo `$nombre`")->fetch(PDO::FETCH_ASSOC);
        $ddl  = $fila["Create " . ucfirst(strtolower($tipo))] ?? null;
        if (!$ddl) { echo "        -> AVISO: sin permiso para leer su código; omitido\n"; continue; }
        $ddl = preg_replace('/DEFINER\s*=\s*\S+\s+/i', '', $ddl, 1);
        $db->exec("DROP $tipo IF EXISTS `$nombre`");
        $db->exec($ddl);
        echo "        -> OK regenerado\n";
    } catch (Throwable $e) {
        echo "        -> ERROR: " . $e->getMessage() . "\n";
    }
}

/* ═══════════ 4. Verificación: ¿queda algún definer fantasma? ═══════════ */
echo "\n[4] Definers después de la reparación:\n";
foreach ($db->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC) as $t) {
    echo "    trigger {$t['Trigger']} -> {$t['Definer']}\n";
}

/* ═══════════ 5. Prueba real de pago (INSERT en caja con ROLLBACK) ═══════════ */
echo "\n[5] Prueba real de pago (se deshace al final, no deja rastro)...\n";
try {
    $db->beginTransaction();
    $db->prepare("INSERT INTO caja (id_pedidoc, costo, m_pago, descuento, efectivo, cajero, fecha_caja, id_cajero)
                  VALUES (0, 0, 'PRUEBA', 0, 0, 'diagnostico', CURDATE(), 0)")->execute();
    $db->rollBack();
    echo "    OK - LOS PAGOS EN CAJA FUNCIONAN.\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "    *** ERROR EXACTO AL PAGAR ***\n    " . $e->getMessage() . "\n";
}

echo "\nListo. Borra este archivo con:  rm reparar_triggers.php\n";
