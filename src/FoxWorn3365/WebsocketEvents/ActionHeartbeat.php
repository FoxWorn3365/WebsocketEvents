<?php

declare(strict_types=1);

namespace FoxWorn3365\WebsocketEvents;

use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\Language;
use pocketmine\player\PlayerDataProvider;
use SocketEvents\SocketClient;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\scheduler\Task;

class ActionHeartbeat extends Task {
    protected Core $plugin;
    protected SocketClient $socket;
    protected $id;

    public function __construct(Core $plugin, SocketClient $socket) {
      $this->plugin = $plugin;
      $this->socket = $socket;
    }

    public function send(string $message) {
        //echo "RESPONDED EVENT {$this->id}\n";
        file_put_contents($this->plugin->getDataFolder() . "/.cache/.responses/{$this->id}", $message);
    }

    public function onRun() : void {
        // Let's see the thing
        /*
        ACTION STRUCTURE
        {
            "class":"action|fetch",
            "type":"player|server",
            "target":"username|null",
            "command":"null|bool",
            "action":"function|command"
            "args":[]
        }
        */
        foreach (glob($this->plugin->getDataFolder() . '/.cache/.actions/*') as $actionToDo) {
            $id = str_replace($this->plugin->getDataFolder() . '/.cache/.actions/', '', $actionToDo);
            $this->id = $id;
            $do = json_decode(file_get_contents($actionToDo));
            @unlink($actionToDo);
            if ($do === false) {
                exit;
                $this->send(json_encode(['status' => 400, 'message' => 'empty to do!']));
                continue;
            }
            if ($do->type == "player") {
                $player = $this->plugin->getServer()->getPlayerExact($do->target);
                if ($do->class == "fetch") {
                    $player = $this->plugin->fetchPlayer($player);
                    $this->send(json_encode(['status' => 200, 'message' => 'completed!', 'type' => $do->class, 'id' => $id, 'data' => $player]));
                } else {
                    $function = $do->action;
                    $arg1 = $args[0] ?? null;
                    $arg2 = $args[1] ?? null;
                    $player->$function($arg1);
                    $this->send(json_encode(['status' => 200, 'message' => 'completed!', 'type' => $do->class, 'id' => $id]));
                }
            } elseif ($do->type == "console") {
                if ($do->command) {
                    // Execute a command
                    $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), $do->action); 
                } else {
                    $function = $do->action;
                    $arg1 = $args[0] ?? null;
                    $arg2 = $args[1] ?? null;
                    $this->plugin->getServer()->$function($arg1);
                }
                $this->send(json_encode(['status' => 200, 'message' => 'completed!', 'id' => $id]));
            } 
        }
    }
}