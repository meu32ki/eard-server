<?php
namespace Eard\Quests\Level3;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission10 extends Quest{
	const QUESTID = 10;
	const NORM = 12;
	public $achievement = 0;

	public static function getName(){
		return "跳ねる大淘汰戦";
	}

	public static function getDescription(){
		return "バッタが大量発生したようです。\n周辺に被害が出る前に掃討してください。";
	}

	public static function getQuestType(){
		return self::TYPE_SUBJUGATION;
	}

	public static function getNorm(){
		return self::NORM;
	}

	public function getNormI(){
		return self::NORM;
	}
	
	public static function getTarget(){
		return EnemyRegister::TYPE_HOPPER;
	}

	public static function getReward(){
		return 1500;
	}

	public function sendReward($player){
		self::sendRewardMeu($player, $this->getReward());
	}

	/*目的達成するたびに+1
	*/
	public function addAchievement(){
		return parent::addAchievement();
	}
}