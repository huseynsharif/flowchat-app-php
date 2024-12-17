<?php
require __DIR__ . '/vendor/autoload.php';
require 'db.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $pdo;

    public function __construct($pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo; // PDO bağlantısını təyin edirik
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Yeni müştəri qoşuldu ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Mesaj alındı: {$msg}\n";

        $data = json_decode($msg, true);
        if (isset($data['username']) && isset($data['message'])) {
            $username = $data['username'];
            $message = $data['message'];

            // İstifadəçi ID-sini tapırıq
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                $userId = $user['id'];

                // Mesajı DB-ə yazırıq
                $insertStmt = $this->pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
                $insertStmt->execute([$userId, $message]);
            }
        }

        // Bütün müştərilərə mesajı geri göndəririk
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
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

// PDO bağlantısını ötürərək ChatServer obyektini yaradın
$chatServer = new ChatServer($pdo);

$server = IoServer::factory(
    new HttpServer(
        new WsServer($chatServer)
    ),
    8080
);

echo "WebSocket serveri 8080 portunda işə düşdü...\n";
$server->run();
