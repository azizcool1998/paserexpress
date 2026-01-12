<?php
declare(strict_types=1);

// Usage:
// php src/cli/seed_admin.php --username=admin --password=... --email=... --first=... --last=... --wa=... --role=admin --is_admin=1

require_once __DIR__ . '/../includes/bootstrap.php';

$args = [];
foreach ($argv as $a) {
    if (str_starts_with($a, '--') && str_contains($a, '=')) {
        [$k, $v] = explode('=', substr($a, 2), 2);
        $args[$k] = $v;
    }
}

$username = trim($args['username'] ?? '');
$password = (string)($args['password'] ?? '');
$email    = trim($args['email'] ?? '');
$first    = trim($args['first'] ?? '');
$last     = trim($args['last'] ?? '');
$wa       = trim($args['wa'] ?? '');
$role     = trim($args['role'] ?? 'admin');
$is_admin = (int)($args['is_admin'] ?? 1);

if ($username==='' || $password==='' || $email==='' || $first==='' || $last==='' || $wa==='') {
    fwrite(STDERR, "Missing required fields.\n");
    exit(1);
}

$validRoles = ['pelanggan','driver','admin'];
if (!in_array($role, $validRoles, true)) $role = 'admin';

$pdo = db();
$pdo->beginTransaction();

try {
    // cek existing by username/email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
    $stmt->execute([$username, $email]);
    $exists = $stmt->fetch();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($exists) {
        $id = (int)$exists['id'];
        $stmt = $pdo->prepare("UPDATE users
            SET username=?, first_name=?, last_name=?, email=?, whatsapp=?, password_hash=?, role=?, is_admin=?, is_active=1
            WHERE id=?");
        $stmt->execute([$username,$first,$last,$email,$wa,$hash,$role,$is_admin,$id]);
        echo "Admin updated (id={$id}).\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, whatsapp, password_hash, role, is_admin, is_active)
            VALUES (?,?,?,?,?,?,?,?,1)");
        $stmt->execute([$username,$first,$last,$email,$wa,$hash,$role,$is_admin]);
        $id = (int)$pdo->lastInsertId();
        echo "Admin created (id={$id}).\n";
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Error: ".$e->getMessage()."\n");
    exit(1);
}
