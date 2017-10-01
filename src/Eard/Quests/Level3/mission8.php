<?php
namespace Eard\Quests\Level3;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission8 extends Quest{
	const QUESTID = 8;
	const NORM = 5;
	public $achievement = 0;

	public static function getName(){
		return "止まれない赤信号";
	}

	public static function getDescription(){
		return "資源区域にて、赤い光を放つ不気味なウィットが見つかったようです。\n至急調査をお願いします。";
	}

	public static function getQuestType(){
		return self::TYPE_SUBJUGATION;
	}

	public static function getTarget(){
		return EnemyRegister::TYPE_LAYZER;
	}

	public static function getReward(){
		return 1600;
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