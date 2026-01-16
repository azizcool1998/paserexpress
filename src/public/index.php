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

    case "admin_update":
        (new UpdateController)->index();
        break;

    case "update_check":
        (new UpdateController)->checkUpdate();
        break;

    case "update_run":
        (new UpdateController)->runUpdate();
        break;

    case "admin_logs":
        (new LogController)->index();
        break;

    case "log_read":
        (new LogController)->read();
        break;

    case "log_analyze":
        (new LogController)->analyze();
        break;

    case "log_fix":
        (new LogController)->fix();
        break;

    case "log_clear":
        (new LogController)->clear();
        break;

    case "api_monitoring_pro":
        require __DIR__ . "/../api/monitoring_pro.php";
        exit;

    default:
        echo "<h2>PaserExpress Running</h2>";
        echo "Page: <b>" . sanitize($page) . "</b>";
}
