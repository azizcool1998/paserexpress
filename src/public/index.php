<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$page = $_GET['page'] ?? 'home';

/*
|------------------------------------------
| API (diproses lebih dulu sebelum switch)
|------------------------------------------
*/
if ($page === 'api_monitoring') {
    require_once __DIR__ . '/../api/monitoring.php';
    exit;
}

/*
|------------------------------------------
| ROUTER HALAMAN
|------------------------------------------
*/
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
    
    case "admin_backup":
        (new BackupController)->index();
        break;

    case "backup_set_status":
        (new BackupController)->setStatus();
        break;

    case "backup_set_interval":
        (new BackupController)->setInterval();
        break;

    default:
        echo "<h2>PaserExpress Running</h2>";
        echo "Page: <b>" . sanitize($page) . "</b>";
}
