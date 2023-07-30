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

namespace vennv\vbasket\utils;

use pocketmine\block\Block;
use pocketmine\item\Item;

final class Seeds {

    public static array $seeds_item = [];

    public static array $nether_wart_item = [];

    public static array $blockSeeds = [];

    public static function isSeedsItem(Item $item) : bool {
        foreach (self::$seeds_item as $seeds) {
            if ($item instanceof $seeds) {
                return true;
            }
        }

        return false;
    }

    public static function isNetherWartItem(Item $item) : bool {
        foreach (self::$nether_wart_item as $nether_wart) {
            if ($item->getTypeId() === $nether_wart->getTypeId()) {
                return true;
            }
        }

        return false;
    }

    public static function isBlockSeeds(Item $item) : bool {
        foreach (self::$blockSeeds as $blockSeeds) {
            if ($item->getTypeId() === $blockSeeds->getTypeId()) {
                return true;
            }
        }

        return false;
    }

    public static function getBlockSeeds(Item $item) : ?Block {
        foreach (self::$blockSeeds as $itemSeed => $blockSeed) {
            if ($item instanceof $itemSeed) {
                return $blockSeed;
            }
        }

        return null;
    }

}