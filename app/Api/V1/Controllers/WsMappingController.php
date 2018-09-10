<?php
namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\MwMapping;
use App\Helpers;
use Illuminate\Support\Facades\DB;

class WsMappingController extends BaseController implements MessageComponentInterface
{
    protected $clients;
    private $subscriptions;
    private $users;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->users = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo 'new connection open';
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $data = json_decode($msg);
        switch ($data->command) {
            case "subscribe":
                $this->subscriptions[$conn->resourceId] = $data->channel;
                break;
            case "message":
                if (isset($this->subscriptions[$conn->resourceId])) {
                    $target = $this->subscriptions[$conn->resourceId];
                    foreach ($this->subscriptions as $id=>$channel) {
                        if ($channel == $target && $id != $conn->resourceId) {
                            $this->users[$id]->send($data->message);
                        }
                    }
                }
        }
        $result = [];
        $result['showVehicleLocation'] = Helpers::showVehicleLocation();
        $result['showUtilization']     = Helpers::showUtilization();
        $result['showAssetUsage']      = Helpers::showAssetUsage();

        foreach($this->clients as $client){
            $client->send(json_encode($result));
        }
    }


    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo 'new connection close';
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }


}