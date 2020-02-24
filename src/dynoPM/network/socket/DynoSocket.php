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

class DynoSocket
{

    private $socket;
    /** @var \ThreadedLogger */
    private $logger;
    /** @var string */
    private $interface;
    /** @var int */
    private $port;

    /**
     * DynoSocket constructor.
     * @param \ThreadedLogger $logger
     * @param int $port
     * @param string $interface
     */
    public function __construct(\ThreadedLogger $logger, int $port = 10305, string $interface = '127.0.0.1')
    {
        $this->logger = $logger;
        $this->interface = $interface;
        $this->port = $port;
        $this->connect();
    }

    public function connect()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false or !@socket_connect($this->socket, $this->interface, $this->port)) {
            $this->logger->critical('Dyno Client can\'t connect ' . $this->interface . ':' . $this->port);
            $this->logger->error('Socket error: ' . socket_strerror(socket_last_error()));

            return false;
        }
        $this->logger->info('Dyno has connected to ' . $this->interface . ':' . $this->port);
        socket_set_nonblock($this->socket);
        socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);

        return true;
    }

    /**
     * @return mixed
     */
    public function getSocket()
    {
        return $this->socket;
    }

    public function close()
    {
        socket_close($this->socket);
    }
}