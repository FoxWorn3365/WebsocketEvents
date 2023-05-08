<?php

namespace SocketEvents;

class SocketClient {
    protected $client;
    protected bool $connected;
    protected $message;
    protected $id;

    function __construct(\Socket $connection) {
        $this->client = $connection;
    }

    public function onMessage(callable $callback) : void {
        $this->message = $callback;
    }

    public function write(string $message) : void {
        if (!socket_send($this->client, $message, strlen($message))) {
            $this->close();
        }
    }

    public function send(string $message) : void {
        $this->write($message);
    }

    public function accept(string $request) : void {
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
        $key = base64_encode(pack(
            'H*',
            sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
        ));
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: {$key}\r\n\r\n";
        $this->send($headers);
        $this->connected = true;
        $this->id = rand(10, 1000) . rand(10, 1000);
    }

    protected function loop(int $lenght = 1024) {
        while ($this->connected) {
            $msg = socket_read($client, $lenght);
            $this->message($msg, $this);
        }
    }

    public function close() {
        $this->connected = false;
        socket_close($this->client); 
    }
}