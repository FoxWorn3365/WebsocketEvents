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

class CommanderHeartbeat extends Task {
    protected SocketClient $socket;
    protected $logger;

    public function __construct(SocketClient $socket, $logger) {
      $this->socket = $socket;
      $this->logger = $logger;
    }

    public function onRun() : void {
        $this->logger->info(TextFormat::GRAY . "[CustomServer][C] Commander Heartbeat sent!");
        $this->socket->clearSend('heartbeat');
    }
}