<?php
namespace App\Api\V1\Controllers;

use App\Http\Controllers\BaseController;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use App\Models\MwMapping;
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

    public function onSubscribe(ConnectionInterface $conn, $topic) {
         
        $doSubscribe = $this->subscribedTopics[$topic->getId()] = $topic;
        $pushData = array(
            'topic' => 'dashboard',
            'data'  => Helpers::dashboardFormat()
        );
        $doSubscribe->broadcast($pushData);
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic) {
        
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "New Connection Close ({$conn->resourceId})\n";
    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->close();
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

    /* The rest of our methods were as they were, omitted from docs to save space */
}