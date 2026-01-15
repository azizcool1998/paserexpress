<?php
require_once __DIR__ . "/../includes/bootstrap.php";
require_once __DIR__ . "/helpers.php";

$endpoint = $_GET["endpoint"] ?? "";

$routes = [
    "auth/login"         => ["AuthApi", "login"],
    "user/profile"       => ["UserApi", "profile"],
    "order/create"       => ["OrderApi", "create"],
    "order/list"         => ["OrderApi", "my_orders"],
    "tracking/get"       => ["TrackingApi", "track"],
];

if (!isset($routes[$endpoint])) {
    api_response(false, "Unknown endpoint: $endpoint", null, 404);
}

[$class, $method] = $routes[$endpoint];

require_once __DIR__ . "/{$class}.php";

$controller = new $class;
$controller->$method();
