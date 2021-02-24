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
 * @noinspection PhpInternalEntityUsedInspection
 */

declare(strict_types=1);

namespace kim\present\lightsource\task;

use kim\present\expansionpack\BlockIds;
use kim\present\lightsource\Loader;
use pocketmine\block\Liquid;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\World;

class GrowingTask extends Task{
    private Loader $owningPlugin;
    private Player $player;
    private int $lightLevel = 0;
    private World $world;
    private Vector3 $vec;

    public function __construct(Loader $owningPlugin, Player $player){
        $this->owningPlugin = $owningPlugin;
        $this->player = $player;
    }

    public function onRun() : void{
        if(
            $this->player->isClosed() ||
            $this->setLightLevel($this->player->getInventory()->getItemInHand()->getBlock()->getLightLevel()) ||
            $this->lightLevel <= 0
        )
            return;

        $pos = $this->player->getPosition();
        $world = $pos->getWorld();
        $vec = $pos->add(0.5, 1, 0.5)->floor();
        if(empty($this->world) || empty($this->vec)){
            $this->world = $world;
            $this->vec = $vec;
            $this->restoreBlock($this->world, $this->vec);
            $this->overrideBlock($this->world, $this->vec);
        }elseif($this->world !== $world || !$this->vec->equals($vec)){
            $this->restoreBlock($this->world, $this->vec);
            $this->overrideBlock($world, $vec);
            $this->world = $world;
            $this->vec = $vec;
        }
    }

    public function onCancel() : void{
        if(!empty($this->world) && !empty($this->vec)){
            $this->restoreBlock($this->world, $this->vec);
        }
    }

    private function setLightLevel(int $lightLevel) : bool{
        $lightLevel &= 0xf;
        //TODO: Remove this hack. Currently, UPDATE_BLOCK is displayed at brightness 14 for an unknown reason.
        if($lightLevel === 14){
            $lightLevel = 15;
        }
        if($this->lightLevel !== $lightLevel){
            $this->lightLevel = $lightLevel;
            if(!empty($this->world) && !empty($this->vec)){
                $this->restoreBlock($this->world, $this->vec);
                $this->overrideBlock($this->world, $this->vec);
            }
            return true;
        }

        return false;
    }

    private function restoreBlock(World $world, Vector3 $vec) : void{
        $runtimeId = RuntimeBlockMapping::getInstance()->toRuntimeId($world->getBlock($vec)->getFullId());
        Server::getInstance()->broadcastPackets($this->world->getViewersForPosition($this->vec), [
            UpdateBlockPacket::create($vec->x, $vec->y, $vec->z, $runtimeId),
            UpdateBlockPacket::create($vec->x, $vec->y, $vec->z, self::AIR(), UpdateBlockPacket::DATA_LAYER_LIQUID)
        ]);
    }

    private function overrideBlock(World $world, Vector3 $vec) : void{
        $block = $world->getBlock($vec);
        $normalLayer = RuntimeBlockMapping::getInstance()->toRuntimeId($world->getBlock($vec)->getFullId());
        $liquidLayer = self::LIGHT($this->lightLevel);

        if($block instanceof Liquid){
            [$normalLayer, $liquidLayer] = [$liquidLayer, $normalLayer];
        }

        Server::getInstance()->broadcastPackets($this->world->getViewersForPosition($this->vec), [
            UpdateBlockPacket::create($vec->x, $vec->y, $vec->z, $normalLayer),
            UpdateBlockPacket::create($vec->x, $vec->y, $vec->z, $liquidLayer, UpdateBlockPacket::DATA_LAYER_LIQUID)
        ]);
    }

    public static function AIR() : int{
        static $cache = null;
        if(empty($cache)){
            $cache = RuntimeBlockMapping::getInstance()->toRuntimeId(0);
        }
        return $cache;
    }

    public static function LIGHT(int $lightLevel) : int{
        static $cache = [];
        if(!isset($cache[$lightLevel = $lightLevel & 0xf])){
            $cache[$lightLevel] = RuntimeBlockMapping::getInstance()->toRuntimeId(BlockIds::LIGHT_BLOCK << 4 | $lightLevel);
        }
        return $cache[$lightLevel];
    }
}