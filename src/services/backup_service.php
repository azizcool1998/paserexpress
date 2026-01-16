<?php

function load_backup_settings() {
    $file = __DIR__ . "/../../config/backup.json";

    if (!file_exists($file)) {
        return [
            "enabled" => "off",
            "interval" => "1d"
        ];
    }

    return json_decode(file_get_contents($file), true);
}

function save_backup_settings($enabled, $interval) {
    $file = __DIR__ . "/../../config/backup.json";

    file_put_contents($file, json_encode([
        "enabled" => $enabled,
        "interval" => $interval
    ], JSON_PRETTY_PRINT));
}

function generate_cron_backup($enabled, $interval) {
    $cron = "/etc/cron.d/paserexpress-backup";

    if ($enabled === "off") {
        @unlink($cron);
        return;
    }

    $entry = parse_interval_to_cron($interval);
    $script = "/usr/local/bin/paserexpress-backup.sh";

    file_put_contents($cron,
        "$entry root bash $script >/dev/null 2>&1\n"
    );
}

function parse_interval_to_cron($i) {
    return [
        "1m"  => "* * * * *",
        "5m"  => "*/5 * * * *",
        "15m" => "*/15 * * * *",
        "30m" => "*/30 * * * *",
        "1h"  => "0 * * * *",
        "2h"  => "0 */2 * * *",
        "3h"  => "0 */3 * * *",
        "6h"  => "0 */6 * * *",
        "9h"  => "0 */9 * * *",
        "12h" => "0 */12 * * *",
        "18h" => "0 */18 * * *",
        "1d"  => "0 0 * * *",
        "3d"  => "0 0 */3 * *",
        "1w"  => "0 0 * * 0",
        "2w"  => "0 0 */14 * *",
        "3w"  => "0 0 */21 * *",
        "1mo" => "0 0 1 * *",
        "2mo" => "0 0 1 */2 *",
        "3mo" => "0 0 1 */3 *",
        "6mo" => "0 0 1 */6 *",
        "9mo" => "0 0 1 */9 *",
        "1y"  => "0 0 1 1 *"
    ][$i] ?? "0 0 * * *";
}

function run_backup_now() {
    $script = "/usr/local/bin/paserexpress-backup.sh";
    shell_exec("bash $script >/dev/null 2>&1 &");
}
