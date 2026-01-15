<?php
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) return $default;
    return $value;
}

function db() {
    $host = env('DB_HOST', '127.0.0.1');
    $name = env('DB_NAME');
    $user = env('DB_USER');
    $pass = env('DB_PASS');   // WAJIB ADA
    $port = env('DB_PORT', 3306);

    if (!$name || !$user || $pass === null) {
        throw new Exception("Database environment variables missing");
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}
