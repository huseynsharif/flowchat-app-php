<?php

$host = 'localhost';
$port = 8081;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, $host, $port);
socket_listen($socket);

$clients = []; // Bağlı müştəriləri izləmək üçün

echo "WebSocket server started at ws://$host:$port\n";

while (true) {
    $read = $clients;
    $read[] = $socket;

    socket_select($read, $write, $except, 0);

    if (in_array($socket, $read)) {
        $newClient = socket_accept($socket);
        $clients[] = $newClient;
        echo "Yeni müştəri bağlandı.\n";
        unset($read[array_search($socket, $read)]);
    }

    foreach ($read as $client) {
        $data = socket_read($client, 2048);
        if (!$data) {
            unset($clients[array_search($client, $clients)]);
            echo "Müştəri bağlantısı kəsildi.\n";
            continue;
        }

        // Signaling mesajlarını yönləndir
        foreach ($clients as $otherClient) {
            if ($otherClient !== $client) {
                socket_write($otherClient, $data, strlen($data));
            }
        }
    }
}

socket_close($socket);
?>
