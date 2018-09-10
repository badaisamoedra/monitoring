<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Api\V1\Controllers\WsMappingController;
use Illuminate\Support\Facades\DB;

require  'vendor/autoload.php';
require  'bootstrap/app.php';


$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WsMappingController()
        )
    ),
    8900
);

$server->run();
?>