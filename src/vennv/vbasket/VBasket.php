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

declare(strict_types=1);

namespace vennv\vbasket;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\BeetrootSeeds;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\item\Beetroot;
use pocketmine\item\Carrot;
use pocketmine\item\MelonSeeds;
use pocketmine\item\Potato;
use pocketmine\item\PumpkinSeeds;
use pocketmine\item\WheatSeeds;
use vennv\vapm\VapmPMMP;
use vennv\vbasket\data\DataManager;
use vennv\vbasket\listener\EventListener;
use vennv\vbasket\utils\ItemUtil;
use vennv\vbasket\utils\Seeds;
use vennv\vbasket\utils\TypeVBasket;

final class VBasket extends PluginBase implements Listener
{

	private static VBasket $instance;

	public static function getInstance(): VBasket
	{
		return self::$instance;
	}

	public function onLoad(): void
	{
		self::$instance = $this;
	}

	public function onEnable(): void
	{
		VapmPMMP::init($this);

		if (!InvMenuHandler::isRegistered())
		{
			InvMenuHandler::register($this);
		}

		Seeds::$seeds_item = [
			WheatSeeds::class,
			Carrot::class,
			Potato::class,
			PumpkinSeeds::class,
			MelonSeeds::class,
			Beetroot::class
		];

		$this->getLogger()->info("Has been loaded " . count(Seeds::$seeds_item) . " seeds.");

		Seeds::$nether_wart_item = [
			ItemUtil::getItem("nether_wart")
		];

		$this->getLogger()->info("Has been loaded " . count(Seeds::$nether_wart_item) . " nether wart.");

		Seeds::$blockSeeds = [
			WheatSeeds::class => VanillaBlocks::WHEAT(),
			Carrot::class => VanillaBlocks::CARROTS(),
			Potato::class => VanillaBlocks::POTATOES(),
			PumpkinSeeds::class => VanillaBlocks::PUMPKIN_STEM(),
			MelonSeeds::class => VanillaBlocks::MELON_STEM(),
			BeetrootSeeds::class => VanillaBlocks::BEETROOTS(),

			// My hope is that this will work, but I'm not sure. :))
			ItemUtil::getItem("nether_wart")::class => VanillaBlocks::NETHER_WART()
		];

		$this->getLogger()->info("Has been loaded " . count(Seeds::$blockSeeds) . " block seeds.");

		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
	{
		if ($command->getName() == "vbasket")
		{
			if (isset($args[0]))
			{
				if ($args[0] == "give")
				{
					if (!isset($args[1]))
					{
						return false;
					}
					else
					{
						if (!isset($args[2]) || !isset($args[3]))
						{
							return false;
						}
						else
						{
							if (!is_numeric($args[3]))
							{
								$sender->sendMessage("Amount must be a number");
								return true;
							}

							$player = $sender->getServer()->getPlayerExact($args[1]);
							if ($player == null)
							{
								$sender->sendMessage("Player not found");
								return true;
							}
							else
							{
								$type = $args[2];

								if ($type == "seeds")
								{
									$type = TypeVBasket::SEEDS;
								}
								else if ($type == "nether_wart")
								{
									$type = TypeVBasket::NETHER_WART;
								}
								else
								{
									$sender->sendMessage("Types: seeds, nether_wart");
									return false;
								}

								DataManager::giveVBasket($player, $type, (int) $args[3]);
							}
						}
					}
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}

		return true;
	}

}