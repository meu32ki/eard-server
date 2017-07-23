<?php

namespace Eard\Enemys;

use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;
use pocketmine\level\Position;
//use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\MobSpawnParticle;
use pocketmine\level\particle\RedstoneParticle;

class EnemySpawn extends Task{
	const TYPE_NOEFFECT = 0;
	const TYPE_COMMON = 1;
	const TYPE_MIDDLE = 2;
	const TYPE_LARGE = 3;
	const TIME = 20;

	public static function call($class, Position $position, $type = self::TYPE_COMMON){
		Server::getInstance()->getScheduler()->scheduleRepeatingTask(new EnemySpawn($class, $position, $type), 2);
	}

	public function __construct($class, $position, $type){
		$this->class = $class;
		$this->position = $position;
		$this->type = $type;
		$this->size = $class::getSize();
		$this->particle = [];
		$this->count = 0;
		for ($yaw = 0; $yaw < 360; $yaw += M_PI/$this->size*3) { 
			for ($pitch = 0; $pitch < 360; $pitch += M_PI/$this->size*3) {
				$rad_y = deg2rad($yaw);
				$rad_p = deg2rad($pitch-180);
				$dis = mt_rand(0, self::TIME-1);
				$d = (23-$dis)/10;
				$p = clone $position;
				$p->x += sin($rad_y)*cos($rad_p)*$this->size*2*$d;
				$p->y += sin($rad_p)*$this->size*2*$d;
				$p->z += -cos($rad_y)*cos($rad_p)*$this->size*2*$d;
				if($dis > 12){
					$this->particle[$dis][] = new RedstoneParticle($p, 2);
				}else{
					$this->particle[$dis][] = new CriticalParticle($p, 1);
				}
			}
		}
	}

	public function onRun($tick){
		$level = $this->position->getLevel();
		foreach ($this->particle[$this->count] as $key => $value) {
			$level->addParticle($value);
		}
		$this->count++;
		if($this->count === self::TIME){
			$this->class::summon($level, $this->position->x, $this->position->y, $this->position->z);
			$level->addParticle(new MobSpawnParticle($this->position, $this->size, $this->size));
			Server::getInstance()->getScheduler()->cancelTask($this->getTaskId());
		}
	}
}