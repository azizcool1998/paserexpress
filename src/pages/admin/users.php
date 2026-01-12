<?php
declare(strict_types=1);
auth_require_login();
auth_require_admin();

$rows = db()->query("SELECT id, username, first_name, last_name, email, whatsapp, role, is_admin, is_active, created_at FROM users ORDER BY id DESC")->fetchAll();
?>
<h1>Kelola Users</h1>

<div class="row">
  <a class="btn" href="/?page=admin_user_form">+ Tambah User</a>
</div>

<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>ID</th><th>Username</th><th>Nama</th><th>Email</th><th>WhatsApp</th>
      <th>Role</th><th>Admin?</th><th>Aktif</th><th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= h($r['username']) ?></td>
        <td><?= h($r['first_name'] . ' ' . $r['last_name']) ?></td>
        <td><?= h($r['email']) ?></td>
        <td><?= h($r['whatsapp']) ?></td>
        <td><?= h($r['role']) ?></td>
        <td><?= ((int)$r['is_admin'] === 1) ? 'yes' : 'no' ?></td>
        <td><?= ((int)$r['is_active'] === 1) ? 'yes' : 'no' ?></td>
        <td><a href="/?page=admin_user_form&id=<?= (int)$r['id'] ?>">Edit</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
