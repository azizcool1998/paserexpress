<?php
declare(strict_types=1);
auth_require_login();
auth_require_admin();

$totalUsers = (int)db()->query("SELECT COUNT(*) c FROM users")->fetch()['c'];
$totalTracking = (int)db()->query("SELECT COUNT(*) c FROM tracking_requests")->fetch()['c'];
?>
<h1>Admin Panel</h1>

<div class="grid">
  <div class="card">
    <h3>Total Users</h3>
    <p class="big"><?= $totalUsers ?></p>
    <a class="btn" href="/?page=admin_users">Kelola Users</a>
  </div>
  <div class="card">
    <h3>Tracking Requests</h3>
    <p class="big"><?= $totalTracking ?></p>
    <p class="muted">Catatan request tracking (opsional).</p>
  </div>
</div>
