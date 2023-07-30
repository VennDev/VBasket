<?php

/**
 * VBasket - PocketMine plugin.
 * Copyright (C) 2023 - 2025 VennDev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace vennv\vbasket\event;

use Throwable;
use pocketmine\block\Air;
use pocketmine\block\NetherWartPlant;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\event\Event;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\sound\BlockBreakSound;
use pocketmine\world\World;
use vennv\vapm\Async;
use vennv\vapm\Promise;
use vennv\vbasket\utils\MathUtil;
use vennv\vbasket\utils\Seeds;

class VBasketPlantEvent extends Event implements Cancellable {

    use CancellableTrait;

    private Player $player;

    private World $world;

    private Location $location;

    private Position $positionBlock;

    private array $itemsToPlant;

    /**
     * @param Player $player
     * @param World $world
     * @param Location $location
     * @param Position $positionBlock
     * @param array<int, Item> $itemsToPlant
     * @throws Throwable
     */
    public function __construct(
        Player   $player,
        World    $world,
        Location $location,
        Position $positionBlock,
        array    $itemsToPlant
    ) {
        $this->player = $player;
        $this->world = $world;
        $this->location = $location;
        $this->positionBlock = $positionBlock;
        $this->itemsToPlant = $itemsToPlant;

        $this->doJob();
    }

    public function getPlayer() : Player {
        return $this->player;
    }

    public function getWorld() : World {
        return $this->world;
    }

    public function getLocation() : Location {
        return $this->location;
    }

    public function getPositionBlock() : Position {
        return $this->positionBlock;
    }

    public function getItemsToPlant() : array {
        return $this->itemsToPlant;
    }

    /**
     * @throws Throwable
     */
    private function doJob() : void {
        new Async(function () {
            $nextNumber = 1;

            foreach ($this->itemsToPlant as $item) {
                for ($i = 0; $i < $item->getCount(); $i++) {
                    Async::await(new Promise(function ($resolve) use ($item, &$nextNumber) {
                        $nextVector = MathUtil::getNextBlockByInteract($this->location, $this->positionBlock->asVector3(), $nextNumber);

                        $blockHere = $this->world->getBlock($nextVector);
                        $blockDownHere = $this->world->getBlock($nextVector->subtract(0, 1, 0));

                        $blockSeed = Seeds::getBlockSeeds($item);

                        if ($blockSeed instanceof NetherWartPlant) {
                            $blockDown = VanillaBlocks::SOUL_SAND();
                        } else {
                            $blockDown = VanillaBlocks::FARMLAND();
                        }

                        if ($blockHere instanceof Air && $blockDownHere instanceof $blockDown) {
                            $this->world->setBlock(
                                $nextVector,
                                $blockSeed
                            );

                            $viewers = $this->world->getViewersForPosition($nextVector);
                            $this->world->addSound($nextVector, new BlockBreakSound(VanillaBlocks::COCOA_POD()), $viewers);
                        }

                        $resolve(true);
                        $nextNumber++;
                    }));
                }
            }
        });
    }

}