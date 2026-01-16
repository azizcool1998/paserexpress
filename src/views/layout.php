<?php
if (!isset($title)) $title = "PaserExpress Panel";
if (!isset($content)) $content = "";
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- GOD UI CSS -->
    <link rel="stylesheet" href="/css/god-ui.css?v=1">

    <!-- Theme JS -->
    <script src="/js/theme.js" defer></script>
</head>

<body class="d-flex">

<!-- SIDEBAR -->
<nav id="sidebar" class="sidebar-god bg-dark text-white">
    <div class="sidebar-header text-center py-4">
        <i class="bi bi-lightning-charge-fill fs-2"></i>
        <h4 class="mt-2 fw-bold">PaserExpress</h4>
    </div>

    <ul class="nav flex-column mt-3">
        <li class="nav-item">
            <a href="?page=admin_dashboard" class="nav-link">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        <li class="nav-item">
            <a href="?page=admin_monitoring" class="nav-link">
                <i class="bi bi-activity me-2"></i> Monitoring PRO
            </a>
        </li>

        <li class="nav-item">
            <a href="?page=admin_users" class="nav-link">
                <i class="bi bi-people-fill me-2"></i> Users
            </a>
        </li>

        <li class="nav-item">
            <a href="?page=admin_backup" class="nav-link">
                <i class="bi bi-cloud-arrow-down-fill me-2"></i> Backups
            </a>
        </li>

        <li class="nav-item">
            <a href="?page=admin_settings" class="nav-link">
                <i class="bi bi-gear-fill me-2"></i> Settings
            </a>
        </li>

        <li class="nav-item mt-auto">
            <a href="?page=logout" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </li>
    </ul>
</nav>

<!-- MAIN CONTENT -->
<div class="flex-grow-1 wrapper-god">

    <!-- TOP NAVBAR -->
    <nav class="navbar navbar-expand-lg god-topbar shadow-sm px-3">
        <div class="container-fluid">

            <button class="btn btn-outline-secondary me-3" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>

            <span class="navbar-brand fw-bold">Admin Panel</span>

            <div class="ms-auto">
                <!-- Theme Switch -->
                <button id="themeToggle" class="btn btn-outline-primary">
                    <i class="bi bi-sun-fill theme-icon-active"></i>
                    <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- PAGE CONTENT -->
    <div class="container py-4 god-content">
        <?= $content ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Sidebar toggle
document.getElementById("sidebarToggle").onclick = function () {
    document.getElementById("sidebar").classList.toggle("expanded");
};
</script>

</body>
</html>
