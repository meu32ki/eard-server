<?php
namespace Eard\Quests\Level1;

use Eard\Quests\Quest;
use pocketmine\item\Item;

class mission3 extends Quest{
	const QUESTID = 3;
	const NORM = 5;
	public $achievement = 0;

	public static function getName(){
		return "ナエ・フォーミング";
	}

	public static function getDescription(){
		return "植林場を作るために苗が足りないんです。\nあなたにはオークの苗の調達をお願いします。";
	}

	public static function getQuestType(){
		return self::TYPE_DELIVERY;
	}

	public static function getTarget(){
		return [Item::SAPLING, 0];
	}

	public static function getReward(){
		return Item::get(Item::BREAD, 0, 3);
	}

	public function sendReward($player){
		self::sendRewardItem($player, $this->getReward());
	}

	/*目的達成するたびに+1
	*/
	public function addAchievement(){
		return parent::addAchievement();
	}
}