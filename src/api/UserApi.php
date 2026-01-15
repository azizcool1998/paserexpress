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
