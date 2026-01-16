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

/* -----------------------------------------------------------
 * SETTINGS SYSTEM — NEW!!
 * Stored in: /config/settings.json
 * ---------------------------------------------------------*/

/**
 * Load settings from JSON file.
 * Always returns array.
 */
function load_settings()
{
    $path = dirname(__DIR__, 2) . "/config/settings.json";

    // file not exists = create default
    if (!file_exists($path)) {
        $default = [
            "APP_NAME"        => "Paser Express",
            "APP_BASE_URL"    => "http://localhost",
            "NODE_DOMAIN"     => "",
            "BACKUP_ENABLED"  => "no",
            "BACKUP_INTERVAL" => "1d"
        ];
        file_put_contents($path, json_encode($default, JSON_PRETTY_PRINT));
        return $default;
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        return [];
    }

    return $data;
}

/**
 * Save settings array to JSON file.
 */
function save_settings($arr)
{
    $path = dirname(__DIR__, 2) . "/config/settings.json";

    if (!is_array($arr)) {
        return false;
    }

    // overwrite entire file
    file_put_contents($path, json_encode($arr, JSON_PRETTY_PRINT));

    return true;
}
