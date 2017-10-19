<?php
namespace Eard\Quests\Level4;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission16 extends Quest{
	const QUESTID = 16;
	const NORM = 1;
	public $achievement = 0;

	public static function getName(){
		return "浮きも垂らせば";
	}

	public static function getDescription(){
		return "資源区域にて、釣り人が非常に強力なウィットを釣り上げたとの情報が入りました。\n安全確保のため討伐してください。";
	}

	public static function getQuestType(){
		return self::TYPE_SUBJUGATION;
	}

	public static function getTarget(){
		return EnemyRegister::TYPE_UNAGI;
	}

	public static function getReward(){
		return 4000;
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