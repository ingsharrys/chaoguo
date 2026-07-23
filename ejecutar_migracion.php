<?php
/**
 * EJECUTOR DE LA MIGRACIÓN - ChaoGuo
 * Ejecuta database/migracion_app_nueva.sql usando la conexión de la app
 * (no necesitas escribir usuario ni contraseña de MySQL).
 *
 * Uso, desde la carpeta del proyecto:
 *     php ejecutar_migracion.php
 *
 * Borrar al terminar:
 *     rm ejecutar_migracion.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Conectado a la base de datos: " . $db->query("SELECT DATABASE()")->fetchColumn() . "\n\n";

$archivo = __DIR__ . '/database/migracion_app_nueva.sql';
if (!file_exists($archivo)) exit("ERROR: no existe $archivo (haz git pull primero)\n");

$sql = file_get_contents($archivo);
$sql = preg_replace('/^\s*--.*$/m', '', $sql);   // quitar comentarios
$sentencias = array_values(array_filter(array_map('trim', explode(';', $sql))));

$ok = 0; $ya = 0; $errores = 0;
foreach ($sentencias as $i => $s) {
    $n = $i + 1;
    $resumen = preg_replace('/\s+/', ' ', mb_substr($s, 0, 70));
    try {
        $db->exec($s);
        echo "OK    [$n] $resumen\n";
        $ok++;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        $inofensivo = (strpos($msg, 'Duplicate key name') !== false
                    || strpos($msg, 'Duplicate column name') !== false
                    || strpos($msg, 'Multiple primary key') !== false
                    || strpos($msg, 'already exists') !== false);
        if ($inofensivo) {
            echo "YA    [$n] $resumen (ya existia, correcto)\n";
            $ya++;
        } else {
            echo "ERROR [$n] $resumen\n      -> $msg\n";
            $errores++;
        }
    }
}

echo "\n=====================================================\n";
echo "Resultado: $ok ejecutadas, $ya ya existian, $errores errores reales.\n";
echo "Ahora ejecuta:  php diagnostico.php   para verificar todo.\n";
echo "Al terminar borra los scripts:  rm ejecutar_migracion.php diagnostico.php\n";
echo "=====================================================\n";
