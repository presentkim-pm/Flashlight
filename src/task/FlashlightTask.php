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

namespace kim\present\flashlight\task;

use kim\present\flashlight\utils\LightLevelCalculator;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;

use function max;

class FlashlightTask extends Task{

    private int $lightLevel = 0;
    private ?Position $pos = null;

    private bool $requireLightLevelUpdate = false;

    public function __construct(private readonly Player $player){
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
        $lightLevel = LightLevelCalculator::toLightBlockLevel($lightLevel);

        if($this->lightLevel === $lightLevel){
            return;
        }

        $this->lightLevel = $lightLevel;
        if($lightLevel <= 0){
            //No more light source, so remove the light block instead of leaving a dark one behind
            $this->restoreBlock();
            $this->pos = null;
            return;
        }
        $this->overrideBlock();
    }

    private function restoreBlock() : void{
        if($this->pos === null){
            return;
        }
        self::sendBlockLayers($this->pos, $this->pos->getWorld()->getBlock($this->pos), VanillaBlocks::AIR());
    }

    private function overrideBlock() : void{
        if($this->pos === null){
            return;
        }

        $normalLayer = $this->pos->getWorld()->getBlock($this->pos);
        $liquidLayer = VanillaBlocks::LIGHT()->setLightLevel($this->lightLevel);
        if($normalLayer instanceof Liquid){
            [$normalLayer, $liquidLayer] = [$liquidLayer, $normalLayer];
        }

        self::sendBlockLayers($this->pos, $normalLayer, $liquidLayer);
    }

    private static function sendBlockLayers(Position $pos, Block $normalLayer, Block $liquidLayer) : void{
        $blockTranslator = TypeConverter::getInstance()->getBlockTranslator();
        $normalLayerId = $blockTranslator->internalIdToNetworkId($normalLayer->getStateId());
        $liquidLayerId = $blockTranslator->internalIdToNetworkId($liquidLayer->getStateId());

        $blockPos = BlockPosition::fromVector3($pos);
        $pos->getWorld()->broadcastPacketToViewers(
            $pos,
            UpdateBlockPacket::create(
                $blockPos,
                $normalLayerId,
                UpdateBlockPacket::FLAG_NETWORK,
                UpdateBlockPacket::DATA_LAYER_NORMAL
            )
        );
        $pos->getWorld()->broadcastPacketToViewers(
            $pos,
            UpdateBlockPacket::create(
                $blockPos,
                $liquidLayerId,
                UpdateBlockPacket::FLAG_NETWORK,
                UpdateBlockPacket::DATA_LAYER_LIQUID
            )
        );
    }
}
