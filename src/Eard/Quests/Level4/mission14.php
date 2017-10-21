<?php
namespace Eard\Quests\Level4;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission14 extends Quest{
	const QUESTID = 14;
	const NORM = 7;
	public $achievement = 0;

	public static function getName(){
		return "骨になっても肉が好き";
	}

	public static function getDescription(){
		return "トンボの変位個体と見られるウィットが、砂漠及び湿地にて確認されました。\n情報の少ないウィットですので慎重に行動してください。";
	}

	public static function getQuestType(){
		return self::TYPE_SUBJUGATION;
	}

	public static function getTarget(){
		return EnemyRegister::TYPE_MUKURO_TONBO;
	}

	public static function getReward(){
		return 4500;
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