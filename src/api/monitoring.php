<?php
require_once __DIR__ . '/../includes/bootstrap.php';

function getSystemData() {
    // CPU Load (1m, 5m, 15m)
    $load = sys_getloadavg();

    // RAM
    $mem = [];
    foreach (file('/proc/meminfo') as $line) {
        list($key, $val) = explode(':', $line);
        $mem[$key] = trim($val);
    }
    $ram_total = intval($mem['MemTotal']) / 1024;
    $ram_available = intval($mem['MemAvailable']) / 1024;
    $ram_used = $ram_total - $ram_available;

    // Disk
    $disk_total = disk_total_space("/") / 1024 / 1024 / 1024;
    $disk_free = disk_free_space("/") / 1024 / 1024 / 1024;
    $disk_used = $disk_total - $disk_free;

    // Service Status
    function checkService($service) {
        $cmd = "systemctl is-active {$service} 2>&1";
        $status = trim(shell_exec($cmd));
        return ($status === "active") ? "running" : "down";
    }

    return [
        "cpu_load" => [
            "1min" => $load[0],
            "5min" => $load[1],
            "15min" => $load[2]
        ],
        "ram" => [
            "total_mb" => round($ram_total, 2),
            "used_mb" => round($ram_used, 2),
            "free_mb" => round($ram_available, 2)
        ],
        "disk" => [
            "total_gb" => round($disk_total, 2),
            "used_gb" => round($disk_used, 2),
            "free_gb" => round($disk_free, 2)
        ],
        "services" => [
            "nginx" => checkService("nginx"),
            "php_fpm" => checkService("php8.3-fpm"),
            "mariadb" => checkService("mariadb")
        ],
        "uptime" => trim(shell_exec("uptime -p")),
        "timestamp" => date("Y-m-d H:i:s")
    ];
}

header('Content-Type: application/json');
echo json_encode([
    "success" => true,
    "data" => getSystemData()
]);
