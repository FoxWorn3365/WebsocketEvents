<?php

namespace SocketEvents;

class SocketClient {
    protected $client;
    protected bool $connected;
    public $message;
    public $id;
    public $log;

    function __construct(\Socket $connection, $log) {
        $this->id = rand(10, 1000) . rand(10, 1000);
        $this->client = $connection;
        $this->log = $log;
    }

    public function onMessage(callable $callback) : void {
        $this->message = $callback;
    }

    protected function unmask(string $text) : string {
        $length = ord($text[1]) & 127;
        if ($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        } elseif ($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        } else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }

    protected function mask(string $text) : string {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);
    
        if ($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        elseif ($length >= 65536)
            $header = pack('CCNN', $b1, 127, $length);
        return $header . $text;
    }

    public function read(int $lenght = 1024) : ?string {
        return $this->unmask(socket_read($this->client, $lenght, PHP_BINARY_READ));
    }

    public function write(string $message) : void {
        $message = $this->mask($message);
        if (!socket_send($this->client, $message, strlen($message), 0)) {
            $this->close();
        }
    }

    public function clearSend(string $message) : void {
        if (!socket_send($this->client, $message, strlen($message), 0)) {
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
        $this->clearSend($headers);
        $this->connected = true;
    }

    public function loop(int $lenght = 1024) {
        while ($this->connected) {
            $msg = socket_read($this->client, $lenght);
            ($this->message)($msg, $this);
        }
    }

    public function close() {
        $this->connected = false;
        $log->info(TextFormat::GRAY . "[CustomServer][I] User {$this->id} disconnected!");
        socket_close($this->client); 
        unset($this);
    }
}