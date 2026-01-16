<?php

require_once __DIR__ . "/functions.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/settings.php"; // OPTIONAL kalau mau dipisah

session_start_secure();

spl_autoload_register(function ($class) {
    $path = dirname(__DIR__) . '/controllers/' . $class . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});
