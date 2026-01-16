<?php
class AdminSettingsController
{
    public function page()
    {
        require __DIR__ . '/../views/admin_settings.php';
    }

    public function save()
    {
        require_admin();

        $new = [
            "APP_NAME" => $_POST['app_name'],
            "APP_BASE_URL" => $_POST['app_domain'],
            "NODE_DOMAIN" => $_POST['node_domain'],
            "BACKUP_ENABLED" => isset($_POST['backup_enabled']) ? 'yes' : 'no',
            "BACKUP_INTERVAL" => $_POST['backup_interval'],
        ];

        save_settings($new);

        header("Location: ?page=admin_settings&saved=1");
        exit;
    }
}
