<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\level\generator\normal\biome;

use pocketmine\level\generator\populator\Setter;
use pocketmine\level\generator\populator\Cactus;

class DesertBiome extends SandyBiome{

	public function __construct(){
		parent::__construct();
		//$this->setElevation(63, 74);
		$this->setElevation(45, 100);

		$setter1 = new Setter(32, 0);
		$setter1->setBaseAmount(3);
		$this->addPopulator($setter1);

		$setter2 = new Cactus();
		$setter2->setBaseAmount(2);
		$this->addPopulator($setter2);

		$this->temperature = 2;
		$this->rainfall = 0;
	}

	public function getName(){
		return "Desert";
	}
}