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
use SocketEvents\SocketClient;

require __DIR__ . '/../SocketEvents/SocketClient.php';

class Core extends PluginBase {
    protected $server;
    protected ?array $clients = [];
    public $socket;
    public $config;
    public ConsoleCommandSender $console;
    public Language $language;
    public $socketID;
    public bool $server_status;

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
		$this->getLogger()->info(TextFormat::DARK_GREEN . "Plugin enabled!");
        // Creating the WebSocket server
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->server_status = true;
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $this->config->get('address', 'localhost'), $this->config->get('port', 1991));
        // Now fork the socket client part to handle multiple client
        $pid = pcntl_fork();

        if ($pid == -1) {
            die("Cannot fork!");
        } elseif ($pid) {
            $this->getLogger()->info(TextFormat::YELLOW . "Loading WSS Server...");
            sleep(2);
            $this->getLogger()->info(TextFormat::DARK_GREEN . "Connection to wss server...");
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($this->socket, $this->config->get('address', 'localhost'), $this->config->get('port', 1991));
            $this->socket = new SocketClient($this->socket, $this->getLogger(), false);
            // Needs to send a CLEAR message
            $this->socket->write('skipconnection');
            return;
        }

        socket_listen($this->server);
        $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] WSS CustomServer1 started!");
        while ($this->server_status) {
            $client = socket_accept($this->server);
            // Accept connection
            $request = socket_read($client, 5000);
            $GLOBAL_SERVER = null; // This var is dedicated to listen of simple server
            $client = new SocketClient($client, $this->getLogger());
            $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] New connection to server by Client {$client->id} v13 with message: {$request}");
            if ($request != 'skipconnection') {
                $client->accept($request);
            } else {
                $client->clearAccept();
                $this->getLogger()->info(TextFormat::DARK_GREEN . "[CustomServer][] Recognized the role 'ServerConsole.Server' to Client {$client->id}");
                $GLOBAL_SERVER = $client;
            }
            /*
            $user->onMessage(function(?string $message, SocketClient $user) {
                $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] SocketMessage from Client {$user->id}: {$message}");
                // Received a message, elaborate this!
                if ($message == 'hello world') {
                    $user->send('Hello world v1.2 - SocketStream!');
                    return;
                } elseif ($message == 'close') {
                    $user->send('Closing client session...');
                    $user->close();
                    return;
                }

                if ($data = @json_decode($message) === false || $data = @json_decode($message) === null) {
                    $user->send('Unknow manager!');
                    $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] Client " . count($this->clients)-1 . " sent an invalid message!");
                    return;
                }

                $user->send('I love u');
            });
            $user->loop();
            */
            /*
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
            */

            $pog = pcntl_fork();
            if ($pog) {
                $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] Connection received, restarting WSS listen...");
                continue;
            }
            $connection = true;
            $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] Client {$client->id} conected");
            // Client management - Main fork and listen activated
            while ($connection) {
                $message = $client->read(20048);
                if ($message == false) {
                    $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] Client {$client->id} Disconnected!");
                    $connection = false;
                }
                $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] SocketMessage from Client {$client->id}: {$message}");
                // Received a message, elaborate this!
                if ($message == 'hello world') {
                    /*
                    $response = 'Hello world v1.2 - SocketStream!';
                    socket_write($client, $response, strlen($response));
                    */
                    $client->send('Hello world');
                    continue;
                } elseif ($message == 'close') {
                    $client->close();
                    $connected = false;
                    unset($client);
                    /*
                    $response = 'Closing client session...';
                    socket_write($client, $response, strlen($response));
                    socket_close($client);
                    */
                    break;
                } elseif ($message == 'completeClose') {
                    $client->close();
                    $this->server_status = false;
                    socket_shutdown($this->server);
                    $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] Closed ALL istances of WebSocket Server - Custom v13");
                    $connection = false;
                    break;
                }

                if ($data = @json_decode($message) === false || $data = @json_decode($message) === null) {
                    //$response = 'Unknow manager';
                    //socket_write($client, $response, strlen($response));
                    $client->send('Invalid!');
                    $this->getLogger()->info(TextFormat::GRAY . "[CustomServer][] Client {$client->id} sent an invalid message!");
                    continue;
                }

                // Callback
                $client->send('Valid JSON');
                /*
                $response = 'Valid JSON';
                socket_write($client, $response, strlen($response));
                */
            }
            $this->getLogger()->info(TextFormat::YELLOW . "[CustomServer][] Client [oldclientid] disconnected from mainLoop()!");
            // Close process
            //exec("kill -9 {$pog}");
            return;

        }
        $this->getLogger()->info(TextFormat::RED . "[CustomServer][] WebSocket Server stopped!");
        return;
        // Save socket client in the memory
        //apcu_store("{$this->socketID}_pm-socket", $this->socket);
	}

	public function onDisable() : void{
        $this->socket->write('completeClose');
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