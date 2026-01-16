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

    <!-- GOD UI CSS -->
    <link rel="stylesheet" href="/assets/god-ui.css">

    <!-- Theme Controller -->
    <script src="/js/theme.js" defer></script>

    <style>
        /* Smooth fade for every page */
        .god-fade {
            animation: godFadeIn 0.5s ease;
        }
        @keyframes godFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>

</head>

<body class="d-flex">

<!-- SIDEBAR -->
<nav id="sidebar" class="god-sidebar bg-dark text-white">

    <!-- BRAND -->
    <div class="sidebar-header text-center py-4">
        <i class="bi bi-lightning-charge-fill fs-1"></i>
        <div class="sidebar-title mt-2 fw-bold">PaserExpress</div>
    </div>

    <ul class="nav flex-column px-2">

        <li class="nav-item mb-1">
            <a href="?page=admin_dashboard" class="nav-link text-white d-flex align-items-center god-link">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        <li class="nav-item mb-1">
            <a href="?page=admin_monitoring" class="nav-link text-white d-flex align-items-center god-link">
                <i class="bi bi-activity me-2"></i> Monitoring PRO
            </a>
        </li>

        <li class="nav-item mb-1">
            <a href="?page=admin_users" class="nav-link text-white d-flex align-items-center god-link">
                <i class="bi bi-people-fill me-2"></i> Manage Users
            </a>
        </li>

        <li class="nav-item mb-1">
            <a href="?page=admin_backup" class="nav-link text-white d-flex align-items-center god-link">
                <i class="bi bi-cloud-arrow-down-fill me-2"></i> Backups
            </a>
        </li>

        <li class="nav-item mb-1">
            <a href="?page=admin_settings" class="nav-link text-white d-flex align-items-center god-link">
                <i class="bi bi-gear-fill me-2"></i> Settings
            </a>
        </li>

        <li class="nav-item mt-auto">
            <a href="?page=logout" class="nav-link text-danger fw-bold d-flex align-items-center god-link">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </li>
    </ul>

</nav>


<!-- MAIN CONTENT -->
<div class="flex-grow-1">

    <!-- TOP NAVBAR -->
    <nav class="navbar navbar-expand-lg bg-body shadow-sm px-3">
        <div class="container-fluid">

            <!-- Sidebar Toggle -->
            <button class="btn btn-outline-secondary me-3" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>

            <!-- Title -->
            <span class="navbar-brand fw-bold">Admin Panel</span>

            <!-- Right Tools -->
            <div class="ms-auto">

                <!-- Theme Toggle -->
                <button id="themeToggle" class="btn btn-outline-primary">
                    <i class="bi bi-moon-fill"></i>
                </button>

            </div>
        </div>
    </nav>

    <!-- PAGE CONTENT -->
    <div class="container py-4">
        <?= $content ?>
    </div>

</div>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar Toggle Script -->
<script>
document.getElementById("sidebarToggle").onclick = function () {
    document.getElementById("sidebar").classList.toggle("expanded");
};
</script>

</body>
</html>
