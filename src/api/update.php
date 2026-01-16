<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

$repo = "https://github.com/azizcool1998/paserexpress.git";
$root = realpath(__DIR__ . "/../../");

$output = [
    "success" => false,
    "message" => "",
];

try {

    // 1. Backup sebelum update
    exec("bash /usr/local/bin/paserexpress-backup.sh --quick", $o1);

    // 2. Pull update Git
    exec("cd {$root} && git fetch --all", $o2);
    exec("cd {$root} && git reset --hard origin/main", $o3);

    // 3. Regenerate env jika template berubah
    if (file_exists("$root/.env.template")) {
        $templateVars = file_get_contents("$root/.env.template");
        if (!empty($templateVars)) {
            file_put_contents("$root/.env", $templateVars);
        }
    }

    // 4. Restart services
    exec("systemctl reload php8.3-fpm");
    exec("systemctl reload nginx");

    $output["success"] = true;
    $output["message"] = "Update berhasil di-install.";

} catch (Exception $e) {
    $output["message"] = "Update gagal: " . $e->getMessage();
}

echo json_encode($output);
