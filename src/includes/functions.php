<?php
/**
 * PaserExpress - Global Helper Functions (Final Version)
 * -----------------------------------------------------
 * - env()    → ambil nilai dari .env
 * - redirect() → aman untuk operasi routing
 * - view()   → load template view
 * - json()   → output JSON API
 * - csrf()   → CSRF token generator/validator
 * - sanitize() → anti-XSS
 * - session_start_secure() → session aman
 */

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        static $env = null;

        if ($env === null) {
            $env = [];

            $file = dirname(__DIR__, 2) . '/.env';
            if (is_file($file)) {
                foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    if (str_starts_with(trim($line), '#')) continue;
                    if (!str_contains($line, '=')) continue;

                    [$k, $v] = explode('=', $line, 2);
                    $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"");
                }
            }
        }

        return $env[$key] ?? $default;
    }
}

/* ------------------------------
    SESSION SECURE
-------------------------------- */
if (!function_exists('session_start_secure')) {
    function session_start_secure()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_samesite' => 'Strict',
                'use_strict_mode' => 1,
            ]);
        }
    }
}

/* ------------------------------
    SANITIZE (Anti-XSS)
-------------------------------- */
if (!function_exists('sanitize')) {
    function sanitize($str)
    {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

/* ------------------------------
    CSRF TOKEN
-------------------------------- */
if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        session_start_secure();
        if (!isset($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }
}

if (!function_exists('csrf_validate')) {
    function csrf_validate($token)
    {
        session_start_secure();
        return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }
}

/* ------------------------------
    REDIRECT
-------------------------------- */
if (!function_exists('redirect')) {
    function redirect($url)
    {
        header("Location: $url");
        exit;
    }
}

/* ------------------------------
    JSON OUTPUT
-------------------------------- */
if (!function_exists('json')) {
    function json($data, int $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

/* ------------------------------
    RENDER VIEW
-------------------------------- */
if (!function_exists('view')) {
    function view($file, $vars = [])
    {
        $path = dirname(__DIR__) . '/views/' . $file . '.php';
        if (!file_exists($path)) {
            die("View not found: $file");
        }
        extract($vars);
        include $path;
    }
}
