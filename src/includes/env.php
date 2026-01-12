<?php
declare(strict_types=1);

function env_load(string $path): array {
    if (!file_exists($path)) return [];

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;

        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);

        // strip quotes
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) ||
            (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        $env[$k] = $v;
    }
    return $env;
}

function env_get(string $key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = env_load(__DIR__ . '/../../.env');
    }
    return $cache[$key] ?? $default;
}
