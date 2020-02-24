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
 * @copyright (c) 2018
 * @author Y$SS-YassLV
 */

declare(strict_types=1);

namespace dynoPM\network;

use dynoPM\Dyno;
use dynoPM\event\packet\DynoDataPacketSendEvent;
use dynoPM\exception\PacketException\InputReceivedException;
use dynoPM\network\packages\{
    ConnectPacket, DisconnectPacket, DynoDataPacket, executor\inputPacket, executor\outputPacket, HeartbeatPacket, InformationPacket
};
use dynoPM\network\socket\DynoClient;
use pocketmine\network\mcpe\protocol\{
    PacketPool, UnknownPacket
};

class DynoInterface
{
    /** @var Dyno */
    private $dyno;
    /** @var string */
    private $ip;
    /** @var int */
    private $port;
    /** @var DynoClient */
    private $client;
    /** @var bool */
    private $connected = true;

    /**
     * DynoInterface constructor.
     * @param Dyno $server
     * @param string $ip
     * @param int $port
     * @throws \Exception
     */
    public function __construct(Dyno $server, string $ip, int $port)
    {
        $this->dyno = $server;
        $this->ip = $ip;
        $this->port = $port;
        $this->registerPackets();
        $this->client = new DynoClient($server->getLogger(), $server->getServer()->getLoader(), $port, $ip);
    }

    private function registerPackets()
    {
        PacketPool::registerPacket(new HeartbeatPacket());
        PacketPool::registerPacket(new ConnectPacket());
        PacketPool::registerPacket(new DisconnectPacket());
        PacketPool::registerPacket(new InformationPacket());
        PacketPool::registerPacket(new inputPacket());
        PacketPool::registerPacket(new outputPacket());
    }

    /**
     * @return Dyno
     */
    public function getDyno()
    {
        return $this->dyno;
    }

    public function reconnect()
    {
        $this->client->reconnect();
    }

    public function shutdown()
    {
        $this->client->shutdown();
    }

    /**
     * @param DynoDataPacket $pk
     */
    public function putPacket(DynoDataPacket $pk)
    {
        $this->dyno->getServer()->getPluginManager()->callEvent($evsend = new DynoDataPacketSendEvent($this->dyno, $pk));
        if (!$evsend->isCancelled()) {
            $pk->encode();
            $this->client->pushMainToThreadPacket($pk->buffer);
        }
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * @throws InputReceivedException
     */
    public function process()
    {
        while (($packet = $this->client->readThreadToMainPacket()) !== null && strlen($packet) !== 0) {
            $this->handlePacket($packet);
        }
        $this->connected = $this->client->isConnected();
        if ($this->client->isNeedAuth()) {
            $this->dyno->connect();
            $this->client->setNeedAuth(false);
        }
    }

    /**
     * @param $buffer
     * @throws InputReceivedException
     */
    public function handlePacket($buffer)
    {
        if (!($pk = PacketPool::getPacket($buffer)) instanceof UnknownPacket) {
            if ($pk instanceof DynoDataPacket) {
                $pk->decode();
                $this->dyno->handleDataPacket($pk);
            }
        }
    }
}
