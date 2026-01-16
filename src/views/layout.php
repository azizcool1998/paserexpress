<?php
if (!isset($title)) $title = "PaserExpress Panel";
if (!isset($content)) $content = "";
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- GOD UI CSS -->
    <link rel="stylesheet" href="/assets/god-ui.css">
</head>

<body class="god-body d-flex">

<!-- ====================== -->
<!-- 📌 SIDEBAR GOD EDITION -->
<!-- ====================== -->
<nav id="sidebar" class="god-sidebar">
    <div class="sidebar-header text-center py-4">
        <i class="bi bi-lightning-charge-fill fs-2"></i>
        <h4 class="mt-2 fw-bold">PaserExpress</h4>
    </div>

    <ul class="god-nav">
        <li><a href="?page=admin_dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li><a href="?page=admin_monitoring"><i class="bi bi-activity"></i> Monitoring PRO</a></li>
        <li><a href="?page=admin_users"><i class="bi bi-people-fill"></i> Manage Users</a></li>
        <li><a href="?page=admin_backup"><i class="bi bi-cloud-arrow-down-fill"></i> Backups</a></li>
        <li><a href="?page=admin_settings"><i class="bi bi-gear-fill"></i> Settings</a></li>

        <li class="mt-auto">
            <a href="?page=logout" class="text-danger fw-bold">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>
    </ul>
</nav>

<!-- ====================== -->
<!-- 🌈 MAIN CONTENT AREA   -->
<!-- ====================== -->
<div class="flex-grow-1 god-main">

    <!-- ====================== -->
    <!-- 🌤 GOD NAVBAR -->
    <!-- ====================== -->
    <nav class="god-navbar shadow-sm px-3 d-flex align-items-center">
        <button class="btn btn-outline-light me-3" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>

        <span class="navbar-brand fw-bold">Admin Panel</span>

        <div class="ms-auto d-flex align-items-center gap-3">

            <!-- Theme Switch -->
            <button class="btn btn-outline-primary" onclick="toggleTheme()">
                <i class="bi bi-brightness-high"></i>
            </button>

            <div class="god-username">
                <i class="bi bi-person-circle"></i>
                <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
            </div>
        </div>
    </nav>

    <!-- ====================== -->
    <!-- 📦 PAGE CONTENT -->
    <!-- ====================== -->
    <div class="container py-4">
        <?= $content ?>
    </div>

    <!-- ====================== -->
    <!-- 🦶 FOOTER -->
    <!-- ====================== -->
    <footer class="god-footer text-center py-3">
        PaserExpress © <?= date("Y") ?> — God UI Edition
    </footer>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar Toggle -->
<script>
document.getElementById("sidebarToggle").onclick = function () {
    document.getElementById("sidebar").classList.toggle("expanded");
};
</script>

<!-- Theme Controller -->
<script>
function toggleTheme() {
    let html = document.documentElement;
    let theme = html.getAttribute("data-theme");
    html.setAttribute("data-theme", theme === "dark" ? "light" : "dark");
    localStorage.setItem("paser_theme", html.getAttribute("data-theme"));
}
document.addEventListener("DOMContentLoaded", () => {
    let saved = localStorage.getItem("paser_theme");
    if (saved) document.documentElement.setAttribute("data-theme", saved);
});
</script>

</body>
</html>
