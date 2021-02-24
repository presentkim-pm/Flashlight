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
 * @noinspection PhpDocSignatureInspection
 */

declare(strict_types=1);

namespace kim\present\flashlight;

use kim\present\flashlight\task\FlashlightTask;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use ReflectionException;

use function spl_object_hash;

final class Loader extends PluginBase{
    /** @var FlashlightTask[] */
    private array $tasks = [];

    private int $updateDelay = 5;

    /** @throws ReflectionException */
    protected function onEnable() : void{
        $this->updateDelay = max(1, (int) ($this->getConfig()->getNested("update-delay", 0.25) * 20));

        $this->getServer()->getPluginManager()->registerEvent(PlayerItemHeldEvent::class, function(PlayerItemHeldEvent $event) : void{
            $this->createFlashlight($event->getPlayer(), $event->getItem());
        }, EventPriority::MONITOR, $this, false);
        $this->getServer()->getPluginManager()->registerEvent(PlayerJoinEvent::class, function(PlayerJoinEvent $event) : void{
            $player = $event->getPlayer();
            $this->createFlashlight($player, $player->getInventory()->getItemInHand());
        }, EventPriority::MONITOR, $this, false);
    }

    private function createFlashlight(Player $player, Item $item) : void{
        $task = $this->tasks[$hash = spl_object_hash($player)] ?? null;
        $lightLevel = $this->getLightLevelFromItem($item);
        if($task === null || $task->getHandler() === null || $task->getHandler()->isCancelled()){
            $this->tasks[$hash] = new FlashlightTask($player, $lightLevel);
            $this->getScheduler()->scheduleRepeatingTask($this->tasks[$hash], $this->updateDelay);
        }else{
            $task->setLightLevel($lightLevel);
        }
    }

    private function getLightLevelFromItem(Item $item) : int{
        //TODO: Direct mapping of light sources that exist only as items
        return $item->getBlock()->getLightLevel();
    }
}