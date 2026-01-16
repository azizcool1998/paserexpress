<?php

class LogController {

    private array $logs;

    public function __construct() {
        $this->logs = [
            "nginx_main" => "/var/log/nginx/error.log",
            "nginx_site" => "/var/log/nginx/paserexpress.error.log",
            "php_fpm"    => "/var/log/php8.3-fpm.log",
            "mariadb"    => "/var/log/mysql/error.log",
            "syslog"     => "/var/log/syslog"
        ];
    }

    public function index() {
        $logs = $this->logs;
        require __DIR__ . '/../views/admin_logs.php';
    }

    public function read() {
        $type = $_GET['type'] ?? '';
        if (!isset($this->logs[$type])) {
            echo json_encode(["success" => false, "error" => "Invalid log type"]);
            return;
        }

        $file = $this->logs[$type];
        if (!file_exists($file)) {
            echo json_encode(["success" => false, "error" => "Log file not found"]);
            return;
        }

        $output = shell_exec("tail -n 200 $file 2>&1");
        echo json_encode(["success" => true, "log" => $output]);
    }

    public function analyze() {
        $output = shell_exec("php " . __DIR__ . "/../tools/analyze_logs.php 2>&1");
        echo json_encode(["success" => true, "result" => $output]);
    }

    public function fix() {
        $output = shell_exec("bash " . __DIR__ . "/../tools/logfix.sh 2>&1");
        echo json_encode(["success" => true, "fix_output" => $output]);
    }

    public function clear() {
        $type = $_GET['type'] ?? '';
        if (!isset($this->logs[$type])) {
            echo json_encode(["success" => false]);
            return;
        }

        file_put_contents($this->logs[$type], "");
        echo json_encode(["success" => true]);
    }
}
