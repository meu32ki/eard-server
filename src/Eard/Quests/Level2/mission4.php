<?php
namespace Eard\Quests\Level2;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission4 extends Quest{
	const QUESTID = 4;
	const NORM = 3;
	public $achievement = 0;

	public static function getName(){
		return "打ち上げ鋏";
	}

	public static function getDescription(){
		return "森林を探索中、「クワガタ」と呼ばれるウィットに遭遇しました。\n危険なウィットのため、排除を依頼します。";
	}

	public static function getQuestType(){
		return self::TYPE_SUBJUGATION;
	}

	public static function getTarget(){
		return EnemyRegister::TYPE_CROSSER;
	}

	public static function getReward(){
		return 500;
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