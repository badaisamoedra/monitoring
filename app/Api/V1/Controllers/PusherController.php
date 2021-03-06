<?php
namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use App\Models\MwMapping;
use App\Models\Topic;
use App\Helpers;

class PusherController implements WampServerInterface  {
    /**
     * A lookup of all the topics clients have subscribed to
     */
    protected $subscribedTopics = array();

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onSubscribe(ConnectionInterface $conn, $topic) {
        $this->users[$conn->resourceId] = $conn;
        $doSubscribe = $this->subscribedTopics[$topic->getId()] = $topic;
        if($topic->getId() == 'dashboard'){
            $pushData = array(
                'topic' => $topic->getId(),
                'data'  => Helpers::dashboardFormat()
            );
        }elseif($topic->getId() == 'tracking-all'){
            $pushData = array(
                'topic' => $topic->getId(),
                'data'  => Helpers::allTrackingFormat()
            );
        }elseif($topic->getId() == 'notification'){
            $pushData = array(
                'topic' => $topic->getId(),
                'data'  => ''
            );
        }else{
            $pushData = array(
                'topic' => $topic->getId(),
                'data'  => Helpers::singleTrackingFormat($topic->getId())
            );
        }
        $objectTopic = New Topic;
        $checkTopic  = $objectTopic->where('name', $topic->getId())->count();
        if(!$checkTopic){
            $objectTopic->create(['name' => $topic->getId()]);
            echo "insert a new topic ({$topic->getId()})\n";
        }

        echo "New subscriber! ({$conn->resourceId})\n";
        $doSubscribe->broadcast($pushData);
       
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic) {
        $totalSubscriber = $this->subscribedTopics[$topic->getId()]->count();
        echo "One client unsubscriber a topic {$topic->getId()} \n";
        if(empty($totalSubscriber)){
            unset($this->subscribedTopics[$topic->getId()]);
            Topic::where('name', $topic->getId())->delete();
            echo "One topic has been removed\n";
        }
    }
    
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->close();
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->close();
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function onBlogEntry($entry) {
	
        $entryData = json_decode($entry, true);

        // If the lookup topic object isn't set there is no one to publish to
        if (!array_key_exists($entryData['topic'], $this->subscribedTopics)) {
            return;
        }

        $topic = $this->subscribedTopics[$entryData['topic']];

        // re-send the data to all the clients subscribed to that topic
        $topic->broadcast($entryData);
    }

}