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

use dynoPM\network\packages\DynoInfo;
use dynoPM\task\TickPluginsSyncWithDyno;
use pocketmine\plugin\{
    Plugin, PluginBase
};

class DynoPM extends PluginBase
{

    public static $instance = null;
    /** @var Dyno|null */
    private $dyno;
    /** @var Dyno[] */
    private $dynos = [];
    /** @var array */
    private $pluginsSync = [];

    /**
     * @return DynoPM
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @throws \Exception
     */
    public function onEnable()
    {
        $this->saveDefaultConfig();
        $this->reloadConfig();

        if ($this->getConfig()->get('ConfigVersion') != DynoInfo::CONFIG_VERSION) {
            $this->setEnabled(false);
            $this->getLogger()->critical("Your configuration is not up to date!");
            return;
        }

        if (!$this->getConfig()->get('enabled')) {
            $this->setEnabled(false);
            return;
        }

        foreach ($this->getConfig()->get('dynos') as $conf) {
            if ($conf['enabled']) {
                $this->addDyno($this->dyno = new Dyno($this, $conf));
            }
        }

        $this->getScheduler()->scheduleRepeatingTask(new TickPluginsSyncWithDyno($this), 40);
        self::$instance = $this;
    }

    /**
     * @param Dyno $dyno
     */
    public function addDyno(Dyno $dyno)
    {
        $this->dynos[spl_object_hash($dyno)] = $dyno;
    }

    public function onDisable()
    {
        foreach ($this->dynos as $dyno) {
            $dyno->shutdown();
        }
    }

    /**
     * @param Dyno $dyno
     */
    public function removeDyno(Dyno $dyno)
    {
        unset($this->dynos[spl_object_hash($dyno)]);
    }

    /**
     * @param string $description
     * @return Dyno|null
     */
    public function getDynoByDescription(string $description): ?Dyno
    {
        foreach ($this->getDynos() as $dyno) {
            if (($dyno->getDescription() == $description)
                and ($dyno->isVerified())) {
                return $dyno;
            }
        }
        return null;
    }

    /**
     * @return Dyno[]
     */
    public function getDynos(): array
    {
        return $this->dynos;
    }

    /**
     * @param Plugin $plugin
     * @param string $dynoDescription
     */
    public function addPluginSyncWithDynoDescription(Plugin $plugin, string $dynoDescription)
    {
        $this->pluginsSync[$plugin->getName()] = array(
            "dynoDescription" => $dynoDescription,
            "plugin" => $plugin
        );
    }

    /**
     * @param Plugin $plugin
     */
    public function removePluginSyncWithDynoDescription(Plugin $plugin)
    {
        unset($this->pluginsSync[$plugin->getName()]);
    }

    /**
     * @return array
     */
    public function getPluginsSyncWithDynoDescription(): array
    {
        return $this->pluginsSync;
    }
}