<?php
declare(strict_types=1);

if (auth_user()) redirect('/?page=welcome');

$error = null;
if (is_post()) {
    csrf_validate($_POST['csrf_token'] ?? null);
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
    } else if (!auth_login($username, $password)) {
        $error = "Login gagal. Cek username/password.";
    } else {
        $u = auth_user();
        if (($u['role'] ?? '') === 'admin') redirect('/?page=admin_dashboard');
        redirect('/?page=welcome');
    }
}
?>
<h1>Login</h1>
<?php if ($error): ?>
  <div class="alert"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" class="card">
  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
  <label>Username</label>
  <input name="username" autocomplete="username" required>

  <label>Password</label>
  <input name="password" type="password" autocomplete="current-password" required>

  <button type="submit">Masuk</button>
</form>
