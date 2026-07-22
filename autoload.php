<?php
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/helpers/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        // Manejo de error si el archivo no existe
        throw new Exception("Unable to load $class_name.");
    }
});
?>
