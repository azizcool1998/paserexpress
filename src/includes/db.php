<?php

function db()
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = env("DB_HOST", "127.0.0.1");
    $port = env("DB_PORT", "3306");
    $name = env("DB_NAME", "");
    $user = env("DB_USER", "");
    $pass = env("DB_PASS", "");

    if ($name === "" || $user === "") {
        die("Database config missing in .env");
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die("DB connect failed: " . $e->getMessage());
    }

    return $pdo;
}
