<?php

declare(strict_types=1);

namespace kim\present\flashlight\tests;

use kim\present\flashlight\utils\LightLevelCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;

final class LightLevelCalculatorTest extends TestCase{

    /** @return iterable<string, array{int, int}> */
    public static function lightBlockLevelProvider() : iterable{
        yield "no light" => [0, 0];
        yield "weakest light" => [1, 0];
        yield "torch" => [14, 13];
        yield "full light" => [15, 14];
        yield "out of range is clamped" => [40, 14];
        yield "negative is clamped" => [-3, 0];
    }

    #[DataProvider('lightBlockLevelProvider')]
    public function testToLightBlockLevel(int $itemLevel, int $expected) : void{
        self::assertSame($expected, LightLevelCalculator::toLightBlockLevel($itemLevel));
    }

    public function testCalcUsesTheLightLevelOfTheBlock() : void{
        self::assertSame(0, LightLevelCalculator::calc(VanillaItems::APPLE()));
        self::assertSame(14, LightLevelCalculator::calc(VanillaBlocks::TORCH()->asItem()));
    }

    public function testCalcUsesTheConfiguredLightLevel() : void{
        $bucket = VanillaItems::LAVA_BUCKET();
        LightLevelCalculator::setLightLevel($bucket->getStateId(), 15);
        self::assertSame(15, LightLevelCalculator::calc($bucket));
    }
}
