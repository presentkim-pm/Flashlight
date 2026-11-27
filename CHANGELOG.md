# Changelog

## 2.0.0 (2026-11-27)

The Poggit plugin version for the original [pmmp/PocketMine-MP](https://github.com/pmmp/PocketMine-MP) 5.x is kept on the
[`legacy/pmmp-5.44`](https://github.com/presentkim-pm/FlashLight/tree/legacy/pmmp-5.44) branch.

### ⚠️ Breaking changes
- Targets [axolotl-pm/PocketMine-MP](https://github.com/axolotl-pm/PocketMine-MP) `5.51` (BedrockProtocol 64,
  Bedrock `1.26.60`) and **PHP 8.4+**.
- Distributed as a phar from GitHub Releases instead of Poggit.
- The light level of the light block is now calculated as `item light level - 1` (a torch gives a level 13 light
  block, a lava bucket 14). Because of an operator precedence bug it used to be `item light level & 14`
  (a torch gave 14, a lava bucket 14, and a level 13 item gave 12).
- `FlashlightTask::LIGHT_BLOCK` (unused) is removed.

### 🔄 Changes
- `composer.json` added (Composer is only used for development and testing); `plugin.yml` has
  `src-namespace-prefix`, and the sources moved from `src/kim/present/flashlight/` to `src/`.
- PHPUnit tests for the light level calculation and for the `UpdateBlockPacket`s the task sends, plus a class load test.
- Invalid `update-delay` / `item-light-levels` values in `config.yml` are reported in the console instead of crashing.

### 🐛 Fixes
- `config.yml` was never saved to the data folder (`saveDefaultConfig()` was missing), so the documented settings
  (`update-delay`, `item-light-levels`) could not be edited.
- The light block was left behind (as an invisible level 0 light block) when the player put the light source away. It
  is now restored to the real block.
- Quitting a player whose task had not been registered raised an undefined array key warning.
