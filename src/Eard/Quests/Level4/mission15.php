<?php
namespace Eard\Quests\Level4;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission15 extends Quest{
	const QUESTID = 15;
	const NORM = 5;
	public $achievement = 0;

	public static function getName(){
		return "ホラアナ管弦楽団";
	}

	public static function getDescription(){
		return "資源区域にて、バッタに似た地下性ウィットが確認されました。\n攻撃がバッタに比べて熾烈だとの情報が入っています。十分注意して討伐してください。";
	}

	public static function getQuestType(){
		return self::TYPE_SUBJUGATION;
	}

	public static function getTarget(){
		return EnemyRegister::TYPE_KAMADOUMA;
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