<?php
namespace Eard\Quests\Level2;

use Eard\Quests\Quest;
use pocketmine\item\Item;

class mission7 extends Quest{
	const QUESTID = 7;
	const NORM = 10;
	public $achievement = 0;

	public static function getName(){
		return "焼けば金なり";
	}

	public static function getDescription(){
		return "うちのかまどが壊れてしまってね...\n工房用かまどを作るために上質な石が必要なんだ";
	}

	public static function getQuestType(){
		return self::TYPE_DELIVERY;
	}

	public static function getTarget(){
		return [Item::STONE, 0];
	}

	public static function getReward(){
		return Item::get(Item::GOLDEN_NUGGET, 0, 5);
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