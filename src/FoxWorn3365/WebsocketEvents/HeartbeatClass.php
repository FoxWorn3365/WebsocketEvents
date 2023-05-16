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
use FoxWorn3365\WebsocketEvents\Socket\SocketClient;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\scheduler\Task;

class HeartbeatClass extends Task {
    private $plugin;
    protected SocketClient $socket;
    private array $loadable = [
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
    protected $data_location;

    public function __construct($plugin, SocketClient $socket){
      $this->plugin = $plugin;
      $this->socket = $socket;
      $this->data_location = $plugin->getDataFolder();
     
    }

    public function onRun() : void {
        
        $jsonServer = new \stdClass;
        
        foreach ($this->loadable as $item => $function) {
            $jsonServer->{$item} = @$this->plugin->getServer()->$function() ?? "void";
        }

        $jsonServer->{'players'} = [];
        $players = new \stdClass;

        foreach($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $jsonServer->{'players'}[] = $player->getName();
        }

        $jsonServer->{'ip_bans'} = $jsonServer->{'ip_bans'}->getEntries();
        $jsonServer->{'ops'} = $jsonServer->{'ops'}->getAll(true);

        // Now load players
        // haha no

        file_put_contents($this->data_location . '/.cache/.server_chunk.json', json_encode($jsonServer));

        $this->socket->clearSend('heartbeat');
    }
}