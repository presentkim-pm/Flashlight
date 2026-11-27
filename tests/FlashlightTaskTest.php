<?php

declare(strict_types=1);

namespace kim\present\flashlight\tests;

use kim\present\flashlight\task\FlashlightTask;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use PHPUnit\Framework\TestCase;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\world\Position;
use pocketmine\world\World;

/** Checks the packets the task broadcasts, with the real protocol serializer. */
final class FlashlightTaskTest extends TestCase{

    /** @return UpdateBlockPacket[] */
    private static function sendBlockLayers(Block $normalLayer, Block $liquidLayer) : array{
        /** @var ClientboundPacket[] $sent */
        $sent = [];
        $world = self::createStub(World::class);
        $world->method('isLoaded')->willReturn(true);
        $world->method('broadcastPacketToViewers')->willReturnCallback(
            function($pos, ClientboundPacket $pk) use (&$sent) : void{
                $sent[] = $pk;
            }
        );

        $method = new \ReflectionMethod(FlashlightTask::class, 'sendBlockLayers');
        $method->invoke(null, new Position(10, 64, -20, $world), $normalLayer, $liquidLayer);

        $packets = [];
        foreach($sent as $pk){
            self::assertInstanceOf(UpdateBlockPacket::class, $pk);
            $packets[] = $pk;
        }
        return $packets;
    }

    public function testBlockLayersPacketsAreSentAndEncodable() : void{
        $light = VanillaBlocks::LIGHT()->setLightLevel(13);
        $packets = self::sendBlockLayers($light, VanillaBlocks::AIR());
        self::assertCount(2, $packets);

        $translator = TypeConverter::getInstance()->getBlockTranslator();
        [$normal, $liquid] = $packets;
        self::assertSame(UpdateBlockPacket::DATA_LAYER_NORMAL, $normal->dataLayerId);
        self::assertSame(UpdateBlockPacket::DATA_LAYER_LIQUID, $liquid->dataLayerId);
        self::assertSame($translator->internalIdToNetworkId($light->getStateId()), $normal->blockRuntimeId);
        self::assertSame($translator->internalIdToNetworkId(VanillaBlocks::AIR()->getStateId()), $liquid->blockRuntimeId);
        self::assertSame([10, 64, -20], [$normal->blockPosition->getX(), $normal->blockPosition->getY(), $normal->blockPosition->getZ()]);

        foreach($packets as $pk){
            $out = new ByteBufferWriter();
            $pk->encode($out);
            $copy = new UpdateBlockPacket();
            $in = new ByteBufferReader($out->getData());
            $copy->decode($in);
            self::assertSame(0, $in->getUnreadLength());
            self::assertSame($pk->blockRuntimeId, $copy->blockRuntimeId);
            self::assertSame($pk->dataLayerId, $copy->dataLayerId);
            self::assertSame($pk->flags, $copy->flags);
        }
    }

    public function testEveryLightLevelHasANetworkId() : void{
        $translator = TypeConverter::getInstance()->getBlockTranslator();
        $ids = [];
        for($level = 0; $level <= 14; ++$level){
            $ids[$translator->internalIdToNetworkId(VanillaBlocks::LIGHT()->setLightLevel($level)->getStateId())] = true;
        }
        self::assertCount(15, $ids, "Every light level must map to its own block state");
    }
}
