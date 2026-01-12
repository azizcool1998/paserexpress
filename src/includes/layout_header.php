<?php
declare(strict_types=1);
$APP_NAME = env_get('APP_NAME', 'Website');
$u = auth_user();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= h($APP_NAME) ?></title>
  <link rel="stylesheet" href="/assets/css/styles.css">
  <script defer src="/assets/js/app.js"></script>
</head>
<body>
<header class="topbar">
  <div class="container row">
    <div class="brand"><?= h($APP_NAME) ?></div>
    <nav class="nav">
      <a href="/?page=welcome">Beranda</a>
      <a href="/?page=tracking">Tracking Resi</a>
      <?php if ($u): ?>
        <?php if (($u['role'] ?? '') === 'admin'): ?>
          <a href="/?page=admin_dashboard">Admin</a>
        <?php endif; ?>
        <a href="/?page=logout">Logout (<?= h($u['username']) ?>)</a>
      <?php else: ?>
        <a href="/?page=login">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container">
