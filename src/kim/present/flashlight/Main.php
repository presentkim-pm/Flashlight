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

namespace kim\present\flashlight;

use kim\present\flashlight\listener\EventListener;
use kim\present\flashlight\utils\LightBlockSupport;
use pocketmine\plugin\PluginBase;

use function max;

final class Main extends PluginBase{
    protected function onEnable() : void{
        LightBlockSupport::registerToPm($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(
            $this,
            (int) max(1, ($this->getConfig()->getNested("update-delay", 0.25) * 20))
        ), $this);
    }
}