<?php

namespace FoxWorn3365\WebsocketEvents\Socket;

use pocketmine\utils\TextFormat;
use FoxWorn3365\WebsocketEvents\PermissionManager;
use FoxWorn3365\WebsocketEvents\Useful;

class SocketServerRunner {
    protected \Socket $server;
    protected $logger;
    protected $config;
    protected $permissions;
    protected string $dir;
    protected bool $online = false;
    protected ?SocketClient $serverClient = null;
    public string $directive;
    protected object $clients;

    function __construct(\Socket $server, $logger, $config, $permissions, $dir) {
        // Import vars from args
        $this->server = $server;
        $this->logger = $logger;
        $this->config = $config;
        $this->permissions = $permissions;
        $this->dir = $dir;

        // Create objects
        $this->connections = new \stdClass;
        $this->clients = new \stdClass;
    }

    private function setupSockets() : void {
        // Check for the full connection logs
        if (!$this->config->get('full_logs', false)) {
            $this->logger = new USeful();
        }

        // Start socket listening
        socket_listen($this->server);
        $this->logger->info(TextFormat::GRAY . "[CustomServer][] WebSocket server started on {$this->directive}");

        $this->online = true;
        $this->loop();
    }

    protected function loop() : void {
        while ($this->online) {
            $this->logger->info(TextFormat::DARK_AQUA . "[CustomServer][LOOP] Awaiting connections...");
            // Check if the server is an istance of the Socket and not a broken pipe
            if (!($this->server instanceof \Socket)) {
                $this->online = false;
                break;
            }

            // Accept new connections from the server
            $client = socket_accept($this->server);

            // Is the client valid?
            if (gettype($client) == 'boolean' || $client === null) {
                // Disconnect all connected clients
                foreach ($this->clients as $id => $clientConnction) {
                    $clientConnection->send('close');
                    $clientConnection->close();
                    $this->clients->{$id} = null;
                    unset($this->clients->{$id});
                }

                // Kill server if connected
                if ($this->server instanceof \Socket) {
                    socket_shutdown($socket);
                }
                $this->online = false;
                break;
            }
            
            // Set the socket timeout
            socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $this->config->get('timeout', 5), 'usec' => 0));

            // The connection is valid, now let's check if it's allowed
            $this->logger->info(TextFormat::DARK_AQUA . "[CustomServer][LOOP] Accepted connection, elaborating it...");

            // Check if the connection is false
            if ($client === false) {
                // Don't kill the ws server, let's restart the listener
                $this->logger->info(TextFormat::RED . "[CustomServer][LOOP] Client is false, closing gate...");
                continue;
            }

            // 
            // > Accept the connection
            //
            // Read the message. It can be headers or a custom token
            $request = socket_read($client, 7500);

            // If the request is empty restart the listener
            if (empty($request)) {
                $this->logger->info(TextFormat::GOLD . "[CustomServer][LOOP] Client timeout-ed, restarting loop...");
                continue;
            }

            // Remove the timeout of client receive
            socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => 0));

            // Set the timeout of send message, just in case
            socket_set_option($client, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 3, 'usec' => 0));

            // Decide if the connection is complex (AKA the WebSocket class of Js) or simple (AKA the normal socket class)
            if (strpos($request, 'Type: simple-connection')) {
                $client = new SocketClient($client, $this->logger, $this->config, false);
            } else {
                $client = new SocketClient($client, $this->logger, $this->config, true);
            }

            // Save all allowed tokens from the config in the client class, later we'll check
            $client->allowed = $this->config->get('tokens', []);

            // Announce the received connection
            $this->logger->info(TextFormat::GRAY . "[CustomServer][] New connection to server by Client {$client->id} v13 with message: {$request}");

            // The request of the callback. It's safe to use a string because as soon as the ws server starts the client will connect to it
            if ($request === 'helloworld_callbackrole_skipconnection_preventingfall') {
                // First, check if there's already a connected server role
                if ($this->serverClient !== null) {
                    $this->logger->info(TextFormat::GOLD . "[CustomServer][I] Someone (Client {$client->id}) tried to connect as Server Role!");
                    // Remove all this thing and restart the listener
                    unset($client);
                    continue;
                } else {
                    $this->logger->info(TextFormat::DARK_GREEN . "[CustomServer][] Recognized the role 'ServerConsole.CallbackAndMain' to Client {$client->id}");
                    $client->clearAccept();
                    $this->serverClient = $client;
                }
            } elseif ($request !== null && $request === $this->config->get('event-socket-token', null)) {
                // The connection is from a Temp WS Client Event
                
                // First, require the data. This function also wait for a response
                $message = json_decode($client->clearSendWithResponse('listening.PLEASE_JSON_PROVIDE.EXCLAMATION', 10024));

                // Broadcast the response to all client. The "CallbackRole" client is not in this object
                foreach ($this->clients as $id => $clientSession) {
                    // Try to send the event via an "event" => true and a "status" => 201
                    // But first let's check the permissions
                    if (!in_array($message->eventName, @$clientSession->permissions['events'])) {
                        continue;
                    }
                    
                    $status = @$clientSession->send(json_encode(['status' => 201, 'type' => 'event', 'event' => true, 'data' => $message]));

                    // Check the status. If it's false, let's disconnect the client from the ws server
                    if (!$status) {
                        $this->logger->info(TextFormat::DARK_RED . "[CustomServer][Events] Can't send events to Client {$clientSession->id}, it will be disconnected...");
                        $clientSession->close();
                        $this->clients->{$id} = null;
                        unset($this->clients->{$id});
                    }
                }

                // Now the client is useless because the WS Events are one-way!
                $client->close();
                continue;
            } elseif ($request !== null && $request === $this->config->get('event-close-token', null)) {
                // This client is the client who is supposed to shutdown the server.
                // First, we need to stop all clients
                foreach ($this->clients as $id => $clientSession) {
                    $clientSession->send('close');
                    $clientSession->close();
                    // Kill the child PID
                    exec("kill -9 {$clientSession->pid}");
                    // Remove the client from the clients array
                    $this->clients->{$id} = null;
                    unset($this->clients->{$id});
                }

                $client->send('done');

                // Kill server if connected
                //@socket_shutdown($this->server);
                //$this->online = false;
                break;
            } else {
                // Accept the default connection but, first, check if there's an empty slot for this connection
                if ($this->count($this->clients) >= $this->config->get('max-connections', 10)) {
                    // No slot :(
                    continue;
                } else {
                    $remainings = $this->config->get('max-connections', 10) - $this->count($this->clients) - 1;
                    $this->logger->info(TextFormat::YELLOW . "{$remainings} WSS Client slot(s) remaining.");
                }

                // Accept the request. WARNING: the second arg is the "allow-doubleauth". If you want to connect with the WebSocket class of js you must set this to true
                if (!$client->accept($request, $this->config->get('allow-doubleauth', false))) {
                    // F
                    continue;
                }

                // Get the permissions
                $client->dispatchPermissions($this->config->get('permissions', []), $this->permissions);
                $client->send(json_encode(['status' => 202, 'message' => 'connected', 'connected' => true, 'id' => $client->id]));
                
                // Add the client in the clients object
                $this->clients->{$client->id} = $client;
            }

            // Fork the process because for every client we need a different process.
            // Don't worry, i'll save the pids in the clients array
            $pog = pcntl_fork();
            if ($pog) {
                $this->logger->info(TextFormat::GRAY . "[CustomServer][] Connection received, restarting WSS listen...");
                if (@$this->clients->{$client->id} !== null) {
                    // I've used the if because the server connections is listened but not in the array
                    $this->clients->{$client->id}->pid = $pog;
                }
                $client->pid = $pog;
                continue;
            } else {
                $connection = true;
                $this->logger->info(TextFormat::GRAY . "[CustomServer][] Client {$client->id} conected");
                // Client management - Main fork and listen activated
                while ($connection) {
                    // Of course, if the client is null or false we disconnect it
                    if ($client === null || $client === false) {
                        $connection = false;
                        break;
                    }

                    // If the message is from the server client we must read is clearly
                    if ($client->id == $this->serverClient->id) {
                        $message = $client->clearRead(20048);
                    } else {
                        $message = $client->read(20048);
                    }

                    if ($message == false) {
                        $this->logger->info(TextFormat::GRAY . "[CustomServer][] Client {$client->id} Disconnected!");
                        $connection = false;
                        $client->close();
                        break;
                    }
                    $this->logger->info(TextFormat::GRAY . "[CustomServer][] SocketMessage from Client {$client->id}: {$message}");
                    // Received a message, elaborate this!
                    if ($message === 'hello world') {
                            // A simple hello world
                            $client->send('Hello world');
                            continue;
                    } elseif ($message === 'heartbeat+hello world+server_chunk') {
                            // We need to get the server chunk data!
                            $this->logger->info(TextFormat::AQUA . "[CustomServer][] Required server chunk from Client {$client->id}");
                            $data = json_decode(file_get_contents("{$this->dir}/.cache/.server_chunk.json"));
                            $client->send(json_encode(['status' => 200, 'message' => 'Data retrived', 'data' => $data]));
                            continue;
                    } elseif ($message === 'heartbeat') {
                        $this->logger->info(TextFormat::GRAY . "[CustomServer][] Heartbeat received!");
                        $client->send('heartbeat received');
                        continue;
                    } elseif ($message === 'close') {
                        $client->close();
                        $connected = false;
                        unset($client);
                        break;
                    }

                    // Ok, let's decode the complex message!
                    $data = @json_decode($message);

                    var_dump($client->permissions);
                    
                    // Check if the input is valid
                    if ($data === false || $data === null) {
                        $client->send('[INVALID INPUT!]');
                        $this->logger->info(TextFormat::GRAY . "[CustomServer][] Client {$client->id} sent an invalid message!");
                        continue;
                    }
    
                    // Now elaborate the request
                    if (!(!empty($data->action) && !empty($data->fetch))) {
                        $client->send(json_encode(['status' => 400, 'message' => 'Missing argument(s)!', 'data' => json_encode($data)]));
                        return;
                    }
    
                    // Fetch something
                    if ($data->action === "GET") {
                        if ($data->fetch === "server") {
                            // Check permissions for server
                            if (@$client->permissions['server'] !== null || $client->permissions['server'] === []) {
                                $data = json_decode(file_get_contents("{$this->dir}/.cache/.server_chunk.json"));
                                $rest = new \stdClass;
                                foreach ($data->data as $keys => $values) {
                                    if (in_array($keys, $client->permissions['server'])) {
                                        $rest->{$keys} = $values;
                                    }
                                }
                                $client->send(json_encode(['status' => 200, 'message' => 'Data retrived!', 'data' => $rest]));
                                continue;
                            } else {
                                $client->send('400');
                            }
                        } elseif ($data->fetch === "player") {
                            if (@$client->permissions['player'] !== null || $client->permissions['player'] === []) {
                                $cached = $data->cached ?? true;
                                if ($cached) {
                                    $data = @json_decode(file_get_contents("{$this->dir}/.cache/.players_chunk.json"))->{$data->target};
                                } else {
                                    $id = rand(100, 10000) . rand(100, 10060);
                                    file_put_contents("{$this->dir}/.cache/.actions/{$id}", json_encode([
                                        'class' => 'fetch',
                                        'type' => 'player',
                                        'target' => $data->target
                                    ]));
                                    sleep(1);
                                    $data = json_decode(file_get_contents("{$this->dir}/.cache/.responses/{$id}"));
                                    @unlink("{$this->dir}/.cache/.responses/{$id}");
                                }
                                $rest = new \stdClass;
                                foreach ($data->data as $keys => $values) {
                                    if (in_array($keys, $client->permissions['player'])) {
                                        $rest->{$keys} = $values;
                                    }
                                }
                                $client->send(json_encode(['status' => 200, 'message' => 'Data retrived!', 'data' => $rest]));
                                continue;
                            } else {
                                $client->send('400');
                            }
                        }
                    // Execute something
                    } elseif ($data->action === "EXECUTE") {
                        $id = rand(100, 10000) . rand(10, 10000);
                        $args = $data->args ?? [];
                        if (!(!empty($data->target) && !empty($data->fetch) && !empty($data->action))) {
                            $client->send(json_encode(['status' => 400, 'message' => 'invalid request']));
                            continue;
                        }
                        if ($data->fetch === 'server') {
                            if (!$client->permissions['exec']['server']) {
                                $client->send('400');
                                continue;
                            }
                            file_put_contents("{$globalDir}/.cache/.actions/{$id}", json_encode([
                                'class' => 'action',
                                'type' => 'server',
                                'target' => null,
                                'command' => true,
                                'action' => $data->action,
                                'args' => $args
                            ]));
                        } elseif ($data->fetch === 'player') {
                            if (!$client->permissions['exec']['player']) {
                                $client->send('400');
                                continue;
                            }
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
    
                    // Default message if 404
                    $client->send(json_encode(['status' => 404, 'message' => 'Not found!']));
                }
                // Default client message on disconnect
                $this->logger->info(TextFormat::YELLOW . "[CustomServer][] Client [oldclientid] disconnected from mainLoop()!");
                return;  
            }
        }
        $this->logger->info(TextFormat::RED . "[CustomServer][] WebSocket Server stopped!");
        return;
    }

    public function run() : void {
        $this->setupSockets();
    }

    private function count(object $object) : int {
        $count = 0;
        foreach ($object as $ctl) {
            $count++;
        }
        return $count;
    }
}