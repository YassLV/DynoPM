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

namespace dynoPM\task;

use dynoPM\Dyno;
use dynoPM\exception\PacketException\InputReceivedException;
use pocketmine\scheduler\Task;

class TickTask extends Task
{
    /** @var Dyno */
    private $dyno;

    /**
     * TickTask constructor.
     * @param Dyno $dyno
     */
    public function __construct(Dyno $dyno)
    {
        $this->dyno = $dyno;
    }

    /**
     * @param int $currentTick
     * @throws InputReceivedException
     */
    public function onRun(int $currentTick)
    {
        $this->dyno->tick();
    }
}