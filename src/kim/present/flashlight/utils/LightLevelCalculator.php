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
 * @noinspection PhpInternalEntityUsedInspection
 */

declare(strict_types=1);

namespace kim\present\flashlight\utils;

use pocketmine\item\Item;

final class LightLevelCalculator{
    /**
     * @var array $lightLevelMap
     * @phpstan-var array<string, int> $lightLevelMap
     */
    private static array $lightLevelMap = [];

    private function __construct(){ }

    /**
     * Set the lighting level for a specific ID and meta directly
     * It supports for set the light level of an item that is not automatically calculated (such as a lava bucket) or a custom items.
     */
    public static function setLightLevel(int $id, int $meta, int $lightLevel) : void{
        self::$lightLevelMap["$id:$meta"] = $lightLevel;
    }

    public static function calc(Item $item) : int{
        return self::$lightLevelMap["{$item->getId()}:{$item->getMeta()}"] ??= $item->getBlock()->getLightLevel();
    }
}