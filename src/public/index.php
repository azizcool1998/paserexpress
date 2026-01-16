<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$page = $_GET['page'] ?? 'home';

switch ($page) {

    case "login":
        (new AuthController)->login();
        break;

    case "logout":
        (new AuthController)->logout();
        break;

    case "admin_dashboard":
        (new AdminController)->dashboard();
        break;

    case "pelanggan_dashboard":
        (new PelangganController)->dashboard();
        break;

    case "driver_dashboard":
        (new DriverController)->dashboard();
        break;

    default:
        echo "<h2>PaserExpress Running</h2>";
        echo "Page: <b>" . sanitize($page) . "</b>";
}

// API: Monitoring
if ($page === 'api_monitoring') {
    require_once __DIR__ . '/../api/monitoring.php';
    exit;
}
