<?php

declare(strict_types=1);

namespace WebsocketEvents;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\Language;
use pocketmine\player\PlayerDataProvider;
use FoxSocket\SocketClientManager;

require __DIR__ . '/../FoxSockets/SocketManager.php';

class Core extends PluginBase {
    protected $server;
    protected ?array $clients = [];
    public $socket;
    public $config;
    public ConsoleCommandSender $console;
    public Language $language;
    public $socketID;

	public function onLoad() : void{
        // Assign SocketID
        $this->socketID = rand(1, 1000) . rand(8, 1931);
		$this->getLogger()->info(TextFormat::WHITE . " Plugin loaded!");
        // Bind the console
        $this->language = new Language('eng');
        $this->console = new ConsoleCommandSender($this->getServer(), $this->language);

	}

	public function onEnable() : void{
        // Loading config
        @mkdir($this->getDataFolder());
        $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        if (!$this->config->exists('address')) {
            $this->config->set('address', 'localhost');
            $this->config->save();
        }
        if (!$this->config->exists('port')) {
            $this->config->set('port', 1945);
            $this->config->save();
        }
//		$this->getServer()->getPluginManager()->registerEvents(new ExampleListener($this), $this);
//		$this->getScheduler()->scheduleRepeatingTask(new BroadcastTask($this->getServer()), 120);
		$this->getLogger()->info(TextFormat::DARK_GREEN . " Plugin enabled!");
        // Creating the WebSocket server
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $this->config->get('address', 'localhost'), $this->config->get('port', 1991));
        // Now fork the socket client part to handle multiple client
        $pid = pcntl_fork();

        if ($pid == -1) {
            die("Cannot fork!");
        } elseif ($pid) {
            $this->getLogger()->info(TextFormat::YELLOW . " Loading WSS Server...");
            sleep(2);
            $this->getLogger()->info(TextFormat::DARK_GREEN . " Connection to wss server...");
            return;
        }

        socket_listen($this->server);
        $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] WSS CustomServer1 started!");
        while (true) {
            $client = socket_accept($this->server);
            // Accept connection
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
            socket_write($client, $headers, strlen($headers));

            $this->clients[] = $client;
            $pog = pcntl_fork();
            if ($pog) {
                $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] Connection received, restarting WSS listen...");
                continue;
            }
            $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] Client " . count($this->clients)-1 . " conected");
            $clientID = count($this->clients)-1;
            // Client management - Main fork and listen activated
            while (true) {
                $message = socket_read($client, 10024, PHP_BINARY_READ);
                $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] SocketMessage from Client {$clientID}: {$message}");
                // Received a message, elaborate this!
                if ($message == 'hello world') {
                    $response = 'Hello world v1.2 - SocketStream!';
                    socket_write($client, $response, strlen($response));
                    continue;
                } elseif ($message == 'close') {
                    $response = 'Closing client session...';
                    socket_write($client, $response, strlen($response));
                    socket_close($client);
                    break;
                }

                if ($data = @json_decode($message) === false || $data = @json_decode($message) === null) {
                    $response = 'Unknow manager';
                    socket_write($client, $response, strlen($response));
                    $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] Client " . count($this->clients)-1 . " sent an invalid message!");
                    continue;
                }

                // Callback
                $response = 'Valid JSON';
                socket_write($client, $response, strlen($response));
            }
        }
        // Save socket client in the memory
        //apcu_store("{$this->socketID}_pm-socket", $this->socket);
	}

	public function onDisable() : void{
        // Let's check if some socket connection is open!
        /*
        if (apcu_exists("{$this->socketID}_pm-socket")) {
            $this->getLogger()->info(TextFormat::YELLOW . "[Custom Server][] SocketServer in memory active, turn off...");
            apcu_fetch("{$this->socketID}_pm-socket")->close();
            $this->getLogger()->info(TextFormat::DARK_GREEN . "[Custom Server][] WebSocket server successfully turned off!");
        } else {
            $this->getLogger()->info(TextFormat::GREEN . "[Custom Server][] No WebSocket instance active in memory. " . TextFormat::RED . "You can consider this an error I think");
        }
        */
        //$this->socket->close();
		$this->getLogger()->info(TextFormat::DARK_RED . " Plugin disabled!");
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "example":
				$sender->sendMessage("Hello " . $sender->getName() . "!");

				return true;
			default:
				throw new \AssertionError("This line will never be executed");
		}
	}
}