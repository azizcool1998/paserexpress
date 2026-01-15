<?php

class AuthApi extends ApiController
{
    public function login()
    {
        $json = json_decode(file_get_contents("php://input"), true);

        $u = trim($json["username"] ?? "");
        $p = trim($json["password"] ?? "");

        if ($u === "" || $p === "") {
            api_response(false, "Username & password required", null, 400);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
        $stmt->execute([$u]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($p, $user["password_hash"])) {
            api_response(false, "Invalid username/password", null, 401);
        }

        $token = api_generate_token($user["id"]);

        api_response(true, "Login success", [
            "token" => $token,
            "user" => [
                "id" => $user["id"],
                "username" => $user["username"],
                "role" => $user["role"],
            ]
        ]);
    }
}
