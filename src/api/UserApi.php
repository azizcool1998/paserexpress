<?php

class UserApi extends ApiController
{
    public function profile()
    {
        $session = api_auth_required();
        $uid = $session["uid"];

        $stmt = $this->pdo->prepare("SELECT id, username, first_name, last_name, email, whatsapp, role FROM users WHERE id=?");
        $stmt->execute([$uid]);
        $data = $stmt->fetch();

        api_response(true, "Profile data", $data);
    }
}
public function all()
{
    $session = api_auth_required();

    // hanya admin
    $uid = $session["uid"];
    $me = $this->pdo->prepare("SELECT role FROM users WHERE id=?");
    $me->execute([$uid]);
    if ($me->fetchColumn() !== "admin") {
        api_response(false, "Admin only", null, 403);
    }

    $stmt = $this->pdo->query("SELECT id, username, email, role, is_admin FROM users");

    api_response(true, "Users list", $stmt->fetchAll());
}
public function create_driver()
{
    $session = api_auth_required();
    $uid = $session["uid"];

    // cek admin
    $role = $this->pdo->query("SELECT role FROM users WHERE id=$uid")->fetchColumn();
    if ($role !== "admin") api_response(false, "Admin only", null, 403);

    $json = json_decode(file_get_contents("php://input"), true);

    $u = $json["username"] ?? "";
    $p = $json["password"] ?? "";
    $wa = $json["whatsapp"] ?? "";

    if ($u=="" || $p=="" || $wa=="") api_response(false, "Missing fields", null, 400);

    $stmt = $this->pdo->prepare("INSERT INTO users (username,password_hash,role,whatsapp,is_admin,is_active) VALUES (?,?,?,?,0,1)");
    $stmt->execute([$u, password_hash($p,PASSWORD_DEFAULT), "driver", $wa]);

    api_response(true, "Driver created", ["id" => $this->pdo->lastInsertId()]);
}
