<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header("Content-Type: application/json");

// Must be logged in as admin
if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

// CPU Load
$load = sys_getloadavg();

// RAM
$mem = [];
foreach (file("/proc/meminfo") as $line) {
    [$key, $val] = explode(":", $line);
    $mem[$key] = (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
}

$total_ram = $mem["MemTotal"] / 1024;
$free_ram  = $mem["MemAvailable"] / 1024;
$used_ram  = $total_ram - $free_ram;

// Disk
$disk_total = disk_total_space("/") / (1024*1024*1024);
$disk_free  = disk_free_space("/") / (1024*1024*1024);
$disk_used  = $disk_total - $disk_free;

// Services
function svc($name) {
    $output = shell_exec("systemctl is-active $name 2>&1");
    return trim($output) === "active" ? "running" : "down";
}

// CPU temperature
$cpu_temp = "N/A";
if (file_exists("/sys/class/thermal/thermal_zone0/temp")) {
    $cpu_temp = round(intval(file_get_contents("/sys/class/thermal/thermal_zone0/temp")) / 1000, 1) . "°C";
}

// Network RX/TX
$net = shell_exec("cat /sys/class/net/eth0/statistics/rx_bytes");
$net2 = shell_exec("cat /sys/class/net/eth0/statistics/tx_bytes");

$result = [
    "success" => true,

    "timestamp" => date("Y-m-d H:i:s"),

    "cpu" => [
        "load_1m"  => $load[0],
        "load_5m"  => $load[1],
        "load_15m" => $load[2],
        "temperature" => $cpu_temp
    ],

    "ram" => [
        "used"  => round($used_ram, 1),
        "free"  => round($free_ram, 1),
        "total" => round($total_ram, 1),
    ],

    "disk" => [
        "used"  => round($disk_used, 2),
        "free"  => round($disk_free, 2),
        "total" => round($disk_total, 2),
    ],

    "network" => [
        "rx_bytes" => intval($net),
        "tx_bytes" => intval($net2),
    ],

    "services" => [
        "nginx"   => svc("nginx"),
        "php_fpm" => svc("php8.3-fpm"),
        "mariadb" => svc("mariadb"),
    ],
    
    "uptime" => trim(shell_exec("uptime -p"))
];

echo json_encode($result, JSON_PRETTY_PRINT);
