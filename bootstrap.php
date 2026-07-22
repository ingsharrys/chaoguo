<?php
// bootstrap.php (inclúyelo lo más arriba posible en TODAS las páginas)
define('APP_DEBUG', true); // ponlo en false en producción

ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);

$logDir = __DIR__ . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/php-error.log';
ini_set('log_errors', '1');
ini_set('error_log', $logFile);

// helper corto
function app_log($msg) {
    error_log('[APP] ' . $msg);
}
