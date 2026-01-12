<?php
declare(strict_types=1);

function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $ok = is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    if (!$ok) {
        http_response_code(419);
        echo "CSRF token invalid.";
        exit;
    }
}
