<?php

class AuthController
{
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === "POST") {

            $u = trim($_POST['username']);
            $p = trim($_POST['password']);

            $pdo = db();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
            $stmt->execute([$u]);
            $data = $stmt->fetch();

            if ($data && password_verify($p, $data['password_hash'])) {

                $_SESSION['uid'] = $data['id'];
                $_SESSION['role'] = $data['role'];
                $_SESSION['admin'] = $data['is_admin'];

                if ($data['is_admin']) redirect("?page=admin_dashboard");
                if ($data['role'] === "driver") redirect("?page=driver_dashboard");
                redirect("?page=pelanggan_dashboard");
            }

            $error = "Username atau password salah!";
            view("login", compact('error'));
            return;
        }

        view("login");
    }

    public function logout()
    {
        session_destroy();
        redirect("?page=login");
    }
}
