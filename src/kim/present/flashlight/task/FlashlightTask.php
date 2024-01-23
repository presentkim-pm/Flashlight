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

namespace kim\present\flashlight\task;

use kim\present\flashlight\utils\LightLevelCalculator;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;

class FlashlightTask extends Task{
    public const LIGHT_BLOCK = 470;

    private Player $player;
    private int $lightLevel = 0;
    private ?Position $pos = null;

    private bool $requireLightLevelUpdate = false;

    public function __construct(Player $player){
        $this->player = $player;
        $this->requestLightLevelUpdate();

        $inventoryListener = CallbackInventoryListener::onAnyChange(fn() => $this->requestLightLevelUpdate());
        $player->getInventory()->getListeners()->add($inventoryListener);
        $player->getOffHandInventory()->getListeners()->add($inventoryListener);
    }

    public function onRun() : void{
        //When player was leaved from server, cancle task self.
        if($this->player->isClosed() || !$this->player->isConnected()){
            $this->getHandler()?->cancel();
            return;
        }

        //If light level update required, update the light level according to the player's inventory
        if($this->requireLightLevelUpdate){
            $this->setLightLevel(max(
                LightLevelCalculator::calc($this->player->getInventory()->getItemInHand()),
                LightLevelCalculator::calc($this->player->getOffHandInventory()->getItem(0))
            ));
            $this->requireLightLevelUpdate = false;
        }

        if($this->lightLevel <= 0){
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

    /**
     * Request to recalculate light level on next 'onRun' call.
     * Designed for reduce the overload of calculating light level for each inventory change.
     */
    public function requestLightLevelUpdate() : void{
        $this->requireLightLevelUpdate = true;
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

        $blockTranslator = TypeConverter::getInstance()->getBlockTranslator();
        $normalLayer = $blockTranslator->internalIdToNetworkId($this->pos->world->getBlock($this->pos)->getStateId());
        self::sendBlockLayers($this->pos, $normalLayer, self::AIR());
    }

    private function overrideBlock() : void{
        if($this->pos === null){
            return;
        }

        $block = $this->pos->world->getBlock($this->pos);
        $blockTranslator = TypeConverter::getInstance()->getBlockTranslator();
        $normalLayer = $blockTranslator->internalIdToNetworkId($block->getStateId());
        $liquidLayer = self::LIGHT($this->lightLevel);
        if($block instanceof Liquid){
            [$normalLayer, $liquidLayer] = [$liquidLayer, $normalLayer];
        }

        self::sendBlockLayers($this->pos, $normalLayer, $liquidLayer);
    }

    private static function sendBlockLayers(Position $pos, int $normalLayer, int $liquidLayer) : void{
        $blockPos = BlockPosition::fromVector3($pos);
        $pos->world->broadcastPacketToViewers($pos, UpdateBlockPacket::create($blockPos, $normalLayer, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL));
        $pos->world->broadcastPacketToViewers($pos, UpdateBlockPacket::create($blockPos, $liquidLayer, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_LIQUID));
    }

    private static function AIR() : int{
        $blockTranslator = TypeConverter::getInstance()->getBlockTranslator();
        return $blockTranslator->internalIdToNetworkId(VanillaBlocks::AIR()->getStateId());
    }

    private static function LIGHT(int $lightLevel) : int{
        $blockTranslator = TypeConverter::getInstance()->getBlockTranslator();
        return $blockTranslator->internalIdToNetworkId(VanillaBlocks::LIGHT()->setLightLevel($lightLevel)->getStateId());
    }
}