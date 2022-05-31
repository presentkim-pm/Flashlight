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
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\flashlight\listener;

use kim\present\flashlight\Main;
use kim\present\flashlight\task\FlashlightTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

use function spl_object_hash;

final class EventListener implements Listener{
    /** @var FlashlightTask[] */
    private array $tasks = [];

    public function __construct(
        private Main $plugin,
        private int $updateDelay
    ){
    }

    /** @priority MONITOR */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event) : void{
        $this->createFlashlight($event->getPlayer(), $event->getItem());
    }

    /** @priority MONITOR */
    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        $this->createFlashlight($player, $player->getInventory()->getItemInHand());
    }

    private function createFlashlight(Player $player, Item $item) : void{
        $task = $this->tasks[$hash = spl_object_hash($player)] ?? null;
        $lightLevel = $this->getLightLevelFromItem($item);
        if($task === null || $task->getHandler() === null || $task->getHandler()->isCancelled()){
            $this->tasks[$hash] = new FlashlightTask($player, $lightLevel);
            $this->plugin->getScheduler()->scheduleRepeatingTask($this->tasks[$hash], $this->updateDelay);
        }else{
            $task->setLightLevel($lightLevel);
        }
    }

    private function getLightLevelFromItem(Item $item) : int{
        //TODO: Direct mapping of light sources that exist only as items
        return $item->getBlock()->getLightLevel();
    }
}