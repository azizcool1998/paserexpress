<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

// -----------------------------
// Function Helpers
// -----------------------------
function cmd($c) { return trim(shell_exec($c)); }

// CPU
$cpu = floatval(cmd("top -bn1 | grep 'Cpu(s)' | awk '{print $2 + $4}'"));

// RAM
$ram_total = intval(cmd("grep MemTotal /proc/meminfo | awk '{print $2}'")) / 1024;
$ram_avail = intval(cmd("grep MemAvailable /proc/meminfo | awk '{print $2}'")) / 1024;
$ram_used = round($ram_total - $ram_avail, 2);
$ram_percent = round(($ram_used / $ram_total) * 100, 2);

// DISK
$disk_total = disk_total_space("/") / 1024 / 1024 / 1024;
$disk_free  = disk_free_space("/")  / 1024 / 1024 / 1024;
$disk_used  = $disk_total - $disk_free;
$disk_percent = round(($disk_used / $disk_total) * 100, 2);

// HTTP check
$http = cmd("curl -o /dev/null -s -w '%{http_code}' http://localhost");

// Service checks
$nginx   = trim(cmd("systemctl is-active nginx"));
$phpfpm  = trim(cmd("systemctl is-active php8.3-fpm"));
$mariadb = trim(cmd("systemctl is-active mariadb"));

// Uptime
$uptime = cmd("uptime -p");

// -----------------------------
// OUTPUT
// -----------------------------
header("Content-Type: application/json");

echo json_encode([
    "time"     => date("H:i:s"),
    "cpu"      => $cpu,

    "ram" => [
        "used"    => $ram_used,
        "total"   => $ram_total,
        "percent" => $ram_percent
    ],

    "disk" => [
        "used"    => $disk_used,
        "total"   => $disk_total,
        "percent" => $disk_percent
    ],

    "http"    => intval($http),
    "nginx"   => $nginx,
    "phpfpm"  => $phpfpm,
    "mariadb" => $mariadb,

    "uptime" => $uptime,
    "success" => true
], JSON_PRETTY_PRINT);
