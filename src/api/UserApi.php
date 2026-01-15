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
