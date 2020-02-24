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

namespace dynoPM\task;

use dynoPM\dynoPM;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class TickPluginsSyncWithDyno extends Task
{
    /** @var DynoPM */
    private $dynoPM;

    /**
     * TickPluginsSyncWithDyno constructor.
     * @param DynoPM $dynoPM
     */
    public function __construct(DynoPM $dynoPM)
    {
        $this->dynoPM = $dynoPM;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        if (empty(($pluginsSyncWithDynoDesc =
            $this->dynoPM->getPluginsSyncWithDynoDescription()))) return;
        /**
         * @var string $pluginName
         * @var array $ar
         */
        foreach ($pluginsSyncWithDynoDesc
                 as $pluginName => $array) {
            /** @var string $description */
            $description = $array["dynoDescription"];
            /** @var Plugin $plugin */
            $plugin = $array["plugin"];
            if ($this->dynoPM->getDynoByDescription($description) !== null) {
                if (!$plugin->isEnabled()) {
                    $this->dynoPM->getServer()->getPluginManager()->enablePlugin($plugin);
                    $this->dynoPM->getLogger()->info(
                        TextFormat::GREEN . "Plugin " . $pluginName . " enabled : Successful Dyno connection"
                    );
                }
            } else {
                if ($plugin->isEnabled()) {
                    $this->dynoPM->getServer()->getPluginManager()->disablePlugin($plugin);
                    $this->dynoPM->getLogger()->critical(
                        "Plugin " . $pluginName . " disabled : Dyno connection failed. 
                        The plugin will be automatically enable once Dyno will be accessible"
                    );
                }
            }
        }
    }
}