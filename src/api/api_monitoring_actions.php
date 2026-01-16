<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header("Content-Type: application/json");

// Protect admin
if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$action = $_GET["action"] ?? "";

function run($cmd) {
    return trim(shell_exec("$cmd 2>&1"));
}

$response = ["success" => false];

switch ($action) {

    case "restart_nginx":
        run("systemctl restart nginx");
        $response = ["success" => true, "message" => "Nginx restarted"];
        break;

    case "restart_php":
        run("systemctl restart php8.3-fpm");
        $response = ["success" => true, "message" => "PHP-FPM restarted"];
        break;

    case "restart_mariadb":
        run("systemctl restart mariadb");
        $response = ["success" => true, "message" => "MariaDB restarted"];
        break;

    case "reboot_server":
        run("nohup bash -c 'sleep 1 && reboot' >/dev/null 2>&1 &");
        $response = ["success" => true, "message" => "Server rebooting"];
        break;

    default:
        $response = ["success" => false, "error" => "Invalid action"];
}

echo json_encode($response);
