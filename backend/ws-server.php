<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\ChatServer;

$settings = require __DIR__ . '/src/settings.php';
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer($settings)
        )
    ),
    8080
);

echo "WebSocket server started on ws://0.0.0.0:8080\n";
$server->run();
