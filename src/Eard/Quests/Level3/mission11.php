<?php
namespace Eard\Quests\Level3;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission11 extends Quest{
	const QUESTID = 11;
	const NORM = 3;
	public $achievement = 0;

	public static function getName(){
		return "逃げるが価値";
	}

	public static function getDescription(){
		return "資源区域にて、高速で逃げる茶色いウィットが発見されたようです。\n至急調査をお願いします。";
	}

	public static function getQuestType(){
		return self::TYPE_SUBJUGATION;
	}

	public static function getTarget(){
		return EnemyRegister::TYPE_BURIKI;
	}

	public static function getReward(){
		return 1000;
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