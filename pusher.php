<?php

use App\Api\V1\Controllers\PusherController;

require  'vendor/autoload.php';
require  'bootstrap/app.php';

$loop   = React\EventLoop\Factory::create();
$pusher = new PusherController;

// Listen for the web server to make a ZeroMQ push after an ajax request
$context = new React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:'.env('ZMQ_TCP_PORT')); // Binding to 127.0.0.1 means the only client that can connect is itself
$pull->on('message', array($pusher, 'onBlogEntry'));

// Set up our WebSocket server for clients wanting real-time updates
$webSock = new React\Socket\Server($loop); // Binding to 0.0.0.0 means remotes can connect
$webSock->listen(env('ZMQ_PORT'), '0.0.0.0'); 
$webServer = new Ratchet\Server\IoServer(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer(
            new Ratchet\Wamp\WampServer(
                $pusher
            )
        )
    ),
    $webSock
);

$loop->run();