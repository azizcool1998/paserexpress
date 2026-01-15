<?php

require_once __DIR__ . "/../includes/bootstrap.php";
require_once __DIR__ . "/helpers.php";

class ApiController
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }
}
