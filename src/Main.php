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

namespace kim\present\flashlight;

use kim\present\flashlight\listener\EventListener;
use kim\present\flashlight\utils\LightLevelCalculator;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;

use function is_array;
use function is_float;
use function is_int;
use function max;
use function min;

final class Main extends PluginBase{

    protected function onEnable() : void{
        $this->saveDefaultConfig();
        $config = $this->getConfig();

        $updateDelay = $config->getNested("update-delay", 0.25);
        if(!is_int($updateDelay) && !is_float($updateDelay)){
            $this->getLogger()->warning("'update-delay' must be a number, using the default value (0.25)");
            $updateDelay = 0.25;
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(
            $this,
            max(1, (int) ($updateDelay * 20))
        ), $this);

        $itemLightLevels = $config->getNested("item-light-levels", []);
        if(!is_array($itemLightLevels)){
            return;
        }
        $stringToItemParser = StringToItemParser::getInstance();
        $legacyParser = LegacyStringToItemParser::getInstance();
        foreach($itemLightLevels as $itemString => $lightLevel){
            $itemString = (string) $itemString;
            if(!is_int($lightLevel)){
                $this->getLogger()->error("Error when 'item-light-levels' data read...");
                $this->getLogger()->error("Light level of '$itemString' must be an integer");
                continue;
            }
            try{
                $item = $stringToItemParser->parse($itemString) ?? $legacyParser->parse($itemString);
                LightLevelCalculator::setLightLevel($item->getStateId(), max(0, min(15, $lightLevel)));
            }catch(LegacyStringToItemParserException){
                $this->getLogger()->error("Error when 'item-light-levels' data read...");
                $this->getLogger()->error("'$itemString' is invalid item name");
            }
        }
    }
}
