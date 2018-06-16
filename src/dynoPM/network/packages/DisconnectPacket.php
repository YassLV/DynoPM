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
 * @author Y&SS-MineBuilderFR
 */

declare(strict_types=1);

namespace dynoPM\network\packages;

use pocketmine\network\mcpe\NetworkSession;

class DisconnectPacket extends DynoDataPacket
{
    public const NETWORK_ID = DynoInfo::DISCONNECT_PACKET;
    public const TYPE_WRONG_PROTOCOL = 0;
    public const TYPE_GENERIC = 1;

    /** @var int */
    public $type;
    /** @var string */
    public $message;

    public function handle(NetworkSession $session): bool
    {
        return false;
    }

    protected function encodePayload()
    {
        $this->putByte($this->type);
        $this->putString($this->message);
    }

    protected function decodePayload()
    {
        $this->type = $this->getByte();
        $this->message = $this->getString();
    }
}