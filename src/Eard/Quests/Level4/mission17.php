<?php
namespace Eard\Quests\Level4;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission17 extends Quest{
	const QUESTID = 17;
	const NORM = 1;
	public $achievement = 0;

	public static function getName(){
		return "鉄学も甘くない";
	}

	public static function getDescription(){
		return "資源区域にて、銀色のブリキを見かけたとの情報が入りました。\n新種の可能性が高いため、調査をお願いします。";
	}

	public static function getQuestType(){
		return self::TYPE_SUBJUGATION;
	}

	public static function getTarget(){
		return EnemyRegister::TYPE_GINMEKKI;
	}

	public static function getReward(){
		return 5000;
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