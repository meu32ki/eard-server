<?php
namespace Eard\Quests\Level2;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission5 extends Quest{
	const QUESTID = 5;
	const NORM = 1;
	public $achievement = 0;

	public static function getName(){
		return "フィッシングルッキング";
	}

	public static function getDescription(){
		return "生活区域の釣り人から、明らかに魚ではないものが釣れるとの報告がありました。\n調査にご協力をお願いします。";
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
		return EnemyRegister::TYPE_UMIMEDAMA;
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