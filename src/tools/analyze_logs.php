<?php

$checks = [
    "PHP Fatal error" => "Periksa file PHP terkait. Biasanya karena syntax error atau variable undefined.",
    "PHP Parse error" => "Ada kesalahan penulisan kode PHP.",
    "Connection refused" => "Service mungkin mati. Restart: systemctl restart php8.3-fpm mariadb nginx",
    "No such file or directory" => "File hilang. Pastikan path benar.",
    "Permission denied" => "Atur permission: chown -R www-data:www-data /var/www/paserexpress",
    "mysqli_sql_exception" => "Periksa credential database di .env",
    "Access denied for user" => "User DB salah atau password salah.",
    "Unknown column" => "Database tidak sesuai schema. Jalankan import schema.sql",
    "SQLSTATE" => "SQL Error – periksa query.",
    "502 Bad Gateway" => "PHP-FPM mungkin down. Restart php8.3-fpm",
    "Primary script unknown" => "Nginx tidak menemukan index.php. Periksa root path.",
];

$logs = [
    "/var/log/nginx/error.log",
    "/var/log/nginx/paserexpress.error.log",
    "/var/log/php8.3-fpm.log",
    "/var/log/mysql/error.log",
    "/var/log/syslog"
];

$output = "=== LOG ANALYZER PRO ===\n\n";

foreach ($logs as $file) {
    if (!file_exists($file)) continue;

    $content = shell_exec("tail -n 200 $file");

    $output .= "\n--- Checking: $file ---\n";

    foreach ($checks as $key => $msg) {
        if (str_contains($content, $key)) {
            $output .= "[FOUND] $key\n";
            $output .= "Fix: $msg\n\n";
        }
    }
}

echo $output;
