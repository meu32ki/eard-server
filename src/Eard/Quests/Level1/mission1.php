<?php
namespace Eard\Quests\Level1;

use Eard\Quests\Quest;
use Eard\Enemys\EnemyRegister;

class mission1 extends Quest{
	const QUESTID = 1;
	const NORM = 3;
	public $achievement = 0;

	public static function getName(){
		return "蝗の一跳ね";
	}

	public static function getDescription(){
		return "最近バッタが増えてきてうるさいんだよね～\n退治してくれないかな？";
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

	/*目的達成するたびに+1
	*/
	public function addAchievement(){
		return parent::addAchievement();
	}
}