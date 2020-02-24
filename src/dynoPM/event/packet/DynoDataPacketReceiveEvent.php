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

namespace dynoPM\event\packet;

use dynoPM\Dyno;
use pocketmine\event\Cancellable;
use pocketmine\network\mcpe\protocol\DataPacket;

class DynoDataPacketReceiveEvent extends PacketDynoEvent implements Cancellable
{

    /** @var Dyno */
    private $dyno;
    /** @var DataPacket */
    private $packet;

    /**
     * DynoDataPacketSendEvent constructor.
     * @param Dyno $dyno
     * @param DataPacket $packet
     */
    public function __construct(Dyno $dyno, DataPacket $packet)
    {
        $this->dyno = $dyno;
        $this->packet = $packet;
    }

    /**
     * @return DataPacket
     */
    public function getPacket(): DataPacket
    {
        return $this->packet;
    }
}