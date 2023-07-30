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

namespace vennv\vbasket\data;

use Exception;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use vennv\vbasket\utils\Balance;
use vennv\vbasket\utils\ItemUtil;
use vennv\vbasket\utils\TypeVBasket;
use vennv\vbasket\VBasket;

final class DataManager {

    private static array $data = [];

    public static function setData(Player $player, $value) : void {
        self::$data[$player->getXuid()] = $value;
    }

    public static function getData(Player $player) : ?DataVBasket {
        return self::$data[$player->getXuid()] ?? null;
    }

    public static function getBalancing(int|float $add, int|float $value, int|float $size) : Balance {
        $should = true;
        $quantity = 0;
        $add = $value + $add;

        if ($value === $size) {
            $should = false;
        }

        if ($add > $size) {
            $add += $size - $add;
            $quantity = abs($size - $add);
        }

        return new Balance($should, $add, $quantity);
    }

    public static function giveVBasket(Player $player, string $type, int $amount) : void {
        for ($i = 0; $i < $amount; $i++) {
            $item = self::getVBasket($player, $type);

            if ($item !== null) {
                $player->getInventory()->addItem($item);
            }
        }
    }

    public static function getVBasket(Player $player, string $type) : ?Item {
        $item = ItemUtil::getItem("barrel");
        $item = match ($type) {
            TypeVBasket::SEEDS => $item->setCustomName(self::getConfig()->get("seeds")),
            TypeVBasket::NETHER_WART => $item->setCustomName(self::getConfig()->get("nether_wart")),
            default => null
        };

        if ($item !== null) {
            $timeString = microtime(true) . $player->getName() . rand(1, 1000);

            $item->setLore(self::getConfig()->get("lore"));
            $item->getNamedTag()->setString("items_vbasket", (new DataVBasket($player))->encode());
            $item->getNamedTag()->setString("type_vbasket", $type);
            $item->getNamedTag()->setString("vbasket", "vbasket");
            $item->getNamedTag()->setString("time_give_vbasket", $timeString);
        }

        return $item;
    }

    public static function removeData(Player $player) : void {
        $data = self::getData($player);

        if ($data !== null) {
            $item = $data->getItemInHand();

            try {
                $player->getInventory()->removeItem($item);
                $item->getNamedTag()->setString("items_vbasket", $data->encodeItems());
                $player->selectHotbarSlot(0);
                $player->getInventory()->addItem($item);
            } catch (Exception $error) {
                $player->sendMessage(
                    TextFormat::RED . "An error occurred while saving your basket. Error: " . $error->getMessage()
                );
            }
        }

        unset(self::$data[$player->getXuid()]);
    }

    public static function getConfig() : Config {
        return VBasket::getInstance()->getConfig();
    }

    public static function getContentsVBasket(Player $player, Item $item) : array {
        $data = $item->getNamedTag()->getString("items_vbasket");

        return (new DataVBasket($player))->decodeItems($data);
    }

    public static function getTypeVBasket(Item $item) : string {
        return $item->getNamedTag()->getString("type_vbasket");
    }

    public static function isVBasket(Item $item) : bool {
        try {
            return $item->getNamedTag()->getString("vbasket") === "vbasket";
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getItemsVBasket(Item $item) : string {
        return $item->getNamedTag()->getString("items_vbasket");
    }

    public static function generatorIdVBasket(Player $player) : string {
        return $player->getXuid() . "-" . microtime(true);
    }

    public static function openVBasket(Player $player, Item $backpack) : void {
        $type = self::getTypeVBasket($backpack);
        $items = self::getContentsVBasket($player, $backpack);

        $data = new DataVBasket($player);
        $data->setWindowCurrent([
            "type" => $type,
            "items" => $items,
            "item_in_hand" => $backpack,
        ]);

        self::setData($player, $data);
        self::getData($player)->getVBasket();
    }

}