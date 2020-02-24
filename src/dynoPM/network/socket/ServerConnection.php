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

namespace dynoPM\network\socket;

use dynoPM\network\packages\DynoInfo;
use pocketmine\utils\Binary;

class ServerConnection
{
    /** @var string */
    private $receiveBuffer = '';
    /** @var DynoSocket */
    private $socket;
    /** @var string */
    private $ip;
    /** @var int */
    private $port;
    /** @var DynoClient */
    private $server;
    /** @var bool|null */
    private $lastCheck;
    /** @var bool */
    private $connected;

    /**
     * ServerConnection constructor.
     * @param DynoClient $server
     * @param DynoSocket $socket
     */
    public function __construct(DynoClient $server, DynoSocket $socket)
    {
        $this->server = $server;
        $this->socket = $socket;
        @socket_getpeername($this->socket->getSocket(), $address, $port);
        $this->ip = $address;
        $this->port = $port;
        $this->lastCheck = microtime(true);
        $this->connected = true;
        $this->run();
    }

    public function run()
    {
        $this->tickProcessor();
    }

    private function tickProcessor()
    {
        while (!$this->server->isShutdown()) {
            $start = microtime(true);
            $this->tick();
            $time = microtime(true);
            if ($time - $start < 0.01) {
                @time_sleep_until($time + 0.01 - ($time - $start));
            }
        }
        $this->tick();
        $this->socket->close();
    }

    private function tick()
    {
        $this->update();
        if (($packets = $this->readPackets()) !== null) {
            foreach ($packets as $packet) {
                $this->server->pushThreadToMainPacket($packet);
            }
        }
        while (($packet = $this->server->readMainToThreadPacket()) !== null && strlen($packet) !== 0) {
            $this->writePacket($packet);
        }
    }

    public function update()
    {
        if ($this->server->needReconnect and $this->connected) {
            $this->connected = false;
            $this->server->needReconnect = false;
        }
        if ($this->connected) {
            $err = socket_last_error($this->socket->getSocket());
            socket_clear_error($this->socket->getSocket());
            if ($err === 10057 or $err === 10054) {
                $this->server->getLogger()->error('Dyno connection has disconnected unexpectedly');
                $this->connected = false;
                $this->server->setConnected(false);
            } else {
                $data = @socket_read($this->socket->getSocket(), 65535, PHP_BINARY_READ);
                if ($data !== '') {
                    $this->receiveBuffer .= $data;
                }
            }
        } else {
            if ((($time = microtime(true)) - $this->lastCheck) >= 3) {
                $this->server->getLogger()->notice('Trying to re-connect to Dyno Server');
                if ($this->socket->connect()) {
                    $this->connected = true;
                    @socket_getpeername($this->socket->getSocket(), $address, $port);
                    $this->ip = $address;
                    $this->port = $port;
                    $this->server->setConnected(true);
                    $this->server->setNeedAuth(true);
                }
                $this->lastCheck = $time;
            }
        }
    }

    /**
     * @return string[]
     */
    public function readPackets(): array
    {
        $packets = [];
        if ($this->receiveBuffer !== '') {
            $offset = 0;
            $len = strlen($this->receiveBuffer);
            while ($offset < $len) {
                if ($offset > $len - 7) {
                    break;
                }
                $magic = Binary::readShort(substr($this->receiveBuffer, $offset, 2));
                if ($magic !== DynoInfo::PROTOCOL_MAGIC) {
                    throw new \RuntimeException('Magic does not match.');
                }
                $pid = $this->receiveBuffer{$offset + 2};
                $pkLen = Binary::readInt(substr($this->receiveBuffer, $offset + 3, 4));
                $offset += 7;

                if ($pkLen <= ($len - $offset)) {
                    $buf = $pid . substr($this->receiveBuffer, $offset, $pkLen);
                    $offset += $pkLen;

                    $packets[] = $buf;
                } else {
                    $offset -= 7;
                    break;
                }
            }
            if ($offset < $len) {
                $this->receiveBuffer = substr($this->receiveBuffer, $offset);
            } else {
                $this->receiveBuffer = '';
            }
        }

        return $packets;
    }

    public function writePacket($data)
    {
        @socket_write($this->socket->getSocket(), Binary::writeShort(DynoInfo::PROTOCOL_MAGIC));
        @socket_write($this->socket->getSocket(), $data{0});
        @socket_write($this->socket->getSocket(), Binary::writeInt(strlen($data) - 1));
        @socket_write($this->socket->getSocket(), substr($data, 1));
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->ip . ':' . $this->port;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return resource|DynoSocket
     */
    public function getSocket()
    {
        return $this->socket;
    }
}
