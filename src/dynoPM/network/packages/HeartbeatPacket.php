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

namespace dynoPM\network\packages;

use pocketmine\network\mcpe\NetworkSession;

class HeartbeatPacket extends DynoDataPacket
{
    public const NETWORK_ID = DynoInfo::HEARTBEAT_PACKET;

    /** @var float */
    public $tps;
    /** @var float */
    public $load;
    /** @var int */
    public $upTime;

    public function handle(NetworkSession $session): bool
    {
        return false;
    }

    protected function encodePayload()
    {
        $this->putFloat($this->tps);
        $this->putFloat($this->load);
        $this->putLong($this->upTime);
    }

    protected function decodePayload()
    {
        $this->tps = $this->getFloat();
        $this->load = $this->getFloat();
        $this->upTime = $this->getLong();
    }
}