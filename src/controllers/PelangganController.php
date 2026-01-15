<?php

class PelangganController
{
    public function __construct()
    {
        if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'pelanggan') {
            redirect("?page=login");
        }
    }

    public function dashboard()
    {
        view("pelanggan_dashboard");
    }
}
