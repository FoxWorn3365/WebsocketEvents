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
use Sock\SocketClient;
use Sock\SocketServer;
use Sock\SocketException;

require __DIR__ . '/../Sock/SocketServer.php';

class Core extends PluginBase {
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
        // Enabling websocket server - ASYNC WAY
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("Could not fork!");
        } elseif ($pid) {
            $this->getLogger()->info(TextFormat::YELLOW . " Loading WSS Server...");
            // Seems to be the parent project
            return;
        }
        $this->socket = new SocketServer($this->config->get('address', 'localhost'), $this->config->get('port', 1991));
        $this->socket->init();
        $this->socket->setConnectionHandler(function($client) {
            $pid = pcntl_fork();
	
            if ($pid == -1) {
                 die('Could not fork!');
            } else if ($pid) {
                // parent process
                return;
            }

            $client->send(json_encode(['status' => 200, 'connected' => true]));

            $this->getLogger()->info(TextFormat::GREEN . "[Custom Server][] Client connected!");

            while (true) {
                $message = $client->read();
                if ($message == '') {
                    continue;
                }

                // Let's see if it's a "hello world" message!
                if ($message == "hello world") {
                    $client->send("Hello world! v1");
                    continue;
                }

                echo "Received message! - {$message}\n";
                // Send a command to the console.
                // WAIT! Let's see the type! if it's get SO we need to send the user's informations!
                $message = json_decode($message);
    
                if ($message->type == "GET") {
                    if ($message->request == "player") {
                        // Retrive information about a player
                        $cached = false;
                        $player = $this->getServer()->getPlayerExact($message->player);
                        $client->send(json_encode(['status' => 200, 'message' => 'Got player!', 'cached' => $cached, 'data' => $player]));
                    } elseif ($message->request == "playerList") {
                        $list = $this->getServer()->getOnlinePlayers();
                        $client->send(json_encode(['status' => 200, 'message' => 'Got player!', 'cached' => false, 'data' => $list]));
                    } elseif ($message->request == "custom") {
                        $data = $this->getServer()->{$message->function}($message->arguments);
                        $client->send(json_encode(['status' => 200, 'message' => 'Got player!', 'cached' => false, 'data' => $data]));
                    }
                }
            }
            $this->getLogger()->info(TextFormat::ORANGE . "[Custom Server][] Client disconnected!");
        });
        $this->socket->listen();
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