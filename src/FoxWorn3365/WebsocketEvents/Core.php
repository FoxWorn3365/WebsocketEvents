<?php

declare(strict_types=1);

namespace FoxWorn3365\WebsocketEvents;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\Language;
use pocketmine\player\PlayerDataProvider;
use FoxWorn3365\WebsocketEvents\Socket\SocketClient;
use FoxWorn3365\WebsocketEvents\Socket\SocketServerRunner;
use pocketmine\Server;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;

//require __DIR__ . '/../SocketEvents/SocketClient.php';

class Core extends PluginBase implements Listener {
    protected $server;
    protected ?array $clients = [];
    public $socket;
    public $config;
    public ConsoleCommandSender $console;
    protected string $defaultConfig = "LS0tCiMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjCiMgXCAgICAgIFwgLyAgICAgIC8gKy0tLS0tLSAgICAgKy0tLS0tICAjCiMgIFwgICAgICBYICAgICAgLyAgfCAgICAgICAgICAgfCAgICAgICAjCiMgICBcICAgIC8gXCAgICAvICAgKy0tLS0tKyAgICAgKy0tLSAgICAjCiMgICAgXCAgLyAgIFwgIC8gICAgICAgICAgfCAgICAgfCAgICAgICAjCiMgICAgIFwvICAgICBcLyAgICAgLS0tLS0tKyAgICAgKy0tLS0tICAjCiMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjCiMgV2ViU29ja2V0IEV2ZW50cyB2MC44QGluZGV2IEJFVEEKIyAiUmVjZWl2ZSwgaGFuZGxlIGFuZCBleGVjdXRlIGFjdGlvbiBmcm9tIGFuZCB0byB5b3VyIFBvY2tldE1pbmUtTVAgc2VydmVyIHZpYSBXZWJTb2NrZXRzISIKIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMKIyAoQykgMjAyMy1ub3cgRmVkZXJpY28gQ29zbWEgKEZveFdvcm4zMzY1KSBhbmQgY29udHJpYnV0b3JzCiMgTUlUIExpY2Vuc2UKIyBSZXF1aXJlIHBocDggb3IgbmV3ZXIKIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMKIyAgICAgICAgQ09OVEFDVFMKIyBFbWFpbDogZm94d29ybjMzNjVAZ21haWwuY29tCiMgRGlzY29yZDogRm94V29ybiMwMDAxCiMgR2l0SHViIGh0dHBzOi8vZ2l0aHViLmNvbS9Gb3hXb3JuMzM2NS9XZWJzb2NrZXRFdmVudHMKIyBHaXRIdWIgKGF1dGhvcik6IGh0dHBzOi8vZ2l0aHViLmNvbS9Gb3hXb3JuMzM2NQojIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMKCmVuYWJsZWQ6IHRydWUgICMjIElzIHRoZSBwbHVnaW4gZW5hYmxlZD8KCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojID4gV2ViU29ja2V0IFNlcnZlciBDb25maWd1cmF0aW9uCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIERlZmF1bHQgc2V0dGluZ3MKc2VydmVyLWlwOiBsb2NhbGhvc3QgICAjIFRoZSBob3N0IG9mIHRoZSBpbnRlcm5hbCBXZWJTb2NrZXQgU2VydmVyLiB1c2UgMC4wLjAuMCB0byBvcGVuIHRvIG90aGVycwpzZXJ2ZXItcG9ydDogMTk5MSAgICAgICMgVGhlIHBvcnQKdGltZW91dDogMiAgICAgICAgICAgICAjIFRpbWVvdXQgKGluIHNlY29uZHMpIG9mIGNsaWVudCBtZXNzYWdlIGxpc3RlbmVyCm1heC1jb25uZWN0aW9uczogMTAgICAgIyBUaGUgbnVtYmVyIG9mIG1heCBzaW11bHRhbmVvdXMgV2ViU29ja2V0IENvbm5lY3Rpb25zIGZvciB0aGlzIHNlcnZlcgphbGxvdy1kb3VibGVhdXRoOiBmYWxzZSAjIEFsbG93cyB0cmFuc21pc3Npb24gb2YgdGhlIHRva2VuIGFmdGVyIGFjY2VwdGluZyB0aGUgY29ubmVjdGlvbiB0ZW1wb3JhcmlseSB3aXRoIGEgdGltZW91dCBvZiAycwoKIyBBdXRoIHNldHRpbmdzCiMgLSBUaGlzIGFycmF5IGNvbnRhaW5zIGFsbCBhbGxvd2VkIFdlYlNvY2tldHMgS2V5cwp0b2tlbnM6CiAgLSBteVRlc3RUb2tlbjEKICAtIG15VGVzdFRva2UyCiMgUGVybWlzc2lvbnMgc2V0dGluZ3MKIyAtIFRoaXMgYXJyYXkgY29udGFpbnMgYWxsIHBlcm1pc3Npb25zIGZvciBldmVyeSB0b2tlbi4KIyAtIFVzZSAqIHRvIGFsbG93IGFsbCBwZXJtaXNzaW9ucwojIC0gUGVybWlzc2lvbnMgaXMgbGlrZTogW3BhcnRdLltuYW1lXSwgZm9yIGV4YW1wbGUgcGxheWVyLm5hbWUKIyAtIEFzIHRoZSBnbG9iYWwgcGVybWlzc2lvbiB5b3UgY2FuIHVzZSBwbGF5ZXIuKiB0byBnaXZlIGFjY2VzcyB0byB0aGUgZW50aXJlIHBsYXllciBjbGFzcwojIC0gQ29tbWFuZCBhbmQgcGxheWVyIGV4ZWN1dGlvbiBpcyB1bmRlciAiZXhlYy5bcGxheWVyfHNlcnZlcl0iCiMgLSBQZXJtaXNzaW9uIGZvciByZWNlaXZlIGV2ZW50OiAiZXZlbnQuW2V2ZW50X25hbWVdIgpwZXJtaXNzaW9uczoKICBteVRlc3RUb2tlbjE6CiAgICAtICcqJwogIG15VGVzdFRva2VuMjoKICAgIC0gcGxheWVyLioKICAgIC0gc2VydmVyLnBsYXllckxpc3QKICAgIC0gZXhlYy5zZXJ2ZXIKICAgIAojIEV2ZW50IHNldHRpbmdzCmV2ZW50LXNvY2tldC10b2tlbjogbXlFdmVudFRlc3RUb2tlbiAgIyBUaGlzIHRva2Ugd2lsbCBiZSB1c2VkIGJ5IHRoZSBldmVudCB3ZWJzb2NrZXQgY2xpZW50IHRvIGNvbm5lY3QuIElsIHdpbGwgaGF2ZSAqIGFzIHBlcm1pc3Npb24KZXZlbnQtY2xvc2UtdG9rZW46IG15Q2xvc2VUb2tlbiAgICAgICAgICMgVGhpcyB0b2tlbiB3aWxsIGJlIHVzZWQgYnkgdGhlIHNlcnZlciBtYW5hZ2VyIHRvIHByb21wdCBhIHNodXRkb3duIGNvbW1hbmQgdG8gYWxsIGNvbm5lY3RlZCBjbGllbnRzCgojIFV0aWxzCndhaXRpbmctY29ubmVjdGlvbi10aW1lOiAxICMgVGhlIHRpbWUgdGhlIHNlcnZlciB3YWl0cyBiZWZvcmUgZXN0YWJsaXNoaW5nIGludGVybmFsIFdlYlNvY2tldCBjb25uZWN0aW9ucwpmdWxsX2xvZ3M6IGZhbHNlICAgICAgICAgICAgICAgICAgICMgU2hvdWxkIHRoZSBwbHVnaW4gc2hhcmUgdGhlIFdTUyBzZXJ2ZXIgbG9ncyB3aXRoIHRoZSBjb25zb2xlPwoKIyBFbmFibGUgb3IgZGlzYWJsZSBzb21lIGV2ZW50IGxpc3RlbmVycwpvbl9wbGF5ZXJfbW92ZTogZmFsc2UKb25fYmxvY2tfdXBkYXRlOiBmYWxzZQouLi4=";
    public Language $language;
    public $socketID;
    public $commander;
    public $pid;
    public $data_location;
    public bool $server_status;
    public $permissions;

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
        $this->data_location = $this->getDataFolder();
        @mkdir($this->getDataFolder());
        // Mem based cache - create dir
        @mkdir($this->getDataFolder() . '/.cache/');
        @mkdir($this->getDataFolder() . '/.cache/.actions/');
        @mkdir($this->getDataFolder() . '/.cache/.responses/');
        if (!file_exists($this->getDataFolder() . "config.yml")) {
            file_put_contents($this->getDataFolder() . "config.yml", base64_decode($this->defaultConfig));
        }
        //$this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);

        // Set event key here
        $this->event_key = $this->config->get('event-socket-token', null);

		$this->getLogger()->info(TextFormat::DARK_GREEN . "Plugin enabled!");

        // Creating the permission class
        $this->permissions = new PermissionManager();

        // Created shared memory space
        $this->websocket_key = rand(1000, 100000);
        $shared = shmop_open($this->websocket_key, "c", 0770, 5076);
        shmop_write($shared, 'awaitingForData', 0);

        // Creating the WebSocket server
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->server_status = true;
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $this->config->get('server-ip', 'localhost'), $this->config->get('server-port', 1991));

        // Now fork the socket client part to handle multiple client and accept connection asyncronoulsy
        $this->server = new SocketServerRunner($this->server, $this->getLogger(), $this->config, $this->permissions, $this->getDataFolder());
        $this->server->directive = $this->config->get('server-ip', 'localhost') . ":" . $this->config->get('server-port', 1991);

        $pid = pcntl_fork();
        if ($pid == -1) {
            die("Can't fork!");
        } elseif ($pid) {
            $this->pid = $pid;
            usleep((int)($this->config->get('waiting-connection-time', 1) * 1000));
            $this->getLogger()->info(TextFormat::GRAY . "Loading WSS Server on " . $this->config->get('server-ip', 'localhost') . ":" . $this->config->get('server-port', 1991) . " ...");

            // Create the heartbeat socket client
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($this->socket, $this->config->get('server-ip', 'localhost'), $this->config->get('server-port', 1991));
            $this->socket = new SocketClient($this->socket, $this->getLogger(), $this->config);

            // Add life support with chunk for not lose the internal ws connection
            $this->getScheduler()->scheduleRepeatingTask(new HeartbeatClass($this, $this->socket), 8*20);

            // Add the tick manager
            $this->getScheduler()->scheduleRepeatingTask(new ActionHeartbeat($this), 2);

            // Register all events here!
            $this->getServer()->getPluginManager()->registerEvents($this, $this);

            // Needs to send a CLEAR message to the WS Server because we want the role!
            $this->socket->clearSend('helloworld_callbackrole_skipconnection_preventingfall');
        } else {
            $this->server->run();
            return;
        }
	}

	public function onDisable() : void{
        $this->socket->clearSend('close');
        $this->socket->close();

        // Close the WS Server via a super duper function
        $sk = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($sk, $this->config->get('server-ip', 'localhost'), $this->config->get('server-port', 1991));
        $sk = new SocketClient($sk, $this->getLogger(), $this->config);
        $sk->clearSendWithResponse($this->config->get('event-close-token', null));
        shell_exec("kill -9 {$this->pid}");
        $sk->close();
	}

    public function onPlayerQuit(PlayerQuitEvent $e) : void {
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_quit',
            'eventClass' => 'playerQuit',
            'data' => [
                'player' => $e->getPlayer()->getName()
            ]
        ]);
    }

    public function onPlayerMove(PlayerMoveEvent $e) : void {
            // Player join event
            if (!$this->config->get('on_player_move', false)) {
                return;
            }
            $player = Fetch::player($e->getPlayer());
            $this->connectSocketAndSendData([
                'event' => true,
                'eventName' => 'player_move',
                'eventClass' => 'playerMove',
                'data' => [
                    'player' => $player
                ]
            ]);
    }

    // COMPLEX DAMAGE EVENT MANAGER
    public function onDamage(\pocketmine\event\entity\EntityDamageEvent $event) {
        $damage = $event->getFinalDamage();
        if($event instanceof \pocketmine\event\entity\EntityDamageByEntityEvent) {
            // ENTITY DAMAGED BY ENTITY
            $victim = $event->getEntity();
            if($victim instanceof \pocketmine\Player) {
                $to = Fetch::player($victim);
                // PLAYER DAMAGED
                $attacker = $event->getDamager();
                // Do stuff here
                if($attacker instanceof \pocketmine\Player) {
                    $from = Fetch::player($attacker);
                } else {
                    $from = Fetch::entity($attacker);
                }
                $this->connectSocketAndSendData([
                    'event' => true,
                    'eventName' => 'player_hit',
                    'eventClass' => 'playerHit',
                    'data' => [
                        'victim' => $to,
                        'attacker' => $from,
                        'damage' => $damage
                    ]
                ]);
            } else {
                $to = Fetch::entity($victim);

                $attacker = $event->getDamager();
                if($attacker instanceof \pocketmine\Player) {
                    $from = Fetch::player($attacker);
                } else {
                    $from = Fetch::entity($attacker);
                }
                $this->connectSocketAndSendData([
                    'event' => true,
                    'eventName' => 'entity_hit',
                    'eventClass' => 'entityHit',
                    'data' => [
                        'victim' => $to,
                        'attacker' => $from,
                        'damage' => $damage
                    ]
                ]);
            }
        } else {
            // SIMPLE ENTITY DAMAGE
            $entity = $event->getEntity();
            if($entity instanceof \pocketmine\Player) {
                $eventName = 'player_hurt';
                $eventClass = 'playerHurt';
                $entity = Fetch::player($entity);
            } else {
                $entity = Fetch::entity($entity);
                $eventName = 'entity_hurt';
                $eventClass = 'entityHurt';
            }
            $this->connectSocketAndSendData([
                'event' => true,
                'eventName' => $eventName,
                'eventClass' => $eventClass,
                'data' => [
                    'victim' => $entity,
                    'damage' => $damage
                ]
            ]);
        }
    }

    public function onPlayerItemUse(\pocketmine\event\player\PlayerItemUseEvent $e) : void {
        $player = Fetch::player($e->getPlayer());
        $item = Fetch::item($e->getItem());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_item_use',
            'eventClass' => 'playerItemUse',
            'data' => [
                'player' => $player,
                'item' => $item
            ]
        ]);
    }

    public function onBlockBreak(\pocketmine\event\block\BlockBreakEvent $e) : void {
        $block = Fetch::block($e->getBlock());
        $item = Fetch::item($e->getItem());
        $player = Fetch::player($e->getPlayer());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'block_break',
            'eventClass' => 'blockBreak',
            'data' => [
                'player' => $player,
                'item' => $item,
                'block' => $block
            ]
        ]);
    }

    public function onBlockPlace(\pocketmine\event\block\BlockPlaceEvent $e) {
        $block = Fetch::block($e->getBlock());
        $item = Fetch::item($e->getItem());
        $player = Fetch::player($e->getPlayer());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'block_place',
            'eventClass' => 'blockPlace',
            'data' => [
                'player' => $player,
                'item' => $item,
                'block' => $block
            ]
        ]);
    }

    public function onBlockUpdate(\pocketmine\event\block\BlockUpdateEvent $e) {
        if (!$this->config->get('on_block_update', false)) {
            return;
        }
        $block = Fetch::block($e->getBlock());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'block_update',
            'eventClass' => 'blockUpdate',
            'data' => [
                'block' => $block
            ]
        ]);
    }
    
    public function onPlayerJoin(PlayerJoinEvent $e) : void {
        // Player join event
        $player = Fetch::player($e->getPlayer());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_join',
            'eventClass' => 'playerJoin',
            'data' => [
                'player' => $player
            ]
        ]);
    }

    public function onPlayerLogin(PlayerLoginEvent $e) : void {
        // Player join event
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_login',
            'eventClass' => 'playerLogin',
            'data' => [
                'player' => $e->getPlayer()->getName()
            ]
        ]);
    }

    public function onBedEnter(\pocketmine\event\player\PlayerBedEnterEvent $e) : void {
        $player = Fetch::player($e->getPlayer());
        $bed = Fetch::block($e->getBed());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_bed_enter',
            'eventClass' => 'playerBedEnter',
            'data' => [
                'player' => $player,
                'bed' => $bed
            ]
        ]);
    }

    public function onBedLeave(\pocketmine\event\player\PlayerBedLeaveEvent $e) : void {
        $player = Fetch::player($e->getPlayer());
        $bed = Fetch::block($e->getBed());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_bed_leave',
            'eventClass' => 'playerBedLeave',
            'data' => [
                'player' => $player,
                'bed' => $bed
            ]
        ]);
    }

    public function onBlockPick(\pocketmine\event\player\PlayerBlockPickEvent $e) : void {
        $player = Fetch::player($e->getPlayer());
        $block = Fetch::block($e->getBlock());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_block_pick',
            'eventClass' => 'playerBlockPick',
            'data' => [
                'player' => $player,
                'block' => $bed
            ]
        ]);
    }

    public function onChat(\pocketmine\event\player\PlayerChatEvent $e) : void {
        $player = Fetch::player($e->getPlayer());
        $message = $e->getMessage();
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_chat',
            'eventClass' => 'playerChat',
            'data' => [
                'player' => $player,
                'message' => $message
            ]
        ]);
    }

    public function onDropItem(\pocketmine\event\player\PlayerDropItemEvent $e) : void {
        $player = Fetch::player($e->getPlayer());
        $item = Fetch::item($e->getItem());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_drop_item',
            'eventClass' => 'playerDropItem',
            'data' => [
                'player' => $player,
                'item' => $item
            ]
        ]);
    }

    public function onJump(\pocketmine\event\player\PlayerJumpEvent $e) : void {
        $player = Fetch::player($e->getPlayer());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_jump',
            'eventClass' => 'playerJump',
            'data' => [
                'player' => $player,
            ]
        ]);
    }

    public function onKick(\pocketmine\event\player\PlayerKickEvent $e) : void {
        $player = Fetch::player($e->getPlayer());
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_kick',
            'eventClass' => 'playerKick',
            'data' => [
                'player' => $player,
                'message' => $e->getQuitMessage()
            ]
        ]);
    }

    public function onRespawn(\pocketmine\event\player\PlayerRespawnEvent $e) : void {
        $player = Fetch::player($e->getPlayer());
        $respawn = $e->getRespawnPosition();
        $respawn->world = $respawn->world->getFolderName();
        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_respawn',
            'eventClass' => 'playerRespawn',
            'data' => [
                'player' => $player,
                'respawn' => $respawn
            ]
        ]);
    }

    public function onDeath(\pocketmine\event\player\PlayerDeathEvent $e) : void {
        $player = Fetch::player($e->getPlayer());
        $message = $e->getDeathMessage();
        $keep = $e->getKeepInventory();
        $xp = $e->getXpDropAmount();
        $drops = $e->getDrops();
        $realDrops = [];
        foreach($drops as $drop) {
            $realDrops[] = Fetch::item($drop);
        }

        $this->connectSocketAndSendData([
            'event' => true,
            'eventName' => 'player_death',
            'eventClass' => 'playerDeath',
            'data' => [
                'player' => $player,
                'message' => $message,
                'keepInventory' => $keep,
                'xp' => $xp,
                'drop' => $realDrops
            ]
        ]);
    }

    public function connectSocketAndSendData(array|object $data) : bool {
        $sk = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($sk, $this->config->get('server-ip', 'localhost'), $this->config->get('server-port', 1991));
        //socket_connect($sk, 'localhost', 1991);
        $sk = new SocketClient($sk, $this->getLogger(), $this->config);
        $answer = $sk->clearSendWithResponse($this->event_key);
        // Remove skin data
        /*
        if (@$data['data']->skin->data !== null) {
            unset($data['data']->skin->data);
        }
        */
        $data = json_encode($data);
        if ($data === false) {
            $this->getLogger()->info(TextFormat::RED . "[EventManager] Sent wrong JSON!");
            return false;
        }

        if ($answer == 'listening.PLEASE_JSON_PROVIDE.EXCLAMATION') {
            // Send the answer
            $sk->clearSend($data);
        } else {
            return false;
        }
        $sk->close();
        return true;
    }
}