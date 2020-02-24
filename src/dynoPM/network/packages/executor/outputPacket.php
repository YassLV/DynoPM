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

namespace dynoPM\network\packages\executor;

use dynoPM\network\packages\{
    DynoDataPacket, DynoInfo
};
use pocketmine\network\mcpe\NetworkSession;

class outputPacket extends DynoDataPacket
{
    public const NETWORK_ID = DynoInfo::OUTPUT_PACKET;

    /** @var string */
    public $tunnelKey;
    /** @var string[] */
    public $errors;
    /** @var int */
    public $countErrors;
    /** @var string[] */
    public $logs;
    /** @var array|object */
    public $getters;
    /** @var int */
    public $getterType;
    /** @var string */
    public $pluginClass;
    /** @var string */
    public $baseInput;
    /** @var array */
    public $want;

    public function handle(NetworkSession $session): bool
    {
        return false;
    }

    protected function encodePayload()
    {
        $this->putString($this->tunnelKey);
        $this->putString(json_encode($this->errors));
        $this->putInt($this->countErrors);
        $this->putString(json_encode($this->logs));
        $this->putString(json_encode($this->getters));
        $this->putString($this->pluginClass);
        $this->putString($this->baseInput);
        $this->putString(json_encode($this->want));
        $this->putInt($this->getterType);
    }

    protected function decodePayload()
    {
        $this->tunnelKey = $this->getString();
        $this->errors = json_decode($this->getString(), true);
        $this->countErrors = $this->getInt();
        $this->logs = json_decode($this->getString(), true);
        $this->getters = $this->getterType ?
            (object)json_decode($this->getString(), true) :
            json_decode($this->getString(), true);
        $this->pluginClass = $this->getString();
        $this->baseInput = $this->getString();
        $this->want = json_decode($this->getString(), true);
        $this->getterType = $this->getInt();
    }
}