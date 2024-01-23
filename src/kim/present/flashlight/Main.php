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

namespace kim\present\flashlight;

use kim\present\flashlight\listener\EventListener;
use kim\present\flashlight\utils\LightLevelCalculator;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;

final class Main extends PluginBase{
    protected function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(
            $this,
            (int) max(1, ($this->getConfig()->getNested("update-delay", 0.25) * 20))
        ), $this);

        $itemLightLevels = $this->getConfig()->getNested("item-light-levels", []);
        if(is_array($itemLightLevels)){
            foreach($itemLightLevels as $itemString => $lightLevel){
                try{
                    $item = StringToItemParser::getInstance()->parse($itemString) ?? LegacyStringToItemParser::getInstance()->parse($itemString);
                    LightLevelCalculator::setLightLevel($item->getStateId(), max(0, min(15, $lightLevel)));
                }catch(LegacyStringToItemParserException){
                    $this->getLogger()->error("Error when 'item-light-levels' data read...");
                    $this->getLogger()->error("'$itemString' is invalid item name");
                }
            }
        }
    }
}