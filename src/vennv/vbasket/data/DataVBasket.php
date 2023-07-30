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

use Throwable;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use vennv\vbasket\utils\ItemUtil;
use vennv\vbasket\utils\TypeVBasket;

final class DataVBasket {

    private Player $owner;
    private array $windowCurrent = [];

    public function __construct(Player $owner) {
        $this->owner = $owner;
    }

    public function getOwner() : Player {
        return $this->owner;
    }

    public function setWindowCurrent(array $window) : void {
        $this->windowCurrent = $window;
    }

    public function getWindowCurrent() : array {
        return $this->windowCurrent;
    }

    public function getTypeCurrent() : string {
        return $this->windowCurrent["type"];
    }

    public function getItemInHand() : ?Item {
        return $this->windowCurrent["item_in_hand"] ?? null;
    }

    public function encode() : string {
        return base64_encode(gzcompress(json_encode($this->windowCurrent)));
    }

    public function decode(string $data) : array {
        return json_decode(gzuncompress(base64_decode($data)), true);
    }

    public function encodeItems() : string {
        return base64_encode(gzcompress(json_encode($this->windowCurrent["items"])));
    }

    public function decodeItems(string $items) : array {
        return json_decode(gzuncompress(base64_decode($items)), true);
    }

    /**
     * @throws Throwable
     */
    public function saveItemsCurrent(array $contents) : void {
        $this->windowCurrent["items"] = [];

        foreach ($contents as $item) {
            $this->windowCurrent["items"][] = [$item->getCount(), ItemUtil::encodeItem($item)];
        }
    }

    public function getVBasket() : void {
        $name = "";

        $type = match ($this->getTypeCurrent()) {
            TypeVBasket::SEEDS => $name = DataManager::getConfig()->get("seeds"),
            TypeVBasket::NETHER_WART => $name = DataManager::getConfig()->get("nether_wart"),
            default => null
        };

        if ($type !== null) {
            $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
            $menu->setName($name);

            $inventory = $menu->getInventory();

            if (count($this->windowCurrent["items"]) > 0) {
                foreach ($this->windowCurrent["items"] as [$count, $item]) {
                    if (is_string($item)) {
                        $inventory->addItem(ItemUtil::decodeItem($item)->setCount($count));
                    }
                }
            }

            $menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
                $in = $transaction->getIn();

                if (DataManager::isVBasket($in)) {
                    return $transaction->discard();
                }

                return $transaction->continue();
            });

            $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) {
                $this->saveItemsCurrent($inventory->getContents());
                DataManager::removeData($this->owner);
            });

            $menu->send($this->owner);
        }
    }

}