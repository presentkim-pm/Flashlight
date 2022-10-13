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

namespace kim\present\flashlight\task;

use pocketmine\block\Liquid;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\Position;

class FlashlightTask extends Task{
    public const LIGHT_BLOCK = 470;

    private Player $player;
    private int $lightLevel = 0;
    private ?Position $pos = null;

    public function __construct(Player $player, int $lightLevel){
        $this->player = $player;
        $this->setLightLevel($lightLevel);
    }

    public function onRun() : void{
        if($this->player->isClosed() || !$this->player->isConnected() || $this->lightLevel <= 0){
            $this->getHandler()?->cancel();
            return;
        }

        $pos = $this->player->getPosition();
        $newPos = Position::fromObject($pos->add(0.5, 1, 0.5)->floor(), $pos->getWorld());
        if($this->pos === null || !$this->pos->equals($newPos)){
            $this->restoreBlock();
            $this->pos = $newPos;
            $this->overrideBlock();
        }
    }

    public function onCancel() : void{
        $this->restoreBlock();
    }

    public function setLightLevel(int $lightLevel) : void{
        $lightLevel = max(0, $lightLevel & 0xf - 1);

        if($this->lightLevel === $lightLevel){
            return;
        }

        $this->lightLevel = $lightLevel;
        $this->overrideBlock();
    }

    private function restoreBlock() : void{
        if($this->pos === null){
            return;
        }

        $normalLayer = RuntimeBlockMapping::getInstance()->toRuntimeId($this->pos->world->getBlock($this->pos)->getFullId());
        self::sendBlockLayers($this->pos, $normalLayer, self::AIR());
    }

    private function overrideBlock() : void{
        if($this->pos === null){
            return;
        }

        $block = $this->pos->world->getBlock($this->pos);
        $normalLayer = RuntimeBlockMapping::getInstance()->toRuntimeId($block->getFullId());
        $liquidLayer = self::LIGHT($this->lightLevel);
        if($block instanceof Liquid){
            [$normalLayer, $liquidLayer] = [$liquidLayer, $normalLayer];
        }

        self::sendBlockLayers($this->pos, $normalLayer, $liquidLayer);
    }

    private static function sendBlockLayers(Position $pos, int $normalLayer, int $liquidLayer) : void{
        $blockPos = BlockPosition::fromVector3($pos);
        Server::getInstance()->broadcastPackets($pos->world->getViewersForPosition($pos), [
            UpdateBlockPacket::create($blockPos, $normalLayer, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL),
            UpdateBlockPacket::create($blockPos, $liquidLayer, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_LIQUID)
        ]);
    }

    public static function AIR() : int{
        return RuntimeBlockMapping::getInstance()->toRuntimeId(0);
    }

    public static function LIGHT(int $lightLevel) : int{
        return RuntimeBlockMapping::getInstance()->toRuntimeId(self::LIGHT_BLOCK << 4 | $lightLevel);
    }
}