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
}
