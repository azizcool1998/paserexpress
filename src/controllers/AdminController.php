<?php

class AdminController
{
    public function __construct()
    {
        if (empty($_SESSION['admin'])) redirect("?page=login");
    }

    public function dashboard()
    {
        $pdo = db();
        $users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();

        view("admin_dashboard", compact('users'));
    }

    public function backupSettings() {
        $settings = load_backup_settings();
        render("admin/admin_backup", compact("settings"));
    }

    public function backupSave() {
        $enabled = $_POST['enabled'] ?? 'off';
        $interval = $_POST['interval'] ?? '1d';

        save_backup_settings($enabled, $interval);
        generate_cron_backup($enabled, $interval);

        redirect("?page=admin_backup&saved=1");
    }

    public function backupRunNow() {
        require_once __DIR__ . "/../services/backup_service.php";
        run_backup_now();
        redirect("?page=admin_backup&run=1");
    }
}
