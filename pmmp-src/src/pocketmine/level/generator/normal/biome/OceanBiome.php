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

use pocketmine\block\Sapling;
use pocketmine\level\generator\populator\TallGrass;
use pocketmine\level\generator\populator\Setter;
use pocketmine\level\generator\populator\Tree;
use pocketmine\block\Block;

class OceanBiome extends GrassyBiome{

	public function __construct(){
		parent::__construct();

		$this->setGroundCover([
			Block::get(237, 9),
			Block::get(237, 9),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
			Block::get(168, 0),
		]);
		
		//$tallGrass = new TallGrass();
		//$tallGrass->setBaseAmount(5);

		$setter = new Setter(38, 1);
		$setter->setBaseAmount(5);

		$this->addPopulator($setter);

		$trees = new Tree([Block::WOOD2, Block::ICE, Sapling::SPRUCE]);
		$trees->setBaseAmount(1);
		$this->addPopulator($trees);

		$this->setElevation(15, 95);

		$this->temperature = 0.5;
		$this->rainfall = 0.5;
	}

	public function getName(){
		return "Ocean";
	}
}