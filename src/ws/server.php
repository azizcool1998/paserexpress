<?php
require_once __DIR__ . "/TrackingSocket.php";

$socket = new TrackingSocket("0.0.0.0", 8282);
$socket->run();
