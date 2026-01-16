<?php

class UpdateController {

    private string $updateDir;
    private string $versionFile;
    private string $timeFile;

    public function __construct() {
        $root = __DIR__ . '/../';
        $this->updateDir  = $root . 'update';
        $this->versionFile = $this->updateDir . '/last_version.txt';
        $this->timeFile    = $this->updateDir . '/last_update.txt';
    }

    public function index() {
        $lastVersion = file_exists($this->versionFile)
            ? trim(file_get_contents($this->versionFile))
            : "(belum pernah update)";

        $lastUpdate = file_exists($this->timeFile)
            ? trim(file_get_contents($this->timeFile))
            : "(belum pernah update)";

        require __DIR__ . '/../views/admin_update.php';
    }

    public function runUpdate() {
        $output = shell_exec("bash /var/www/paserexpress/src/update/updater.sh 2>&1");
        echo json_encode(["success" => true, "output" => $output]);
    }

    public function checkUpdate() {
        $repo = "https://api.github.com/repos/azizcool1998/paserexpress/commits/main";
        $json = shell_exec("curl -s $repo -H 'User-Agent: PaserExpress'");
        $data = json_decode($json, true);

        $latest = substr($data['sha'] ?? "", 0, 7);
        $current = file_exists($this->versionFile)
            ? trim(file_get_contents($this->versionFile))
            : "(unknown)";

        $hasUpdate = $latest && $current !== $latest;

        echo json_encode([
            "success" => true,
            "latest" => $latest,
            "current" => $current,
            "update_available" => $hasUpdate
        ]);
    }
}
