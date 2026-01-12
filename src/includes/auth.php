<?php
declare(strict_types=1);

function auth_start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

function auth_user(): ?array {
    auth_start();
    return $_SESSION['user'] ?? null;
}

function auth_require_login(): void {
    if (!auth_user()) redirect('/?page=login');
}

function auth_require_admin(): void {
    $u = auth_user();
    if (!$u || ($u['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo "Forbidden.";
        exit;
    }
}

function auth_login(string $username, string $password): bool {
    auth_start();
    $stmt = db()->prepare("SELECT id, username, first_name, last_name, email, whatsapp, password_hash, role, is_admin, is_active FROM users WHERE username=? LIMIT 1");
    $stmt->execute([$username]);
    $u = $stmt->fetch();

    if (!$u) return false;
    if ((int)$u['is_active'] !== 1) return false;
    if (!password_verify($password, $u['password_hash'])) return false;

    unset($u['password_hash']);
    $_SESSION['user'] = $u;
    return true;
}

function auth_logout(): void {
    auth_start();
    $_SESSION = [];
    session_destroy();
}
