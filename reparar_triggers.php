<?php
/**
 * REPARAR TRIGGERS CON USUARIO FANTASMA - ChaoGuo
 *
 * Los triggers de la base de datos quedaron firmados (DEFINER) por un
 * usuario MySQL que ya no existe (p. ej. restaurantechaog_qsevgfn), lo que
 * bloquea los pagos en caja con el error 1449. Este script los regenera
 * firmados por el usuario actual de la aplicación, con el mismo contenido.
 *
 * Uso, desde la carpeta del proyecto:
 *     php reparar_triggers.php
 * Borrar al terminar:
 *     rm reparar_triggers.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Conectado a: " . $db->query("SELECT DATABASE()")->fetchColumn() . "\n";
echo "Usuario actual: " . $db->query("SELECT CURRENT_USER()")->fetchColumn() . "\n\n";

echo "[1] Triggers actuales y sus firmantes:\n";
foreach ($db->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC) as $t) {
    echo "    {$t['Trigger']}  (definer: {$t['Definer']})\n";
}

echo "\n[2] Regenerando triggers con el usuario actual...\n";

$triggers = [
    'check_m_pago_not_empty_update' => "
        CREATE TRIGGER `check_m_pago_not_empty_update` BEFORE UPDATE ON `caja` FOR EACH ROW BEGIN
            IF NEW.m_pago IS NULL OR TRIM(NEW.m_pago) = '' THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'm_pago no puede quedar vacío (trigger BEFORE UPDATE)';
            END IF;
        END",
    'tr_caja_m_pago_no_vacio' => "
        CREATE TRIGGER `tr_caja_m_pago_no_vacio` BEFORE INSERT ON `caja` FOR EACH ROW BEGIN
            IF NEW.m_pago IS NULL OR TRIM(NEW.m_pago) = '' THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'm_pago no puede ser nulo o vacío (trigger BEFORE INSERT)';
            END IF;
        END",
    'no_delete_precios' => "
        CREATE TRIGGER `no_delete_precios` BEFORE DELETE ON `precios` FOR EACH ROW BEGIN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'No se pueden eliminar datos de la tabla precios (Trigger Blocked)';
        END",
];

foreach ($triggers as $nombre => $sql) {
    try {
        $db->exec("DROP TRIGGER IF EXISTS `$nombre`");
        $db->exec($sql);
        echo "    OK    $nombre regenerado\n";
    } catch (Throwable $e) {
        echo "    ERROR $nombre -> " . $e->getMessage() . "\n";
    }
}

echo "\n[3] Triggers después de la reparación:\n";
foreach ($db->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC) as $t) {
    echo "    {$t['Trigger']}  (definer: {$t['Definer']})\n";
}

echo "\n[4] Prueba real de pago (INSERT en caja, se deshace al final)...\n";
try {
    $db->beginTransaction();
    $db->prepare("INSERT INTO caja (id_pedidoc, costo, m_pago, descuento, efectivo, cajero, fecha_caja, id_cajero)
                  VALUES (0, 0, 'PRUEBA', 0, 0, 'diagnostico', CURDATE(), 0)")->execute();
    $db->rollBack();
    echo "    OK - LOS PAGOS EN CAJA FUNCIONAN (registro de prueba creado y deshecho).\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "    *** ERROR EXACTO AL PAGAR ***\n    " . $e->getMessage() . "\n";
}

echo "\nListo. Borra este archivo con:  rm reparar_triggers.php\n";
