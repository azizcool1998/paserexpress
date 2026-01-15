<?php
/**
 * PaserExpress Bootstrap Loader
 * ----------------------------------------
 * - Load .env file
 * - Register environment variables
 * - Setup timezone and error mode
 * - Autoload includes and common functions
 */

// ================================================================
// Load .env file
// ================================================================
$envPath = dirname(__DIR__, 2) . '/.env';

if (!file_exists($envPath)) {
    die("Fatal Error: .env file not found at {$envPath}\n");
}

$envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($envLines as $line) {
    $line = trim($line);

    // Skip comments
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    // Split KEY=VALUE
    $parts = explode('=', $line, 2);
    if (count($parts) !== 2) {
        continue;
    }

    $key   = trim($parts[0]);
    $value = trim($parts[1]);

    // Remove wrapping quotes
    $value = trim($value, "\"'");

    // Set environment variable
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// ================================================================
// Helper: env()
// ================================================================
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

// ================================================================
// Load Database function
// ================================================================
require_once __DIR__ . '/db.php';

// ================================================================
// Load global helper functions
// (buat file functions.php jika diperlukan)
// ================================================================
$functionsFile = __DIR__ . '/functions.php';
if (file_exists($functionsFile)) {
    require_once $functionsFile;
}

// ================================================================
// Timezone & Error Display
// ================================================================
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

if (env('APP_DEBUG', 'false') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ================================================================
// Sessions
// ================================================================
if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
