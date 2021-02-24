<?php

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection SpellCheckingInspection
 */

declare(strict_types=1);

namespace kim\present\flashlight;

use kim\present\flashlight\task\GrowingTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

use function spl_object_hash;

class Loader extends PluginBase implements Listener{
    /** @var GrowingTask[] */
    private array $tasks = [];

    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function registerTask(Player $player) : void{
        if(!isset($this->tasks[$hash = spl_object_hash($player)]) || $this->tasks[$hash]->getHandler()->isCancelled()){
            $this->tasks[$hash] = new GrowingTask($this, $player);
            $this->getScheduler()->scheduleRepeatingTask($this->tasks[$hash], 2);
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        $this->registerTask($event->getPlayer());
    }
}