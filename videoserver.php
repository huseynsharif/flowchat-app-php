<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class VideoCallServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients[$conn->resourceId] = $conn;
        echo "Yeni müştəri qoşuldu ({$conn->resourceId})\n";

        // Yeni qoşulan istifadəçini digərlərinə bildir
        foreach ($this->clients as $clientId => $client) {
            if ($clientId != $conn->resourceId) {
                $client->send(json_encode(["type" => "join", "from" => $conn->resourceId]));
            }
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data) return;

        // Mesajı müvafiq istifadəçiyə göndər
        foreach ($this->clients as $clientId => $client) {
            if ($data['to'] == $clientId) {
                $client->send(json_encode(array_merge($data, ["from" => $from->resourceId])));
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        unset($this->clients[$conn->resourceId]);
        echo "Müştəri ayrıldı ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Xəta: {$e->getMessage()}\n";
        $conn->close();
    }
}

use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(new VideoCallServer())
    ),
    8081
);

echo "Video zəng serveri 8081 portunda işə düşdü...\n";
$server->run();
