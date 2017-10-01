<?php
namespace Eard\Quests\Level3;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission9 extends Quest{
	const QUESTID = 9;
	const NORM = 5;
	public $achievement = 0;

	public static function getName(){
		return "空色の編隊飛行";
	}

	public static function getDescription(){
		return "資源区域にて、空色に光るウィットが発見されたようです。\n至急調査をお願いします。";
	}

	public static function getQuestType(){
		return self::TYPE_SUBJUGATION;
	}

	public static function getTarget(){
		return EnemyRegister::TYPE_MANGLER;
	}

	public static function getReward(){
		return 2000;
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