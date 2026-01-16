<?php
if (!isset($title)) $title = "PaserExpress Admin";
if (!isset($content)) $content = "";
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- GOD UI CSS -->
    <link rel="stylesheet" href="/css/god-ui.css">

    <!-- Dark/Light theme controller -->
    <script src="/js/theme.js" defer></script>
</head>

<body class="god-body">

<!-- ============================== -->
<!-- 💠 SIDEBAR GOD MODE -->
<!-- ============================== -->
<aside id="god-sidebar" class="god-sidebar">

    <div class="god-sidebar-header">
        <i class="bi bi-lightning-charge-fill god-logo"></i>
        <span class="god-title">PaserExpress</span>
    </div>

    <nav class="god-nav">
        <a href="?page=admin_dashboard" class="god-nav-item">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>

        <a href="?page=admin_monitoring" class="god-nav-item">
            <i class="bi bi-activity"></i> <span>Monitoring PRO</span>
        </a>

        <a href="?page=admin_users" class="god-nav-item">
            <i class="bi bi-people-fill"></i> <span>Manage Users</span>
        </a>

        <a href="?page=admin_backup" class="god-nav-item">
            <i class="bi bi-cloud-arrow-down-fill"></i> <span>Backups</span>
        </a>

        <a href="?page=admin_settings" class="god-nav-item">
            <i class="bi bi-gear-fill"></i> <span>Settings</span>
        </a>

        <a href="?page=admin_update" class="god-nav-item">
            <i class="bi bi-cloud-arrow-up-fill"></i> <span>Update Engine</span>
        </a>
    </nav>

    <div class="god-logout-box">
        <a href="?page=logout" class="god-nav-item logout">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

</aside>

<!-- ============================== -->
<!-- 💠 MAIN AREA -->
<!-- ============================== -->
<div class="god-main">

    <!-- ============================== -->
    <!-- 💠 NAVBAR GOD -->
    <!-- ============================== -->
    <header class="god-navbar shadow-sm">

        <button id="sidebarToggle" class="god-btn-icon me-3">
            <i class="bi bi-list"></i>
        </button>

        <span class="god-navbar-title"><?= htmlspecialchars($title) ?></span>

        <div class="ms-auto">
            <button id="themeToggle" class="god-btn-icon">
                <i class="bi bi-moon-stars-fill"></i>
            </button>
        </div>

    </header>

    <!-- ============================== -->
    <!-- 💠 PAGE CONTENT -->
    <!-- ============================== -->
    <main class="god-container">
        <?= $content ?>
    </main>

</div>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar toggle -->
<script>
document.getElementById("sidebarToggle").onclick = function() {
    document.getElementById("god-sidebar").classList.toggle("expanded");
};
</script>

</body>
</html>
