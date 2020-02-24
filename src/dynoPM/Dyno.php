<?php
/**
 * ________
 * ___  __ \____  ______________
 * __  / / /_  / / /_  __ \  __ \
 * _  /_/ /_  /_/ /_  / / / /_/ /
 * /_____/ _\__, / /_/ /_/\____/
 *         /____/
 *
 * This program is free: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is based on PocketMine Software and Synapse.
 *
 * @copyright (c) 2020
 * @author Y$SS-YassLV
 */

declare(strict_types=1);

namespace dynoPM;

use dynoPM\event\packet\{
    DynoDataPacketReceiveEvent, OutputPacketReceivedEvent
};
use dynoPM\exception\PacketException\InputReceivedException;
use dynoPM\network\{
    DynoInterface
};
use dynoPM\network\packages\{
    ConnectPacket, DisconnectPacket, DynoDataPacket, DynoInfo, executor\inputPacket, executor\outputPacket, HeartbeatPacket, InformationPacket
};
use dynoPM\task\TickTask;
use pocketmine\Server;
use pocketmine\utils\MainLogger;

class Dyno
{
    /** @var DynoPM */
    private $owner;
    /** @var TickTask */
    private $task;
    /** @var Server */
    private $server;
    /** @var MainLogger */
    private $logger;
    /** @var string */
    private $serverIp;
    /** @var int */
    private $port;
    /** @var string */
    private $description;
    /** @var bool */
    private $enable = true;
    /** @var string */
    private $password;
    /** @var DynoInterface */
    private $interface;
    /** @var bool */
    private $verified = false;
    /** @var mixed */
    private $lastUpdate;
    /** @var mixed */
    private $lastRecvInfo;
    /** @var array */
    private $clientData = [];
    /** @var int */
    private $connectionTime = PHP_INT_MAX;

    /**
     * Dyno constructor.
     * @param DynoPM $owner
     * @param array $config
     * @throws \Exception
     */
    public function __construct(DynoPM $owner, array $config)
    {
        $this->owner = $owner;
        $this->server = $owner->getServer();
        $this->task = new TickTask($this);
        $this->logger = $this->server->getLogger();

        $this->serverIp = (string)($config['ip'] ?? '127.0.0.1');
        $this->port = (int)($config['port'] ?? 10102);

        $this->description = (string)$config['description'];
        $this->password = (string)$config['password'];
        $this->interface = new DynoInterface($this, $this->serverIp, $this->port);

        $this->lastUpdate = microtime(true);
        $this->lastRecvInfo = microtime(true);

        $this->owner->getScheduler()->scheduleRepeatingTask($this->task, 1);
        $this->connect();
    }

    public function connect()
    {
        $this->getServer()->getLogger()->info("Connecting " . $this->getHash());
        $this->verified = false;
        $pk = new ConnectPacket();
        $pk->password = $this->password;
        $pk->description = $this->description;
        $pk->protocol = DynoInfo::CURRENT_PROTOCOL;
        $this->sendDataPacket($pk);
        $this->connectionTime = microtime(true);
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->serverIp . ':' . $this->port;
    }

    /**
     * @param DynoDataPacket $pk
     * Send Packet To Dyno !
     */
    public function sendDataPacket(DynoDataPacket $pk)
    {
        $this->interface->putPacket($pk);
    }

    /**
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->enable;
    }

    /**
     * @return DynoInterface
     */
    public function getInterface(): DynoInterface
    {
        return $this->interface;
    }

    public function shutdown()
    {
        if ($this->verified) {
            $pk = new DisconnectPacket();
            $pk->type = DisconnectPacket::TYPE_GENERIC;
            $pk->message = 'Server closed';
            $this->sendDataPacket($pk);
            $this->getLogger()->debug('Dyno client has disconnected from Dyno server');
        }
    }

    /**
     * @return \AttachableThreadedLogger|MainLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @throws InputReceivedException
     */
    public function tick()
    {
        $this->interface->process();
        if ((($time = microtime(true)) - $this->lastUpdate) >= 5) {
            $this->lastUpdate = $time;
            $pk = new HeartbeatPacket();
            $pk->tps = $this->server->getTicksPerSecondAverage();
            $pk->load = $this->server->getTickUsageAverage();
            $pk->upTime = (int)(microtime(true) - \pocketmine\START_TIME);
            $this->sendDataPacket($pk);
        }
        if (((($time = microtime(true)) - $this->lastUpdate) >= 30) and $this->interface->isConnected()) {
            $this->interface->reconnect();
        }
        if (microtime(true) - $this->connectionTime >= 15 and !$this->verified) {
            $this->interface->reconnect();
        }
    }

    /**
     * @return string
     */
    public function getServerIp(): string
    {
        return $this->serverIp;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param DynoDataPacket $pk
     * @throws InputReceivedException
     */
    public function handleDataPacket(DynoDataPacket $pk)
    {
        $this->server->getPluginManager()->callEvent(new DynoDataPacketReceiveEvent($this, $pk));
        switch ($pk::NETWORK_ID) {
            case DynoInfo::DISCONNECT_PACKET:
                /** @var DisconnectPacket $pk */
                $this->verified = false;
                switch ($pk->type) {
                    case DisconnectPacket::TYPE_GENERIC:
                        $this->getLogger()->notice('Dyno Client has disconnected due to ' . $pk->message);
                        $this->interface->reconnect();
                        break;
                    case DisconnectPacket::TYPE_WRONG_PROTOCOL:
                        $this->getLogger()->error($pk->message);
                        break;
                }
                break;
            case DynoInfo::INFORMATION_PACKET:
                /** @var InformationPacket $pk */
                switch ($pk->type) {
                    case InformationPacket::TYPE_LOGIN:
                        if ($pk->message === InformationPacket::INFO_LOGIN_SUCCESS) {
                            $this->logger->info('Login success to ' . $this->serverIp . ':' . $this->port);
                            $this->verified = true;
                        } elseif ($pk->message === InformationPacket::INFO_LOGIN_FAILED) {
                            $this->logger->info('Login failed to ' . $this->serverIp . ':' . $this->port);
                        }
                        break;
                    case InformationPacket::TYPE_CLIENT_DATA:
                        $this->clientData = json_decode($pk->message, true)['clientList'];
                        $this->lastRecvInfo = microtime();
                        break;

                }
                break;
            case DynoInfo::INPUT_PACKET:
                /** @var inputPacket $pk */
                throw new InputReceivedException();
                break;
            case DynoInfo::OUTPUT_PACKET:
                /** @var outputPacket $pk */
                $this->server->getPluginManager()->callEvent(new OutputPacketReceivedEvent($this, $pk));
                if ($pk->countErrors > 0) {
                    foreach ($pk->errors as $error) {
                        $this->getLogger()->critical($error);
                    }
                }
                break;
        }
    }

    /**
     * @param string $message
     */
    public function sendPluginMessage(string $message)
    {
        $pk = new InformationPacket();
        $pk->type = InformationPacket::TYPE_PLUGIN_MESSAGE;
        $pk->message = $message;
        $this->sendDataPacket($pk);
    }

    /**
     * @return array
     */
    public function getClientData(): array
    {
        return $this->clientData;
    }

    /**
     * @return DynoInterface
     */
    public function getDynoInterface(): DynoInterface
    {
        return $this->interface;
    }

    /**
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }
}