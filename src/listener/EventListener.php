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
 * @author       PresentKim (debe3721@gmail.com)
 * @link         https://github.com/PresentKim
 * @license      https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\flashlight\listener;

use kim\present\flashlight\Main;
use kim\present\flashlight\task\FlashlightTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\scheduler\TaskHandler;

final class EventListener implements Listener{

    /**
     * @var TaskHandler[]
     * @phpstan-var array<string, TaskHandler>
     */
    private array $tasks = [];

    public function __construct(
        private readonly Main $plugin,
        private readonly int $updateDelay
    ){}

    /** @priority MONITOR */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event) : void{
        $hash = $event->getPlayer()->getUniqueId()->toString();
        /**
         * I thought it was impossible,
         * but if the PlayerJoinEvent handler given item,
         * it could have happened enough.
         */
        if(!isset($this->tasks[$hash])){
            return;
        }
        $task = $this->tasks[$hash]->getTask();
        if($task instanceof FlashlightTask){
            $task->requestLightLevelUpdate();
        }
    }

    /** @priority MONITOR */
    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        $this->tasks[$player->getUniqueId()->toString()] = $this->plugin->getScheduler()->scheduleRepeatingTask(
            new FlashlightTask($player),
            $this->updateDelay
        );
    }

    /** @priority MONITOR */
    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        $hash = $player->getUniqueId()->toString();
        if(isset($this->tasks[$hash])){
            $this->tasks[$hash]->cancel();
            unset($this->tasks[$hash]);
        }
    }
}
