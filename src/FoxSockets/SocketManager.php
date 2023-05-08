<?php

namespace FoxSockets;

class SocketClientManager {
    protected $client;
    protected bool $disconnectOnError;

    function __construct(\Socket $client, $disconnectOnError = true) {
        $this->client = $client;
        $this->disconnectOnError = $disconnectOnError;
    }

    public function send(string $message) : bool|void {
        if (!socket_send($this->client, $message, strlen($message)) && $this->disconnectOnError) {
            socket_close($this->client);
            $this->client = null;
        }
    } 
}