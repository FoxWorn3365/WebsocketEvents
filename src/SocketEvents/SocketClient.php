<?php

namespace SocketEvents;

use pocketmine\utils\TextFormat;
use FoxWorn3365\WebsocketEvents\PermissionManager;

class SocketClient {
    protected $client;
    public bool $connected = false;
    public $message;
    public $id;
    public $log;
    public bool $needsMask;
    public string $ip = "void";
    public int $port = 0;
    public $key;
    public array $allowed = [];
    public array $permissions = [];

    function __construct(\Socket $connection, $log, bool $needsMask = false) {
        $this->id = rand(10, 1000) . rand(10, 1000);
        $this->client = $connection;
        $this->log = $log;
        $this->needsMask = $needsMask;
    }

    public function onMessage(callable $callback) : void {
        $this->message = $callback;
    }

    public function clearSendWithResponse(string $message, int $lenght = 1024) : string {
        $this->clearSend($message);
        return $this->clearRead($lenght);
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
        $header = "";
    
        if ($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        elseif ($length >= 65536)
            $header = pack('CCNN', $b1, 127, $length);
        return $header . $text;
    }

    public function read(int $lenght = 1024) : ?string {
        $response = socket_read($this->client, $lenght);
        if ($this->needsMask) {
            return $this->unmask($response);
        }
        return $response;
    }

    public function clearRead(int $lenght = 1024) : ?string {
        return @socket_read($this->client, $lenght);
    }

    public function translate(string $text) : string {
        return $this->unmask($text);
    }

    public function write(string $message) : ?bool {
        if ($this->needsMask) {
            $message = $this->mask($message);
        }
        if (!$this->connected) {
            return false;
        }
        $status = @socket_send($this->client, $message, strlen($message), 0);
        if (!$status) {
            return false;
        } else {
            return true;
        }
    }

    public function clearSend(string $message) : bool {
        $status = socket_send($this->client, $message, strlen($message), 0);
        if (!$this->connected) {
            return false;
        }
        if (!$status) {
            return false;
        }

        return true;
    }

    public function send(string $message) : ?bool {
        return $this->write($message);
    }

    public function isAllowed() {
        if (!in_array($this->key, $this->allowed)) {
            $this->log->info(TextFormat::RED . "[CustomServer][WatchDog] Unauthorized client {$this->id} tried to connect, removing it...");
            return false;
        }
        return true;
    }

    public function accept(string $request) : bool {
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
        preg_match('#Authorization: Basic (.*)\r\n#', $request, $matches_a);
        $this->key = str_replace(': ', '', base64_decode(@$matches_a[1]) . ' ');
        $this->log->info(TextFormat::GRAY . "[CustomServer][WatchDog] Security token excange from {$this->id}: BasicAuth@{$this->key}");
        $this->log->info(TextFormat::DARK_GREEN . "[CustomServer][WatchDog] Security token excange with {$this->id} completed, client " . TextFormat::BOLD . "connected");
        if (!$this->isAllowed()) {
            $this->send('Cant connect!');
            $this->close();
            return false;
        }
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
        return true;
    }

    public function dispatchPermissions(array|object $permissions, PermissionManager $manager) : void {
        $localPerm = @$permissions[$this->key] ?? [];
        $this->permissions = $manager->getPermissions($localPerm);
    }

    public function clearAccept() : void {
        $this->clearSend('connected!');
        $this->connected = true;
    }

    public function auto() : void {
        $this->connected = true;
    }

    public function loop(int $lenght = 1024) {
        while ($this->connected) {
            $msg = socket_read($this->client, $lenght);
            ($this->message)($msg, $this);
        }
    }

    public function close() : void {
        if (!$this->connected) {
            return;
        }
        $this->connected = false;
        $this->log->info(TextFormat::GRAY . "[CustomServer][I] User {$this->id} disconnected!");
        socket_close($this->client); 
    }

    public function getIP() : string {
        return $this->ip;
    }
}