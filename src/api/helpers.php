<?php

function api_response($status, $message, $data = null, $code = 200)
{
    http_response_code($code);
    header("Content-Type: application/json");

    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

function api_auth_required()
{
    $headers = getallheaders();
    $token = $headers["Authorization"] ?? "";

    if (!str_starts_with($token, "Bearer ")) {
        api_response(false, "Unauthorized: Missing Bearer token", null, 401);
    }

    $token = substr($token, 7);

    if (!api_verify_token($token)) {
        api_response(false, "Unauthorized: Invalid token", null, 401);
    }

    return api_decode_token($token);
}

function api_generate_token($user_id)
{
    $payload = [
        "uid" => $user_id,
        "time" => time()
    ];

    $secret = env("APP_KEY", "PASEREXPRESS_SECRET");

    return base64_encode(json_encode($payload) . "." . hash_hmac("sha256", json_encode($payload), $secret));
}

function api_verify_token($token)
{
    $secret = env("APP_KEY", "PASEREXPRESS_SECRET");

    $decoded = base64_decode($token);
    if (!$decoded || !str_contains($decoded, ".")) return false;

    [$json, $hash] = explode(".", $decoded, 2);
    $verify = hash_hmac("sha256", $json, $secret);

    return hash_equals($verify, $hash);
}

function api_decode_token($token)
{
    $decoded = base64_decode($token);
    [$json] = explode(".", $decoded, 2);
    return json_decode($json, true);
}
