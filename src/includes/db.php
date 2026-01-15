<?php
/**
 * PaserExpress - Database Connection (Final Version)
 * --------------------------------------------------
 * - Uses PDO (MySQL/MariaDB)
 * - Reads credentials from env()
 * - Auto retry 3x if MariaDB not ready
 * - Singleton instance
 */

if (!function_exists('db')) {
    function db(): PDO
    {
        static $pdo = null;
        if ($pdo !== null) {
            return $pdo;
        }

        // Load database settings
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $name = env('DB_NAME');
        $user = env('DB_USER');
        $pass = env('DB_PASS');

        if (!$name || !$user) {
            die("Fatal DB Error: Missing DB_NAME or DB_USER in .env\n");
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Try connect 3 times
        $attempts = 3;
        $lastError = null;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $pdo = new PDO($dsn, $user, $pass, $options);

                // Enable strict SQL mode
                $pdo->exec("SET SESSION sql_mode='STRICT_ALL_TABLES'");
                return $pdo;

            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                error_log("[DB] Connection failed (attempt {$i}/{$attempts}): " . $lastError);
                sleep(1); // wait before retry
            }
        }

        // If all attempts fail → fatal
        die("Fatal DB Error: Cannot connect to database.\nLast error: {$lastError}\n");
    }
}
