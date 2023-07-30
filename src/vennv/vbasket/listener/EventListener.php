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

declare(strict_types = 1);

namespace vennv\vbasket\listener;

use pocketmine\event\player\PlayerJumpEvent;
use Throwable;
use pocketmine\block\Air;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use vennv\vbasket\data\DataManager;
use vennv\vbasket\event\VBasketPlantEvent;
use vennv\vbasket\utils\ItemUtil;
use vennv\vbasket\utils\MathUtil;
use vennv\vbasket\utils\Seeds;
use vennv\vbasket\utils\TypeVBasket;

final class EventListener implements Listener {

    public function onPlayerJump(PlayerJumpEvent $event) : void {
        $player = $event->getPlayer();

        $itemHand = $player->getInventory()->getItemInHand();

        if (DataManager::isVBasket($itemHand)) {
            DataManager::openVBasket($player, $itemHand);
        }
    }

    /**
     * @throws Throwable
     */
    public function onPlayerInteract(PlayerInteractEvent $event) : void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $world = $player->getWorld();
        $location = clone $player->getLocation();
        $positionBlock = $block->getPosition();
        $inventory = $player->getInventory();

        $positionBlock = $world->getBlock($positionBlock->asVector3()->add(0, 1, 0))->getPosition();

        $itemHand = clone $inventory->getItemInHand();

        if (DataManager::isVBasket($itemHand)) {
            $event->cancel();

            $typeVBasket = DataManager::getTypeVBasket($itemHand);

            $blockFarmLand = match ($typeVBasket) {
                TypeVBasket::SEEDS => VanillaBlocks::FARMLAND(),
                TypeVBasket::NETHER_WART => VanillaBlocks::SOUL_SAND(),
                default => null,
            };

            if ($blockFarmLand !== null) {
                for ($count = 0; $count < DataManager::getConfig()->get("length"); $count++) {
                    $nextVector = MathUtil::getNextBlockByInteract($location, $positionBlock->asVector3(), $count);

                    $blockHere = $world->getBlock($nextVector);
                    $blockDownHere = $world->getBlock($nextVector->subtract(0, 1, 0));

                    if (
                        !$blockHere instanceof Air ||
                        !$blockDownHere instanceof $blockFarmLand ||
                        $blockDownHere instanceof Air
                    ) {
                        break;
                    }
                }

                if ($count === 0) {
                    $player->sendMessage(DataManager::getConfig()->get("not_the_place"));
                } else {
                    $i = $count;
                    $itemsToPlant = [];

                    $items = DataManager::getContentsVBasket($player, $itemHand);
                    if (count($items) > 0) {
                        foreach ($items as $key => [$countItem, $item]) {
                            if (is_string($item)) {
                                $itemSeed = ItemUtil::decodeItem($item);

                                if (
                                    (Seeds::isSeedsItem($itemSeed) || Seeds::isNetherWartItem($itemSeed)) &&
                                    ($typeVBasket === TypeVBasket::SEEDS || $typeVBasket === TypeVBasket::NETHER_WART) &&
                                    $i > 0
                                ) {
                                    if (!isset($itemsToPlant[$itemSeed->getTypeId()])) {
                                        $itemsToPlant[$itemSeed->getTypeId()] = $itemSeed->setCount(0);
                                    }

                                    $itemSeedCount = $itemsToPlant[$itemSeed->getTypeId()]->getCount();

                                    $balance = DataManager::getBalancing($countItem, $itemSeedCount, $i);
                                    if ($balance->should) {
                                        $itemsToPlant[$itemSeed->getTypeId()] = $itemSeed->setCount($itemSeedCount + $balance->add);

                                        if ($balance->add === $countItem) {
                                            unset($items[$key]);
                                        } else {
                                            $items[$key][0] -= $balance->add;
                                        }

                                        $i -= $balance->add;
                                    }
                                }
                            }
                        }
                    }

                    if (count($itemsToPlant) > 0) {
                        $eventVBasket = new VBasketPlantEvent($player, $world, $location, $positionBlock, $itemsToPlant);
                        $eventVBasket->call();

                        $encode = base64_encode(gzcompress(json_encode($items)));
                        $itemHand->getNamedTag()->setString("items_vbasket", $encode);
                        $inventory->setItemInHand($itemHand);
                    } else {
                        $player->sendMessage(DataManager::getConfig()->get("dont_have_seeds"));
                    }
                }
            }
        }
    }

}
