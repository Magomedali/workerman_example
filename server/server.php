<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Workerman\Worker;
// create a ws-server. all your users will connect to it
$ws_worker = new Worker("websocket://0.0.0.0:8000");

// storage of user-connection link
$pullConnections = [];

$ws_worker->onConnect = function($connection) use (&$pullConnections)
{
    $connection->onWebSocketConnect = function($connection) use (&$pullConnections)
    {
        // put get-parameter into $users collection when a new user is connected
        // you can set any parameter on site page. for example client.html: ws = new WebSocket("ws://127.0.0.1:8000/?user=tester01");

        //$user = array_search($connection, $users);

        foreach ($pullConnections as $id => $connection) {
	    	$connection->send("{$id}: New connection {$connection->id} in our chat!");
	    }
	    
	    $f = fopen("log.log","a+");
	    fwrite($f, "New connection {$connection->id} in our chat!"."\n");
	    fclose($f);

        $pullConnections[$connection->id] = $connection;
        // or you can use another parameter for user identification, for example $_COOKIE['PHPSESSID']
    };
};

$ws_worker->onClose = function($connection) use(&$pullConnections)
{
    // unset parameter when user is disconnected
    $id = array_search($connection, $pullConnections);
    unset($pullConnections[$id]);

    $f = fopen("log.log","a+");
    fwrite($f, "Connection  {$id} closed\n");
    fclose($f);

    foreach ($pullConnections as $c_id => $connection) {
    	$connection->send("{$c_id}: Connection {$id} closed");
    }
};


function onMessageCallbacks(&$pullConnections, $connection,  $data){
	// you have to use for $data json_decode because send.php uses json_encode
    $data = json_decode($data); // but you can use another protocol for send data send.php to local tcp-server
    // send a message to the user by userId
    if (isset($pullConnections[$connection->id]) && isset($data->message)) {
        $webconnection = $pullConnections[$connection->id];
        $webconnection->send($data->message);
    }

    $f = fopen("log.log","a+");
    fwrite($f, json_encode($data)."\n");
    fclose($f);

    foreach ($pullConnections as $id => $connection) {
	    $connection->send("{$id}: Hello");
	}
};



// it starts once when you start server.php:
$ws_worker->onWorkerStart = function() use (&$pullConnections)
{
    // create a local tcp-server. it will receive messages from your site code (for example from send.php)
    $inner_tcp_worker = new Worker("tcp://127.0.0.1:1234");
    
    // create a handler that will be called when a local tcp-socket receives a message (for example from send.php)
    $inner_tcp_worker->onMessage = function($connection, $data) use (&$pullConnections) {

        onMessageCallbacks($pullConnections,$connection,$data);

    };

    $inner_tcp_worker->listen();
};

// Emitted when data is received
$ws_worker->onMessage = function($connection, $data) use (&$pullConnections){
    // you have to use for $data json_decode because send.php uses json_encode
    onMessageCallbacks($pullConnections,$connection,$data);
};


// Run worker
Worker::runAll();