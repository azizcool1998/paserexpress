<?php
header("Content-Type: application/json");

function get_cpu_usage() {
    $load = sys_getloadavg();
    $cores = (int) shell_exec("nproc");

    return [
        "1m"  => round(($load[0] / $cores) * 100, 2),
        "5m"  => round(($load[1] / $cores) * 100, 2),
        "15m" => round(($load[2] / $cores) * 100, 2),
    ];
}

function get_ram_usage() {
    $m = [];
    foreach (file("/proc/meminfo") as $line) {
        [$key, $val] = explode(":", $line);
        $m[$key] = trim($val);
    }

    $total = (int) filter_var($m["MemTotal"], FILTER_SANITIZE_NUMBER_INT);
    $free  = (int) filter_var($m["MemAvailable"], FILTER_SANITIZE_NUMBER_INT);
    $used  = $total - $free;

    return [
        "total_mb" => round($total / 1024, 2),
        "used_mb"  => round($used / 1024, 2),
        "free_mb"  => round($free / 1024, 2),
        "percent"  => round(($used / $total) * 100, 2)
    ];
}

function get_disk_usage() {
    $total = disk_total_space("/");
    $free  = disk_free_space("/");
    $used  = $total - $free;

    return [
        "total_gb" => round($total / 1024 / 1024 / 1024, 2),
        "used_gb"  => round($used / 1024 / 1024 / 1024, 2),
        "free_gb"  => round($free / 1024 / 1024 / 1024, 2),
        "percent"  => round(($used / $total) * 100, 2),
    ];
}

function get_uptime() {
    return trim(shell_exec("uptime -p"));
}

function get_network() {
    $rx = (int) shell_exec("cat /proc/net/dev | awk '/eth0/ {print \$2}'");
    $tx = (int) shell_exec("cat /proc/net/dev | awk '/eth0/ {print \$10}'");

    return [
        "rx_kb" => round($rx / 1024, 2),
        "tx_kb" => round($tx / 1024, 2)
    ];
}

function get_services() {
    $svc = fn($s) => trim(shell_exec("systemctl is-active $s")) === "active" ? "running" : "down";

    return [
        "nginx"   => $svc("nginx"),
        "php_fpm" => $svc("php8.3-fpm"),
        "mariadb" => $svc("mariadb"),
    ];
}

$output = [
    "success" => true,
    "timestamp" => date("Y-m-d H:i:s"),
    "cpu" => get_cpu_usage(),
    "ram" => get_ram_usage(),
    "disk" => get_disk_usage(),
    "network" => get_network(),
    "uptime" => get_uptime(),
    "services" => get_services(),
];

echo json_encode($output);
