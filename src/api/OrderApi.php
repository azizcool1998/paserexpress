<?php

class OrderApi extends ApiController
{
    public function create()
    {
        $session = api_auth_required();
        $uid = $session["uid"];

        $json = json_decode(file_get_contents("php://input"), true);

        $pickup = $json["pickup"] ?? "";
        $dropoff = $json["dropoff"] ?? "";
        $price = $json["price"] ?? "";

        if ($pickup === "" || $dropoff === "" || $price === "") {
            api_response(false, "Missing required fields", null, 400);
        }

        $stmt = $this->pdo->prepare("INSERT INTO orders (user_id,pickup,dropoff,price,status) VALUES (?,?,?,?,?)");
        $stmt->execute([$uid, $pickup, $dropoff, $price, "pending"]);

        api_response(true, "Order created", ["order_id" => $this->pdo->lastInsertId()]);
    }

    public function my_orders()
    {
        $session = api_auth_required();
        $uid = $session["uid"];

        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
        $stmt->execute([$uid]);

        api_response(true, "Orders list", $stmt->fetchAll());
    }
}
