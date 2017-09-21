<?php
namespace Eard\Quests\Level1;

use Eard\Quests\Quest;

class mission1 extends Quest{
	const QUESTID = 1;
	const NORM = 1;
	public $achievement = 0;

	public function getName(){
		return "クエスト1-1";
	}

	public function getDescription(){
		return "最初のクエストだよ!";
	}

	public function getQuestType(){
		return self::TYPE_SPECIAL;
	}

	/*目的達成するたびに+1
	*/
	public function addAchievement(){
		$result = parent::addAchievement();
	}
}