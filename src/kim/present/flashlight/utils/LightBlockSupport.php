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

use Closure;
use customiesdevs\customies\world\LegacyBlockIdToStringIdMap;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\Utils;
use Webmozart\PathUtil\Path;

use const pocketmine\BEDROCK_DATA_PATH;

final class LightBlockSupport{
    private function __construct(){ }

    public static function registerToCustomies() : void{
        //TODO: Support Customies plugin
    }

    public static function registerToPm(PluginBase $plugin) : void{
        $stream = PacketSerializer::decoder(
            Utils::assumeNotFalse(file_get_contents(Path::join(BEDROCK_DATA_PATH, "canonical_block_states.nbt")), "Missing required resource file"),
            0,
            new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary())
        );
        $k = 0;
        $lightBlockRuntimeIds = [];
        while(!$stream->feof()){
            ++$k;
            $state = $stream->getNbtCompoundRoot();
            if($state->getString("name") === "minecraft:light_block"){
                $lightBlockRuntimeIds[] = $k;
            }
        }

        $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($lightBlockRuntimeIds) : void{
            self::applyToRuntionBlockMapping($lightBlockRuntimeIds);
            $server = Server::getInstance();
            $server->getAsyncPool()->addWorkerStartHook(static function(int $worker) use ($server, $lightBlockRuntimeIds) : void{
                $server->getAsyncPool()->submitTaskToWorker(new class($lightBlockRuntimeIds) extends AsyncTask{
                    /** @param int[] $lightBlockRuntimeIds */
                    public function __construct(private array $lightBlockRuntimeIds){
                    }

                    public function onRun() : void{
                        LightBlockSupport::applyToRuntionBlockMapping((array) $this->lightBlockRuntimeIds);
                    }
                }, $worker);
            });
        }), 0);
    }

    /**
     * @param int[] $lightBlockRuntimeIds
     *
     * @internal
     */
    public static function applyToRuntionBlockMapping(array $lightBlockRuntimeIds) : void{
        $legacyId = LegacyBlockIdToStringIdMap::getInstance()->stringToLegacy("minecraft:light_block");
        foreach($lightBlockRuntimeIds as $legacyMeta => $staticRuntimeId){
            Closure::bind( //HACK: Closure bind hack to access inaccessible members
                closure: static function($staticRuntimeId, $legacyId, $legacyMeta){
                    RuntimeBlockMapping::getInstance()->registerMapping($staticRuntimeId, $legacyId, $legacyMeta);
                },
                newThis: null,
                newScope: RuntimeBlockMapping::class
            )($staticRuntimeId, $legacyId, $legacyMeta);
        }
    }
}