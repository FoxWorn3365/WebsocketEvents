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

class HeartbeatClass extends Task {
    private $plugin;
    protected SocketClient $socket;
    private array $loadable;
    protected $data_location;
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

    public function __construct($plugin, SocketClient $socket, array $loadable){
      $this->plugin = $plugin;
      $this->socket = $socket;
      $this->loadable = $loadable;
      $this->data_location = $plugin->getDataFolder();
     
    }

    public function onRun() : void {
        
        $jsonServer = new \stdClass;
        
        foreach ($this->loadable as $item => $function) {
            $jsonServer->{$item} = @$this->plugin->getServer()->$function() ?? "void";
            //var_dump($jsonServer->{$item});
        }

        //var_dump($this->plugin->getServer()->getOnlinePlayers());

        $jsonServer->{'players'} = [];
        $players = new \stdClass;

        foreach($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $jsonServer->{'players'}[] = $player->getName();
            $playerClass = new \stdClass;
            foreach ($this->playerload as $element => $function) {
                $playerClass->{$element} = $player->$function();
            }
            $playerClass->skin = new \stdClass;
            $playerClass->skin->cape = new \stdClass;
            $playerClass->skin->cape->data = $player->getSkin()->getCapeData();
            $playerClass->skin->data = $player->getSkin()->getSkinData();
            $playerClass->skin->id = $player->getSkin()->getSkinId();
            unset($playerClass->location->world);
            unset($playerClass->position->world);
            $playerClass->spawn->world = $playerClass->spawn->world->getFolderName();
            //$playerClass->uuid = @$playerClass->uuid->uuid;
            $playerClass->gamemode = $playerClass->gamemode->getEnglishName();
            $players->{$player->getName()} = $playerClass;
        }

        $jsonServer->{'ip_bans'} = $jsonServer->{'ip_bans'}->getEntries();
        $jsonServer->{'ops'} = $jsonServer->{'ops'}->getAll(true);

        // Now load players

        file_put_contents($this->data_location . '/.cache/.server_chunk.json', json_encode($jsonServer));
        file_put_contents($this->data_location . '/.cache/.players_chunk.json', json_encode($players));

        $this->socket->clearSend('heartbeat');
    }
}