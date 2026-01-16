<?php

/* -----------------------------------------------------------
 * ENV LOADER
 * ---------------------------------------------------------*/
function env($key, $default = "")
{
    static $vars = null;

    if ($vars === null) {
        $vars = [];
        $path = dirname(__DIR__, 2) . "/.env";
        if (file_exists($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
                if (strpos(trim($line), "=") !== false) {
                    [$k, $v] = explode("=", $line, 2);
                    $vars[trim($k)] = trim($v, "\"");
                }
            }
        }
    }

    return $vars[$key] ?? $default;
}

/* -----------------------------------------------------------
 * VIEW & SECURITY HELPERS
 * ---------------------------------------------------------*/
function redirect($url)
{
    header("Location: $url");
    exit;
}

function view($name, $data = [])
{
    extract($data);
    include dirname(__DIR__) . "/views/{$name}.php";
}

function sanitize($str)
{
    return htmlspecialchars($str, ENT_QUOTES, "UTF-8");
}

function session_start_secure()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
