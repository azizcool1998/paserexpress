<?php

class DriverController
{
    public function __construct()
    {
        if (!isset($_SESSION['uid']) || $_SESSION['role'] !== 'driver') {
            redirect("?page=login");
        }
    }

    public function dashboard()
    {
        view("driver_dashboard");
    }
}
