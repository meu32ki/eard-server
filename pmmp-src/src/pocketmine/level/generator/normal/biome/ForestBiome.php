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
use pocketmine\level\generator\populator\Tree;
use pocketmine\level\generator\populator\Setter;

class ForestBiome extends GrassyBiome{

	const TYPE_NORMAL = 0;
	const TYPE_BIRCH = 1;

	public $type;

	public function __construct($type = self::TYPE_NORMAL){
		parent::__construct();

		$this->type = $type;

		$trees1 = new Tree(Sapling::OAK);
		$trees1->setBaseAmount(3);
		$this->addPopulator($trees1);
		$trees2 = new Tree(Sapling::SPRUCE);
		$trees2->setBaseAmount(3);
		$this->addPopulator($trees2);
		$trees3 = new Tree(Sapling::BIRCH);
		$trees3->setBaseAmount(3);
		$this->addPopulator($trees3);
		$trees4 = new Tree(Sapling::JUNGLE);
		$trees4->setBaseAmount(3);
		$this->addPopulator($trees4);
		$trees5 = new Tree(Sapling::ACACIA);
		$trees5->setBaseAmount(3);
		$this->addPopulator($trees5);
		$trees6 = new Tree(Sapling::DARK_OAK);
		$trees6->setBaseAmount(3);
		$this->addPopulator($trees6);

		$tallGrass = new TallGrass();
		$tallGrass->setBaseAmount(20);

		$this->addPopulator($tallGrass);

		$setter1 = new Setter(39, 0);
		$setter1->setBaseAmount(3);
		$this->addPopulator($setter1);

		$setter2 = new Setter(40, 0);
		$setter2->setBaseAmount(3);
		$this->addPopulator($setter2);

		//$this->setElevation(63, 81);
		$this->setElevation(24, 110);

		if($type === self::TYPE_BIRCH){
			$this->temperature = 0.6;
			$this->rainfall = 0.5;
		}else{
			$this->temperature = 0.7;
			$this->rainfall = 0.8;
		}
	}

	public function getName(){
		return $this->type === self::TYPE_BIRCH ? "Birch Forest" : "Forest";
	}
}