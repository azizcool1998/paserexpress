<?php

class TrackingApi extends ApiController
{
    public function track()
    {
        $session = api_auth_required();
        $order_id = $_GET["id"] ?? 0;

        $stmt = $this->pdo->prepare("SELECT * FROM tracking WHERE order_id=? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$order_id]);

        api_response(true, "Tracking data", $stmt->fetch());
    }
}
