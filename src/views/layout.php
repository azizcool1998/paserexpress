<?php
if (!isset($title)) $title = "PaserExpress Panel";
if (!isset($content)) $content = "";
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">

    <!-- Theme Controller -->
    <script src="/js/theme.js" defer></script>
</head>

<body class="d-flex">

<!-- SIDEBAR -->
<nav id="sidebar" class="sidebar-mini bg-dark text-white">
    <div class="sidebar-header text-center py-3">
        <i class="bi bi-lightning-charge-fill fs-3"></i>
        <div class="sidebar-title">PaserExpress</div>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="?page=admin_dashboard" class="nav-link text-white">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="?page=admin_monitoring" class="nav-link text-white">
                <i class="bi bi-activity"></i>
                <span>Monitoring PRO</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="?page=admin_users" class="nav-link text-white">
                <i class="bi bi-people-fill"></i>
                <span>Manage Users</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="?page=admin_backup" class="nav-link text-white">
                <i class="bi bi-cloud-arrow-down-fill"></i>
                <span>Backups</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="?page=admin_settings" class="nav-link text-white">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        </li>

        <li class="nav-item mt-auto">
            <a href="?page=logout" class="nav-link text-danger fw-bold">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>


<!-- MAIN CONTENT -->
<div class="flex-grow-1">

    <!-- TOP NAVBAR -->
    <nav class="navbar navbar-expand-lg bg-body shadow-sm px-3">
        <div class="container-fluid">

            <button class="btn btn-outline-secondary me-3" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>

            <span class="navbar-brand fw-bold">Admin Panel</span>

            <div class="ms-auto">
                <!-- Dark/Light Toggle -->
                <button id="themeToggle" class="btn btn-outline-primary">
                    <i class="bi bi-moon-fill"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- PAGE CONTENT CONTAINER -->
    <div class="container py-4">
        <?= $content ?>
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar Controller -->
<script>
document.getElementById("sidebarToggle").onclick = function () {
    document.getElementById("sidebar").classList.toggle("expanded");
};
</script>

</body>
</html>
