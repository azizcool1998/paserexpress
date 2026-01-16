<?php

class BackupController {

    private string $backupDir;
    private string $statusFile;
    private string $intervalFile;

    public function __construct() {
        $root = __DIR__ . '/../';
        $this->backupDir = $root . 'storage/backups';
        $this->statusFile = $root . 'storage/backup_status.txt';
        $this->intervalFile = $root . 'storage/backup_interval.txt';

        if (!is_dir($this->backupDir)) mkdir($this->backupDir, 0777, true);
    }

    public function index() {
        $files = scandir($this->backupDir);
        $backups = array_filter($files, fn($f) => strpos($f, 'backup_') === 0);

        $status = file_exists($this->statusFile)
            ? trim(file_get_contents($this->statusFile))
            : "off";

        $interval = file_exists($this->intervalFile)
            ? trim(file_get_contents($this->intervalFile))
            : "none";

        require __DIR__ . '/../views/admin_backup.php';
    }

    public function setStatus() {
        $status = $_POST['status'] ?? "";

        if (!in_array($status, ["on", "off"])) {
            $this->json(false, "Status invalid.");
            return;
        }

        file_put_contents($this->statusFile, $status);

        if ($status === "off") {
            unlink("/etc/cron.d/paserexpress_backup");
        }

        $this->json(true, "Auto backup " . strtoupper($status));
    }

    public function setInterval() {
        $interval = $_POST['interval'] ?? "";

        if (!$interval) {
            $this->json(false, "Pilih interval.");
            return;
        }

        file_put_contents($this->intervalFile, $interval);
        $this->applyCron($interval);

        $this->json(true, "Interval backup diperbarui.");
    }

    private function applyCron(string $interval) {
        $map = [
            "1_min" => "* * * * *",
            "5_min" => "*/5 * * * *",
            "15_min" => "*/15 * * * *",
            "30_min" => "*/30 * * * *",
            "1_hour" => "0 * * * *",
            "2_hour" => "0 */2 * * *",
            "3_hour" => "0 */3 * * *",
            "6_hour" => "0 */6 * * *",
            "9_hour" => "0 */9 * * *",
            "12_hour" => "0 */12 * * *",
            "18_hour" => "0 */18 * * *",
            "1_day" => "0 0 * * *",
            "3_day" => "0 0 */3 * *",
            "1_week" => "0 0 * * 0",
            "2_week" => "0 0 */14 * *",
            "3_week" => "0 0 */21 * *",
            "1_month" => "0 0 1 * *",
            "2_month" => "0 0 1 */2 *",
            "3_month" => "0 0 1 */3 *",
            "6_month" => "0 0 1 */6 *",
            "9_month" => "0 0 1 */9 *",
            "1_year" => "0 0 1 1 *",
        ];

        $cron = $map[$interval] ?? "* * * * *";

        $job = "$cron /var/www/paserexpress/src/cron/autobackup.sh >/dev/null 2>&1";

        file_put_contents("/etc/cron.d/paserexpress_backup", $job . PHP_EOL);
    }

    private function json(bool $ok, string $msg) {
        echo json_encode(["success" => $ok, "message" => $msg]);
    }
}
