<?php
declare(strict_types=1);
auth_require_login();
auth_require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;

$data = [
  'username' => '',
  'first_name' => '',
  'last_name' => '',
  'email' => '',
  'whatsapp' => '',
  'role' => 'pelanggan',
  'is_admin' => 0,
  'is_active' => 1,
];

if ($editing) {
  $stmt = db()->prepare("SELECT id, username, first_name, last_name, email, whatsapp, role, is_admin, is_active FROM users WHERE id=?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) { echo "<p>User tidak ditemukan.</p>"; return; }
  $data = array_merge($data, $row);
}

$error = null;
$success = null;

if (is_post()) {
  csrf_validate($_POST['csrf_token'] ?? null);

  $data['username'] = trim((string)($_POST['username'] ?? ''));
  $data['first_name'] = trim((string)($_POST['first_name'] ?? ''));
  $data['last_name'] = trim((string)($_POST['last_name'] ?? ''));
  $data['email'] = trim((string)($_POST['email'] ?? ''));
  $data['whatsapp'] = trim((string)($_POST['whatsapp'] ?? ''));
  $data['role'] = (string)($_POST['role'] ?? 'pelanggan');
  $data['is_admin'] = isset($_POST['is_admin']) ? 1 : 0;
  $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
  $password = (string)($_POST['password'] ?? '');

  $validRoles = ['pelanggan','driver','admin'];
  if ($data['username']==='' || $data['email']==='' || $data['first_name']==='' || $data['last_name']==='' || $data['whatsapp']==='') {
    $error = "Semua field wajib (kecuali password saat edit).";
  } elseif (!in_array($data['role'], $validRoles, true)) {
    $error = "Role tidak valid.";
  } elseif (!$editing && strlen($password) < 8) {
    $error = "Password minimal 8 karakter.";
  } else {
    try {
      if ($editing) {
        if ($password !== '') {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $stmt = db()->prepare("UPDATE users SET username=?, first_name=?, last_name=?, email=?, whatsapp=?, role=?, is_admin=?, is_active=?, password_hash=? WHERE id=?");
          $stmt->execute([$data['username'],$data['first_name'],$data['last_name'],$data['email'],$data['whatsapp'],$data['role'],$data['is_admin'],$data['is_active'],$hash,$id]);
        } else {
          $stmt = db()->prepare("UPDATE users SET username=?, first_name=?, last_name=?, email=?, whatsapp=?, role=?, is_admin=?, is_active=? WHERE id=?");
          $stmt->execute([$data['username'],$data['first_name'],$data['last_name'],$data['email'],$data['whatsapp'],$data['role'],$data['is_admin'],$data['is_active'],$id]);
        }
        $success = "User berhasil diupdate.";
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare("INSERT INTO users (username, first_name, last_name, email, whatsapp, password_hash, role, is_admin, is_active) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$data['username'],$data['first_name'],$data['last_name'],$data['email'],$data['whatsapp'],$hash,$data['role'],$data['is_admin'],$data['is_active']]);
        $success = "User berhasil dibuat.";
        $data = array_merge($data, ['password'=>'']);
      }
    } catch (Throwable $e) {
      $error = "Gagal simpan user: " . $e->getMessage();
    }
  }
}
?>

<h1><?= $editing ? 'Edit User' : 'Tambah User' ?></h1>

<?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert ok"><?= h($success) ?></div><?php endif; ?>

<form method="post" class="card">
  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

  <label>Username</label>
  <input name="username" value="<?= h((string)$data['username']) ?>" required>

  <div class="grid2">
    <div>
      <label>First Name</label>
      <input name="first_name" value="<?= h((string)$data['first_name']) ?>" required>
    </div>
    <div>
      <label>Last Name</label>
      <input name="last_name" value="<?= h((string)$data['last_name']) ?>" required>
    </div>
  </div>

  <label>Email</label>
  <input name="email" type="email" value="<?= h((string)$data['email']) ?>" required>

  <label>Nomor WhatsApp</label>
  <input name="whatsapp" value="<?= h((string)$data['whatsapp']) ?>" placeholder="628xxxx" required>

  <label>Role</label>
  <select name="role">
    <option value="pelanggan" <?= ($data['role']==='pelanggan')?'selected':'' ?>>pelanggan</option>
    <option value="driver" <?= ($data['role']==='driver')?'selected':'' ?>>driver</option>
    <option value="admin" <?= ($data['role']==='admin')?'selected':'' ?>>admin</option>
  </select>

  <label>Password <?= $editing ? '(kosongkan jika tidak ganti)' : '' ?></label>
  <input name="password" type="password" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>

  <div class="row">
    <label class="check">
      <input type="checkbox" name="is_admin" <?= ((int)$data['is_admin']===1)?'checked':'' ?>>
      Administrator (yes/no)
    </label>
    <label class="check">
      <input type="checkbox" name="is_active" <?= ((int)$data['is_active']===1)?'checked':'' ?>>
      Active
    </label>
  </div>

  <div class="row">
    <button type="submit">Simpan</button>
    <a class="btn ghost" href="/?page=admin_users">Kembali</a>
  </div>
</form>
