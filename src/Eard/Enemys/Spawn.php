<?php
namespace Eard\Enemys;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\Task;
use pocketmine\level\generator\normal\eardbiome\Biome;

# Eard
use Eard\Event\Time;
use Eard\MeuHandler\Account;
use Eard\Quests\Quest;


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
			EnemyRegister::TYPE_MANGLER => false,
			EnemyRegister::TYPE_LAYZER => false,
			EnemyRegister::TYPE_STINGER => true,
			EnemyRegister::TYPE_ARI => true,
			EnemyRegister::TYPE_HANEARI => true,
			EnemyRegister::TYPE_KAMADOUMA => true,
			EnemyRegister::TYPE_UNAGI => true,
			EnemyRegister::TYPE_BURIKI => true,
			EnemyRegister::TYPE_GINMEKKI => true,
			EnemyRegister::TYPE_KINMEKKI => true,
			EnemyRegister::TYPE_MUKURO_TONBO => false,
			EnemyRegister::TYPE_UMIMEDAMA => false,
			EnemyRegister::TYPE_AYZER => false,
			EnemyRegister::TYPE_KUMO => true,
			EnemyRegister::TYPE_REIZOUKO => false,
			EnemyRegister::TYPE_KAMAKIRI => false,
			EnemyRegister::TYPE_SENTAKUKI => false
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

	public function onRun(int $tick){
		Time::timeSync();
		Time::timeSignal();

		$level = Server::getInstance()->getDefaultLevel();
		$time = $level->getTime();
		$isNight = ($time%24000 >= 14000);
		$weather = $level->getWeather()->getWeather();
		$list = self::$wihts[$this->spawnType];
		$plst = Server::getInstance()->getOnlinePlayers();
		shuffle($plst);
		if(!isset($plst[0])){
			return false;
		}
		$player = $plst[0];
		$c = count($plst);
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
			$quest = Account::get($player)->getNowQuest();
			if($quest !== null && $quest::getQuestType() === Quest::TYPE_SUBJUGATION && $type === $quest::getTarget()){
				$rate /= 2;
			}
			$rate /= $c/($c+1);
			$rate = ceil($rate);
			if(!isset($class::getBiomes()[$biome]) || (!$boolean && !($isNight || $biome === Biome::END)) || mt_rand(0, $rate) !== 1){
				continue;
			}else if(isset(Humanoid::$noRainBiomes[$biome]) || $weather > 2 || $weather < 1){
				$y = $level->getHighestBlockAt($x, $z);
				if(!$class::spawnGround()){
					$y = 0;
					for(;;){
						if($y > 48){
							$y = -1;
							break;
						}
						$id = $level->getBlockIdAt($x, $y, $z);
						++$y;
						$id2 = $level->getBlockIdAt($x, $y, $z);
						if($id !== 0 && $id2 == 0){
							$y += 2;
							break;
						}
					}
				}else{
					$y += 2;
				}
				if($y !== -1){
					EnemyRegister::summon($level, $type, $x+0.5, $y, $z+0.5);
				}
			}
		}
	}
}