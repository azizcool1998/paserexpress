<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../includes/bootstrap.php';

try {

    /* ============================
     * CPU LOAD
     * ============================ */
    $load = sys_getloadavg();
    $cpuLoad = [
        "1min"  => round($load[0], 2),
        "5min"  => round($load[1], 2),
        "15min" => round($load[2], 2),
    ];

    /* ============================
     * CPU PERCENT (approx)
     * ============================ */
    $cpuPercent = 0;
    if (strtolower(PHP_OS_FAMILY) === "linux") {
        $stat1 = file_get_contents("/proc/stat");
        usleep(100000);
        $stat2 = file_get_contents("/proc/stat");

        preg_match("/cpu\s+(.*)/", $stat1, $m1);
        preg_match("/cpu\s+(.*)/", $stat2, $m2);

        $cpu1 = array_map('intval', preg_split('/\s+/', trim($m1[1])));
        $cpu2 = array_map('intval', preg_split('/\s+/', trim($m2[1])));

        $idle1 = $cpu1[3];
        $idle2 = $cpu2[3];
        $total1 = array_sum($cpu1);
        $total2 = array_sum($cpu2);

        $diffIdle  = $idle2 - $idle1;
        $diffTotal = $total2 - $total1;
        $diffUsage = $diffTotal - $diffIdle;

        if ($diffTotal > 0) {
            $cpuPercent = round(($diffUsage / $diffTotal) * 100, 1);
        }
    }

    /* ============================
     * RAM
     * ============================ */
    $memInfo = file("/proc/meminfo");
    $mem = [];
    foreach ($memInfo as $line) {
        list($key, $val) = explode(":", $line);
        $mem[$key] = (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
    }

    $memTotal = $mem['MemTotal'] ?? 0;
    $memFree  = $mem['MemAvailable'] ?? 0;
    $memUsed  = $memTotal - $memFree;
    $ramPercent = $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 1) : 0;

    /* ============================
     * DISK
     * ============================ */
    $diskTotal = disk_total_space("/");
    $diskFree  = disk_free_space("/");
    $diskUsed  = $diskTotal - $diskFree;

    $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

    /* ============================
     * UPTIME (Human Format)
     * ============================ */
    $uptimeRaw = @file_get_contents("/proc/uptime");
    $uptimeSec = (int) explode(" ", $uptimeRaw)[0];

    $uptime = sprintf(
        "%d days, %d hours, %d minutes",
        floor($uptimeSec / 86400),
        floor(($uptimeSec % 86400) / 3600),
        floor(($uptimeSec % 3600) / 60)
    );

    /* ============================
     * SERVICES
     * ============================ */
    function service_status($service) {
        $check = shell_exec("systemctl is-active $service");
        return trim($check) === "active" ? "running" : "down";
    }

    $services = [
        "nginx"   => service_status("nginx"),
        "php_fpm" => service_status("php8.3-fpm"),
        "mariadb" => service_status("mariadb")
    ];

    /* ============================
     * FINAL RESPONSE
     * ============================ */

    echo json_encode([
        "success" => true,
        "data" => [
            "timestamp" => date("Y-m-d H:i:s"),
            "php_version" => PHP_VERSION,

            "cpu_percent" => $cpuPercent,
            "cpu_load" => $cpuLoad,

            "ram" => [
                "total_mb" => round($memTotal / 1024, 1),
                "used_mb"  => round($memUsed / 1024, 1),
                "free_mb"  => round($memFree / 1024, 1),
            ],
            "ram_percent" => $ramPercent,

            "disk" => [
                "total_gb" => round($diskTotal / (1024**3), 2),
                "used_gb"  => round($diskUsed  / (1024**3), 2),
                "free_gb"  => round($diskFree  / (1024**3), 2),
            ],
            "disk_percent" => $diskPercent,

            "uptime"   => $uptime,
            "services" => $services
        ]
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
