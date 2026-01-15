<?php
require_once __DIR__ . "/../includes/bootstrap.php";
require_once __DIR__ . "/JWT.php";

function api_response($status, $message, $data = null, $code = 200)
{
    http_response_code($code);
    header("Content-Type: application/json");

    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_generate_token($uid)
{
    $secret = env("APP_KEY", "PASEREXPRESS_SECRET");

    return JWT::encode([
        "uid" => $uid,
        "iat" => time(),
        "exp" => time() + (86400 * 7) // token berlaku 7 hari
    ], $secret);
}

function api_auth_required()
{
    $headers = getallheaders();
    $auth = $headers["Authorization"] ?? "";
    
    if (!str_starts_with($auth, "Bearer ")) {
        api_response(false, "Missing Bearer Token", null, 401);
    }

    $jwt = substr($auth, 7);

    $secret = env("APP_KEY", "PASEREXPRESS_SECRET");
    $payload = JWT::decode($jwt, $secret);

    if (!$payload) {
        api_response(false, "Invalid or expired token", null, 401);
    }

    return $payload;
}
