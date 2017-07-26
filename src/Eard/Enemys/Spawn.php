<?php
namespace Eard\Enemys;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\Task;
use pocketmine\level\generator\biome\Biome;

class Spawn extends Task{
	public $spawnType = true;
	const SHIGEN = false;
	const SEIKATSU = true;

	/*
	type => can spawn day and night(true or false),...
	*/
	public static $wihts = [
		self::SHIGEN => [
			EnemyRegister::TYPE_HOPPER => true,
			EnemyRegister::TYPE_CROSSER => false,
		],
		self::SEIKATSU => [
			EnemyRegister::TYPE_HOPPER => false,
		]
	];

	public static function init($spawnType){
		$task = new Spawn($spawnType);
		Server::getInstance()->getScheduler()->scheduleRepeatingTask($task, 20);
	}

	public function __construct($spawnType){
		$this->spawnType = $spawnType;
	}

	public function onRun($tick){
		$level = Server::getInstance()->getDefaultLevel();
		$time = $level->getTime();
		$isNight = ($time%24000 >= 13700);
		$weather = $level->getWeather()->getWeather();
		$list = self::$wihts[$this->spawnType];
		foreach (Server::getInstance()->getOnlinePlayers() as $key => $player) {
			foreach ($list as $type => $boolean) {
				$class = EnemyRegister::getClass($type);
				$yaw = mt_rand(0, 360);
				$rad = deg2rad($yaw);
				$x = (int) $player->x - sin($rad)*mt_rand(25, 35);
				$z = (int) $player->z + cos($rad)*mt_rand(25, 35);
				$biome = $level->getBiomeId($x, $z);
				$rate = $class::getSpawnRate();
				if(!$isNight){
					$rate *= 2;
				}
				if(!isset($class::getBiomes()[$biome]) || (!$boolean && !($isNight || $biome === Biome::END)) || mt_rand(0, $rate) !== 1){
					continue;
				}else if(isset(Humanoid::$noRainBiomes[$biome]) || $weather > 2 || $weather < 1){
					$y = $level->getHighestBlockAt($x, $z)+2;
					EnemyRegister::summon($level, $type, $x+0.5, $y, $z+0.5);
				}
			}
		}
	}
}