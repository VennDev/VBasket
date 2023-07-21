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

namespace vennv\vbasket\utils;

use pocketmine\entity\Location;
use pocketmine\math\Vector3;

final class MathUtil
{

	public static function getNextBlockByInteract(Location $location, Vector3 $blockVector, int $distance = 0): Vector3
	{
		$distance += 2;

		$xFrom = $location->getX();
		$zFrom = $location->getZ();
		$yawFrom = $location->getYaw();
		$pitchFrom = $location->getPitch();

		$yawRad = deg2rad($yawFrom);
		$pitchRad = deg2rad($pitchFrom);

		$directionX = -sin($yawRad) * cos($pitchRad);
		$directionZ = cos($yawRad) * cos($pitchRad);

		$nextBlockX = $xFrom + $distance * $directionX;
		$nextBlockZ = $zFrom + $distance * $directionZ;

		return new Vector3($nextBlockX, $blockVector->getY(), $nextBlockZ);
	}

}