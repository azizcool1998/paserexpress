<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$args = [];
foreach ($argv as $a) {
    if (strpos($a, "--") === 0 && str_contains($a, "=")) {
        [$k, $v] = explode("=", substr($a, 2), 2);
        $args[$k] = $v;
    }
}

$username = $args['username'] ?? '';
$password = $args['password'] ?? '';
$email    = $args['email'] ?? '';
$first    = $args['first'] ?? '';
$last     = $args['last'] ?? '';
$wa       = $args['wa'] ?? '';
$role     = 'admin';
$is_admin = (int)($args['is_admin'] ?? 1);

if ($username==='' || $password==='' || $email==='' || $first==='' || $last==='' || $wa==='') {
    exit("Missing required fields.\n");
}

$pdo = db();
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
$stmt->execute([$username]);
$exist = $stmt->fetch();

if ($exist) {
    $pdo->prepare("UPDATE users SET username=?, first_name=?, last_name=?, email=?, whatsapp=?, password_hash=?, role=?, is_admin=?, is_active=1 WHERE id=?")
        ->execute([$username,$first,$last,$email,$wa,$hash,$role,$is_admin,$exist['id']]);
    echo "Admin updated.\n";
} else {
    $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, whatsapp, password_hash, role, is_admin, is_active) VALUES (?,?,?,?,?,?,?,?,1)")
        ->execute([$username,$first,$last,$email,$wa,$hash,$role,$is_admin]);
    echo "Admin created.\n";
}
