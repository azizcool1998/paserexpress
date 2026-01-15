<?php

class TrackingSocket
{
    private $address;
    private $port;
    private $clients = [];

    public function __construct($address, $port)
    {
        $this->address = $address;
        $this->port = $port;
    }

    public function run()
    {
        $server = stream_socket_server("tcp://{$this->address}:{$this->port}", $errno, $errstr);

        if (!$server) die("Failed: $errstr ($errno)");

        echo "WebSocket Tracking Running on :{$this->port}\n";

        while (true) {
            $client = @stream_socket_accept($server);
            if ($client) {
                $this->clients[] = $client;
                $this->handle($client);
            }
        }
    }

    private function broadcast($msg)
    {
        foreach ($this->clients as $client) {
            @fwrite($client, $msg . "\n");
        }
    }

    private function handle($client)
    {
        while (!feof($client)) {
            $data = trim(fgets($client));

            if ($data == "") continue;

            // Broadcast lokasi ke semua client
            $this->broadcast($data);
        }
    }
}
