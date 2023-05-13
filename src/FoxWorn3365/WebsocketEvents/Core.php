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
use SocketEvents\SocketClient;
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
    protected string $defaultConfig = "LS0tCiMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjCiMgXCAgICAgIFwgLyAgICAgIC8gKy0tLS0tLSAgICAgKy0tLS0tICAjCiMgIFwgICAgICBYICAgICAgLyAgfCAgICAgICAgICAgfCAgICAgICAjCiMgICBcICAgIC8gXCAgICAvICAgKy0tLS0tKyAgICAgKy0tLSAgICAjCiMgICAgXCAgLyAgIFwgIC8gICAgICAgICAgfCAgICAgfCAgICAgICAjCiMgICAgIFwvICAgICBcLyAgICAgLS0tLS0tKyAgICAgKy0tLS0tICAjCiMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjCiMgV2ViU29ja2V0IEV2ZW50cyB2MC44QGluZGV2IEJFVEEKIyAiUmVjZWl2ZSwgaGFuZGxlIGFuZCBleGVjdXRlIGFjdGlvbiBmcm9tIGFuZCB0byB5b3VyIFBvY2tldE1pbmUtTVAgc2VydmVyIHZpYSBXZWJTb2NrZXRzISIKIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMKIyAoQykgMjAyMy1ub3cgRmVkZXJpY28gQ29zbWEgKEZveFdvcm4zMzY1KSBhbmQgY29udHJpYnV0b3JzCiMgTUlUIExpY2Vuc2UKIyBSZXF1aXJlIHBocDggb3IgbmV3ZXIKIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMKIyAgICAgICAgQ09OVEFDVFMKIyBFbWFpbDogZm94d29ybjMzNjVAZ21haWwuY29tCiMgRGlzY29yZDogRm94V29ybiMwMDAxCiMgR2l0SHViIGh0dHBzOi8vZ2l0aHViLmNvbS9Gb3hXb3JuMzM2NS9XZWJzb2NrZXRFdmVudHMKIyBHaXRIdWIgKGF1dGhvcik6IGh0dHBzOi8vZ2l0aHViLmNvbS9Gb3hXb3JuMzM2NQojIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMKCmVuYWJsZWQ6IHRydWUgICMjIElzIHRoZSBwbHVnaW4gZW5hYmxlZD8KCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojID4gV2ViU29ja2V0IFNlcnZlciBDb25maWd1cmF0aW9uCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIERlZmF1bHQgc2V0dGluZ3MKc2VydmVyLWlwOiBsb2NhbGhvc3QgICAjIFRoZSBob3N0IG9mIHRoZSBpbnRlcm5hbCBXZWJTb2NrZXQgU2VydmVyLiB1c2UgMC4wLjAuMCB0byBvcGVuIHRvIG90aGVycwpzZXJ2ZXItcG9ydDogMTk5MSAgICAgICMgVGhlIHBvcnQKdGltZW91dDogMiAgICAgICAgICAgICAjIFRpbWVvdXQgKGluIHNlY29uZHMpIG9mIGNsaWVudCBtZXNzYWdlIGxpc3RlbmVyCm1heC1jb25uZWN0aW9uczogMTAgICAgIyBUaGUgbnVtYmVyIG9mIG1heCBzaW11bHRhbmVvdXMgV2ViU29ja2V0IENvbm5lY3Rpb25zIGZvciB0aGlzIHNlcnZlcgoKIyBBdXRoIHNldHRpbmdzCiMgLSBUaGlzIGFycmF5IGNvbnRhaW5zIGFsbCBhbGxvd2VkIFdlYlNvY2tldHMgS2V5cwp0b2tlbnM6CiAgLSBteVRlc3RUb2tlbjEKIAojIFBlcm1pc3Npb25zIHNldHRpbmdzCiMgLSBUaGlzIGFycmF5IGNvbnRhaW5zIGFsbCBwZXJtaXNzaW9ucyBmb3IgZXZlcnkgdG9rZW4uCiMgLSBVc2UgKiB0byBhbGxvdyBhbGwgcGVybWlzc2lvbnMKIyAtIFBlcm1pc3Npb25zIGlzIGxpa2U6IFtwYXJ0XS5bbmFtZV0sIGZvciBleGFtcGxlIHBsYXllci5uYW1lCiMgLSBBcyB0aGUgZ2xvYmFsIHBlcm1pc3Npb24geW91IGNhbiB1c2UgcGxheWVyLiogdG8gZ2l2ZSBhY2Nlc3MgdG8gdGhlIGVudGlyZSBwbGF5ZXIgY2xhc3MKIyAtIENvbW1hbmQgYW5kIHBsYXllciBleGVjdXRpb24gaXMgdW5kZXIgImV4ZWN1dGlvbi5bcGxheWVyfHNlcnZlcl0uW2FjdGlvbl0iCnBlcm1pc3Npb25zOgogIG15VGVzdFRva2VuMToKICAgIC0gJyonCiAgbXlUZXN0VG9rZW4yOgogICAgLSBwbGF5ZXIuKgogICAgLSBzZXJ2ZXIuZXhlY3V0ZQogICAgCiMgRXZlbnQgc2V0dGluZ3MKZXZlbnQtc29ja2V0LXRva2VuOiBteUV2ZW50VGVzdFRva2VuICAjIFRoaXMgdG9rZSB3aWxsIGJlIHVzZWQgYnkgdGhlIGV2ZW50IHdlYnNvY2tldCBjbGllbnQgdG8gY29ubmVjdC4gSWwgd2lsbCBoYXZlICogYXMgcGVybWlzc2lvbgoKIyBVdGlscwp3YWl0aW5nLWNvbm5lY3Rpb24tdGltZTogMSAjIFRoZSB0aW1lIHRoZSBzZXJ2ZXIgd2FpdHMgYmVmb3JlIGVzdGFibGlzaGluZyBpbnRlcm5hbCBXZWJTb2NrZXQgY29ubmVjdGlvbnMKZnVsbF9sb2dzOiB0cnVlICAgICAgICAgICAgICAgICAgICMgU2hvdWxkIHRoZSBwbHVnaW4gc2hhcmUgdGhlIFdTUyBzZXJ2ZXIgbG9ncyB3aXRoIHRoZSBjb25zb2xlPwoKIyBFbmFibGUgb3IgZGlzYWJsZSBzb21lIGV2ZW50IGxpc3RlbmVycwpvbl9wbGF5ZXJfbW92ZTogZmFsc2UKb25fYmxvY2tfdXBkYXRlOiBmYWxzZQouLi4=";
    public Language $language;
    public $socketID;
    public $commander;
    public $data_location;
    public bool $server_status;
    public $permissions;
    public array $storable = [
        'data_path' => 'getDataPath',
        'difficulty' => 'getDifficulty',
        'file_path' => 'getFilePath',
        'force_gamemode' => 'getForceGamemode',
        //'gamemode' => 'getGamemode',
        'ip' => 'getIp',
        'ip_bans' => 'getIPBans',
        'ipv6' => 'getIpV6',
        'max_players' => 'getMaxPlayers',
        'motd' => 'getMotd',
        'name' => 'getName',
        'ops' => 'getOps',
        //'players' => 'getOnlinePlayers',
        'online_mode' => 'getOnlineMode',
        'pocketmine_version' => 'getPocketMineVersion',
        'port' => 'getPort',
        'tick' => 'getTick',
        'tps' => 'getTicksPerSecond',
        'version' => 'getVersion',
        'hardcore' => 'isHardcore'
    ];
    protected array $playerload = [
        'online' => 'isConnected',
        'display_name' => 'getDisplayName',
        'gamemode' => 'getGamemode',
        'healt' => 'getHealth',
        'id' => 'getId',
        'last_played' => 'getLastPlayed',
        'location' => 'getLocation',
        'max_healt' => 'getMaxHealth',
        'name' => 'getName',
        'name_tag' => 'getNameTag',
        'position' => 'getPosition',
        //'skin' => 'getSkin',
        'spawn' => 'getSpawn',
        'uuid' => 'getUniqueId',
        'viewers' => 'getViewers',
        'world' => 'getWorld'
    ];

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
        // Now fork the socket client part to handle multiple client
        //$pid = pcntl_fork();

        // Now async task

        $socket = $this->server;
        $logger = $this->getLogger();
        $config = $this->config;
        $permissions = $this->permissions;
        $globalDir = $this->getDataFolder();
        $makefunction = function() use ($socket, $logger, $config, $permissions, $shared, $globalDir) {
            // System config
            if (!$config->get('full_logs', true)) {
                $logger = new \stdClass;
                $logger->info = function() {
                    return;
                };
            }
            socket_listen($socket);
            $GLOBAL_SERVER = null; // This var is dedicated to listen of simple server
            $GLOBAL_SERVER_CLIENT = null;
            $GLOBAL_CALLBACK_CLIENT = null;
            $GLOBAL_CALLBACK = null;
            $logger->info(TextFormat::GRAY . "[CustomServer][] WSS CustomServer1 started!");
            $server_status = true;
    
            // Collecting users via websocket
            $clientList = [];

            // Create a global memory-based storage to save every process
    
            while ($server_status) {
                $logger->info(TextFormat::DARK_AQUA . "[CustomServer][LOOP] Awaiting connections...");
                if (!($socket instanceof \Socket)) {
                    $server_status = false;
                    break;
                }
                $client = @socket_accept($socket);
                var_dump($client);
                // Set timeout to answer
                /*
                if (gettype($client) == 'boolean') {
                    $count = 0;
                    foreach ($clientList as $clientSession) {
                        $clientSession->send('close');
                        $clientSession->close();
                        unset($clientList[$count]);
                        $count++;
                    }
                    // Kill server
                    @socket_shutdown($socket);
                    $server_status = false;
                    break;
                }
                */
                socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
                $logger->info(TextFormat::DARK_AQUA . "[CustomServer][LOOP] Accepted connection, elaborating it...");
                if ($client == false || $client == null) {
                    $logger->info(TextFormat::RED . "[CustomServer][LOOP] Client is false, closing gate...");
                    $server_status = false;
                    //exec("kill -9 {$pid}");
                    break;
                }
                // Accept connection
                $request = socket_read($client, 7500);
                var_dump($request);
                if (empty($request)) {
                    $logger->info(TextFormat::GOLD . "[CustomServer][LOOP] Client timeout-ed, restarting loop...");
                    continue;
                }
                socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => 0));
                if (strpos($request, 'Type: simple-connection')) {
                    $client = new SocketClient($client, $logger, false);
                } else {
                    $client = new SocketClient($client, $logger, true);
                }
                $client->allowed = $config->get('tokens', []);
                $logger->info(TextFormat::GRAY . "[CustomServer][] New connection to server by Client {$client->id} v13 with message: {$request}");
                if ($request === 'helloworld_callbackrole_skipconnection_preventingfall') {
                    $logger->info(TextFormat::DARK_GREEN . "[CustomServer][] Recognized the role 'ServerConsole.Callback' to Client {$client->id}");
                    $client->clearAccept();
                    $GLOBAL_CALLBACK = $client->id;
                    $GLOBAL_CALLBACK_CLIENT = $client;
                } elseif ($request === 'helloworld_consolerole_skipconnection_preventingfall') {
                    $client->clearAccept();
                    $logger->info(TextFormat::DARK_GREEN . "[CustomServer][] Recognized the role 'ServerConsole.Server' to Client {$client->id}");
                    $GLOBAL_SERVER = $client->id;
                    $GLOBAL_SERVER_CLIENT = $client;
                    //var_dump($client->getIP());
                    continue;
                    // Commander doesn't need to be listened so
                    //continue; but now they have to
                } elseif ($request !== null && $request === $config->get('event-socket-token', null)) {
                    $message = json_decode($client->clearSendWithResponse('listening.PLEASE_JSON_PROVIDE.EXCLAMATION', 9024));
                    $goods = [];
                    //var_dump('LISTS', $clientList);
                    $count = 0;
                    foreach ($clientList as $clientSession) {
                        $status = @$clientSession->send(json_encode(['status' => 201, 'type' => 'event', 'event' => true, 'data' => $message]));
                       // var_dump($status);
                        if (!$status) {
                            $clientSession->close();
                            $clientList[$count] = null;
                            $logger->info(TextFormat::DARK_RED . "[CustomServer][Events] Can't send events to Client {$clientSession->id}, it will be disconnected...");
                        }
                        $count++;
                    }
                    $client->close();
                    continue;
                } elseif ($request !== null && $request === $config->get('event-close-token', null)) {
                    // Close the websocket and all sockets
                    $count = 0;
                    foreach ($clientList as $clientSession) {
                        $clientSession->send('close');
                        $clientSession->close();
                        unset($clientList[$count]);
                        $count++;
                    }
                    // Kill server
                    socket_shutdown($socket);
                    $server_status = false;
                    break;
                } else {
                    if (count($clientList) >= $config->get('max-connections', 10)) {
                        continue;
                    } else {
                        $remainings = $config->get('max-connections', 10) - count($clientList);
                        $logger->info(TextFormat::YELLOW . "{$remainings} WSS Client slot(s) remaining.");
                    }
                    if (!$client->accept($request)) {
                        continue;
                    }
                    $client->dispatchPermissions($config->get('permissions', []), $permissions);
                    $client->send(json_encode(['status' => 202, 'message' => 'connected', 'connected' => true, 'id' => $client->id]));
                    //var_dump($client->getIP());
                    $clientList[] = $client;
                    //var_dump('SHARED:', $clientList);
                }

                // Sync clients with shmop
                /*
                $data = shmop_read($shared, 0, 0);
                $clients = @explode('_', $data);
                if (count($clients) > 1) {
                    var_dump($data);
                    $clients = explode('_', $data);
                    foreach ($clientList as $clientMeta) {
                        if (!in_array($clientMeta->id, $clients)) {
                            $logger->info(TextFormat::GRAY . "[CustomServer][] Client {$clientMeta->id} disconnected from the WSS due to inactivity...");
                        }
                    }
                }
                */

                //var_dump($clientList);
    
                $pog = pcntl_fork();
                if ($pog) {
                    $logger->info(TextFormat::GRAY . "[CustomServer][] Connection received, restarting WSS listen...");
                    $client->pid = $pog;
                    continue;
                } else {
                    $connection = true;
                    $logger->info(TextFormat::GRAY . "[CustomServer][] Client {$client->id} conected");
                    // Client management - Main fork and listen activated
                    while ($connection) {
                        if ($client == null) {
                            $connection = false;
                            break;
                        }
                        if ($client->id == $GLOBAL_SERVER || $client->id == $GLOBAL_CALLBACK) {
                            $message = $client->clearRead(20048);
                        } else {
                            $message = @$client->read(20048);
                        }
                        if ($message == false) {
                            $logger->info(TextFormat::GRAY . "[CustomServer][] Client {$client->id} Disconnected!");
                            $connection = false;
                            $client->close();
                            break;
                            //exec("kill -9 {$pog}");
                        }
                        $logger->info(TextFormat::GRAY . "[CustomServer][] SocketMessage from Client {$client->id}: {$message}");
                        // Received a message, elaborate this!
                        if ($message == 'hello world') {
                            /*
                            $response = 'Hello world v1.2 - SocketStream!';
                            socket_write($client, $response, strlen($response));
                            */
                            $client->send('Hello world');
                            continue;
                        } elseif ($message == 'heartbeat') {
                            $logger->info(TextFormat::GRAY . "[CustomServer][] Heartbeat received!");
                            $client->send('heartbeat received');
                            $GLOBAL_SERVER_CLIENT->clearSend('keepalie');
                            continue;
                        } elseif ($message == 'close') {
                            $client->close();
                            $connected = false;
                            //exec("kill -9 {$client->pid}");
                            unset($client);
                            continue;
                            /*
                            $response = 'Closing client session...';
                            socket_write($client, $response, strlen($response));
                            socket_close($client);
                            */
                        } elseif ($message == 'completeClose') {
                            $client->write('closing...');
                            $client->close();
                            foreach ($this->clients as $client_a) {
                                $client_a->close();
                            }
                            $server_status = false;
                            socket_shutdown($socket);
                            $logger->info(TextFormat::GRAY . "[CustomServer][] Closed ALL istances of WebSocket Server - Custom v13.1");
                            $connection = false;
                            //exec("kill -9 {$pid}");
                            //exec("kill -9 {$pog}");
                            break;
                        }
        
                        $data = @json_decode($message);
        
                        if ($data === false || $data === null) {
                            //$response = 'Unknow manager';
                            //socket_write($client, $response, strlen($response));
                            $client->send('Invalid!');
                            $logger->info(TextFormat::GRAY . "[CustomServer][] Client {$client->id} sent an invalid message!");
                            return;
                        }
        
                        // Callback
                        //$client->send('Valid JSON');
                        // Now elaborate the request
                        if (!(!empty($data->action) && !empty($data->fetch))) {
                            $client->send(json_encode(['status' => 400, 'message' => 'Missing argument(s)!', 'data' => json_encode($data)]));
                            return;
                        }
        
                        if ($data->action === "GET") {
                            if ($data->fetch == "server") {
                                $data = json_decode(file_get_contents("{$globalDir}/.cache/.server_chunk.json"));
                                $client->send(json_encode(['status' => 200, 'message' => 'Data retrived!', 'data' => $data->data]));
                                continue;
                            } elseif ($data->fetch == "player") {
                                $cached = $data->cached ?? true;
                                if ($cached) {
                                    $data = json_decode($GLOBAL_SERVER_CLIENT->clearSendWithResponse(json_encode([
                                        'fetch' => 'player',
                                        'action' => 'get',
                                        'request' => $data->target
                                    ])));
                                } else {
                                    $id = rand(100, 10000) . rand(100, 10060);
                                    file_put_contents("{$globalDir}/.cache/.actions/{$id}", json_encode([
                                        'class' => 'fetch',
                                        'type' => 'player',
                                        'target' => $data->target
                                    ]));
                                    sleep(1);
                                    $data = json_decode(file_get_contents("{$globalDir}/.cache/.responses/{$id}"));
                                    @unlink("{$globalDir}/.cache/.responses/{$id}");
                                }
                                $client->send(json_encode(['status' => 200, 'message' => 'Data retrived!', 'data' => $data->data]));
                                continue;
                            }
                        } elseif ($data->action == "EXECUTE") {
                            $id = rand(100, 10000) . rand(10, 10000);
                            $args = $data->args ?? [];
                            if (!(!empty($data->target) && !empty($data->fetch) && !empty($data->action))) {
                                $client->send(json_encode(['status' => 400, 'message' => 'invalid request']));
                                continue;
                            }
                            if ($data->fetch == 'server') {
                                file_put_contents("{$globalDir}/.cache/.actions/{$id}", json_encode([
                                    'class' => 'action',
                                    'type' => 'server',
                                    'target' => null,
                                    'command' => true,
                                    'action' => $data->action,
                                    'args' => $args
                                ]));
                            } elseif ($data->fetch == 'player') {
                                file_put_contents("{$globalDir}/.cache/.actions/{$id}", json_encode([
                                    'class' => 'action',
                                    'type' => 'player',
                                    'target' => $data->target,
                                    'command' => null,
                                    'action' => $data->action,
                                    'args' => $args
                                ]));
                            }
                            sleep(1);
                            $response = json_decode(file_get_contents("{$globalDir}/.cache/.responses/{$id}"));
                            @unlink("{$globalDir}/.cache/.responses/{$id}");
                            $client->send(json_encode(['status' => 200, 'message' => 'Action performed!', 'data' => $response]));
                            continue;
                        }
        
                        $client->send(json_encode(['status' => 404, 'message' => 'Not found!']));
                        /*
                        $response = 'Valid JSON';
                        socket_write($client, $response, strlen($response));
                        */
                    }
                    $logger->info(TextFormat::YELLOW . "[CustomServer][] Client [oldclientid] disconnected from mainLoop()!");
                    // Close process
                    //exec("kill -9 {$pog}");
                    return;  
                }
    
            }
            $logger->info(TextFormat::RED . "[CustomServer][] WebSocket Server stopped!");
            return;
            // Save socket client in the memory
            //apcu_store("{$this->socketID}_pm-socket", $this->socket);
        };

        $pid = pcntl_fork();
        if ($pid == -1) {
            die("Can't fork!");
        } elseif ($pid) {
            usleep((int)($this->config->get('waiting-connection-time', 1) * 1000));
            $this->getLogger()->info(TextFormat::YELLOW . "Loading WSS Server on " . $this->config->get('server-ip', 'localhost') . ":" . $this->config->get('server-port', 1991) . " ...");
            $this->getLogger()->info(TextFormat::DARK_GREEN . "Connection to wss server...");
            // First commander
            $this->commander = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($this->commander, $this->config->get('server-ip', 'localhost'), $this->config->get('server-port', 1991));
            $this->commander = new SocketClient($this->commander, $this->getLogger());
            $this->commander->clearSend('helloworld_consolerole_skipconnection_preventingfall');
            $this->commander->auto();
            //$this->getScheduler()->scheduleRepeatingTask(new CommanderHeartbeat($this->commander, $this->getLogger()), 20*20);
            $this->getScheduler()->scheduleRepeatingTask(new ActionHeartbeat($this, $this->commander), 1);
            $asynclistener = pcntl_fork();
            // Creating commanding socket
            if ($asynclistener == -1) {
                die("Can't fork!");
            } elseif ($asynclistener) {
                $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                socket_connect($this->socket, $this->config->get('server-ip', 'localhost'), $this->config->get('server-port', 1991));
                $this->socket = new SocketClient($this->socket, $this->getLogger());
                // Add life support with chunk for not lose the internal ws connection
                $this->getScheduler()->scheduleRepeatingTask(new HeartbeatClass($this, $this->socket, $this->storable), 8*20);
                //$this->getScheduler()->scheduleRepeatingTask(new SocketLoop($this->socket, $this), 10);
                $this->getServer()->getPluginManager()->registerEvents($this, $this);
                $this->getLogger()->info(TextFormat::BLUE . "[EventManager][] Async eventManager task started!");
                // Needs to send a CLEAR message
                $this->socket->clearSend('helloworld_callbackrole_skipconnection_preventingfall');
                $this->getLogger()->info(TextFormat::AQUA . "[CustomServer][Client:Heartbeat] Heartbeat client connected to websockets!");
            } else {
                // Now ready to listen to requests
                while ($this->commander->connected) {
                    $this->runServer();
                }
                //die("Concluded!");
            }
    
            $this->commander = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($this->commander, $this->config->get('address', 'localhost'), $this->config->get('port', 1991));
            $this->commander = new SocketClient($this->commander, $this->getLogger());
        } else {
            $makefunction();
        }
	}

    public function runServer() : void {
        $message = @json_decode($this->commander->clearRead(10024));
        if ($message === null) {
            return;
        }
        if ($message->fetch == 'server') {
            if ($message->cached) {
                $data = json_decode(file_get_contents($this->getDataFolder() . '/.cache/.server_chunk.json'))->{$message->request};
                @$this->commander->clearSend(json_encode(['status' => 200, 'data' => $data]));
            } else {
                $functionName = $message->request;
                $response = @$this->getServer()->$functionName();
                @$this->commander->clearSend(json_encode(['status' => 200, 'data' => $response]));
            }
        } elseif ($message->fetch == 'player') {
            if ($message->action == 'get') {
                $data = @json_decode(file_get_contents($this->getDataFolder() . '/.cache/.players_chunk.json'))->{$message->request};
                @$this->commander->clearSend(json_encode(['status' => 200, 'data' => $data]));
            } elseif ($message->action == 'do') {
                
            }
        }
    }

	public function onDisable() : void{
        $this->socket->clearSend('completeClose');
        $this->socket->clearRead();
        $this->getLogger()->info(TextFormat::RED . "Shutting down WS Server done, awaiting for confirm...");
        $this->socket->close();
        $this->commander->close();
		$this->getLogger()->info(TextFormat::DARK_RED . "Plugin disabled!");
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
            $player = $this->fetchPlayer($e->getPlayer());
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
                $to = $this->fetchPlayer($victim);
                // PLAYER DAMAGED
                $attacker = $event->getDamager();
                // Do stuff here
                if($attacker instanceof \pocketmine\Player) {
                    $from = $this->fetchPlayer($attacker);
                } else {
                    $from = $this->fetchEntity($attacker);
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
                $to = $this->fetchEntity($victim);

                $attacker = $event->getDamager();
                if($attacker instanceof \pocketmine\Player) {
                    $from = $this->fetchPlayer($attacker);
                } else {
                    $from = $this->fetchEntity($attacker);
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
                $entity = $this->fetchPlayer($entity);
            } else {
                $entity = $this->fetchEntity($entity);
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
        $player = $this->fetchPlayer($e->getPlayer());
        $item = $this->fetchItem($e->getItem());
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
        $block = $this->fetchBlock($e->getBlock());
        $item = $this->fetchItem($e->getItem());
        $player = $this->fetchPlayer($e->getPlayer());
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
        $block = $this->fetchBlock($e->getBlock());
        $item = $this->fetchItem($e->getItem());
        $player = $this->fetchPlayer($e->getPlayer());
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
        $block = $this->fetchBlock($e->getBlock());
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
        $player = $this->fetchPlayer($e->getPlayer());
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
        $player = $this->fetchPlayer($e->getPlayer());
        $bed = $this->fetchBlock($e->getBed());
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
        $player = $this->fetchPlayer($e->getPlayer());
        $bed = $this->fetchBlock($e->getBed());
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
        $player = $this->fetchPlayer($e->getPlayer());
        $block = $this->fetchBlock($e->getBlock());
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
        $player = $this->fetchPlayer($e->getPlayer());
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
        $player = $this->fetchPlayer($e->getPlayer());
        $item = $this->fetchItem($e->getItem());
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
        $player = $this->fetchPlayer($e->getPlayer());
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
        $player = $this->fetchPlayer($e->getPlayer());
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
        $player = $this->fetchPlayer($e->getPlayer());
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
        $player = $this->fetchPlayer($e->getPlayer());
        $message = $e->getDeathMessage();
        $keep = $e->getKeepInventory();
        $xp = $e->getXpDropAmount();
        $drops = $e->getDrops();
        $realDrops = [];
        foreach($drops as $drop) {
            $realDrops[] = $this->fetchItem($drop);
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

    public function fetchEntity(object $entity) : object {
        $newEntity = new \stdClass;
        $newEntity->type = 'entity';
        $captable = [
            'name' => 'getName',
            'healt' => 'getHealth',
            'location' => 'getLocation',
            'max_healt' => 'getMaxHealth',
            'world' => 'getWorld',
            'xp' => 'getXpDropAmount',
            'viewers' => 'getViewers',
            'id' => 'getId'
        ];

        foreach ($captable as $key => $function) {
            $newEntity->{$key} = $entity->$function();
        }

        unset($newEntity->location->world);
        $newEntity->world = $newEntity->world->getFolderName();

        return $newEntity;
    }

    public function fetchItem(object $item) : object {
        $itemClass = new \stdClass;
        $itemClass->type = 'item';
        $gettable = [
            'count' => 'getCount',
            'custom_name' => 'getCustomName',
            'id' => 'getId',
            'max_stack_size' => 'getMaxStackSize',
            'name' => 'getName',
            'name_tag' => 'getNamedTag',
            'vanilla_name' => 'getVanillaName',
            'null' => 'isNull'
        ];
        foreach ($gettable as $item_a => $function) {
            $itemClass->{$item_a} = @$item->$function();
        }
        return $itemClass;
    }

    public function fetchBlock(object $block) : object {
        $takable = [
            'id' => 'getId',
            'max_stack_size' => 'getMaxStackSize',
            'name' => 'getName',
            'position' => 'getPosition',
            'solid' => 'isSolid',
            'transparent' => 'isTransparent',
            'light_level' => 'getLightLevel',
            'placable' => 'canBePlaced',
            'replacable' => 'canBeReplaced'
        ];
        $bed = new \stdClass;
        $bed->type = 'block';
        foreach ($takable as $id => $function) {
            $bed->{$id} = @$block->$function();
        }
        if ($bed->name == 'Bed') {
            $bed->occupied = $block->isOccupied();
        }
        //$bed->position->world = $bed->position->world->getFolderName();
        return $bed;
    }

    public function connectSocketAndSendData(array|object $data) : bool {
        $sk = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($sk, $this->config->get('server-ip', 'localhost'), $this->config->get('server-port', 1991));
        //socket_connect($sk, 'localhost', 1991);
        $sk = new SocketClient($sk, $this->getLogger());
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
            //echo "data sent\n";
        } else {
            //echo "data not sent\n";
            return false;
        }
        $sk->close();
        return true;
    }

    public function fetchPlayer(object $player) : ?object {
        $playerClass = new \stdClass;
        $playerClass->type = 'player';
        // return $playerClass;
        if (gettype($player) != 'object') {
            return null;
        }
        foreach ($this->playerload as $element => $function) {
            $playerClass->{$element} = $player->$function();
        }
        $playerClass->skin = new \stdClass;
        $playerClass->skin->cape = new \stdClass;
        $playerClass->skin->cape->data = $player->getSkin()->getCapeData();
        $playerClass->skin->data = null;
        $playerClass->skin->id = $player->getSkin()->getSkinId();
        unset($playerClass->location->world);
        unset($playerClass->position->world);
        $playerClass->world = $playerClass->world->getFolderName();
        //$playerClass->uuid = @$playerClass->uuid->uuid;
        $playerClass->gamemode = $playerClass->gamemode->getEnglishName();
        return $playerClass;
    }

    /*

    public function onReceive(DataPacketReceiveEvent $event) {
        // Update static server info

        //$this->getLogger()->info(TextFormat::BLUE . "[ChunkDataManager][] Chunk received!");
        $jsonServer = new \stdClass;
        foreach ($this->storable as $item => $function) {
            /*
            $function = $this->server->{$function};
            $data = ($function)();
            
            $jsonServer->{$item} = @$this->getServer()->$function() ?? "void";
        }
        file_put_contents($this->data_location . '/.cache/.server_chunk.json', json_encode($jsonServer));
    }

    */
}