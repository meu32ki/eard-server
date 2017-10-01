<?php
namespace Eard\Quests\Level3;

use Eard\Quests\Quest;
use pocketmine\item\Item;

class mission13 extends Quest{
	const QUESTID = 13;
	const NORM = 6;
	public $achievement = 0;

	public static function getName(){
		return "世界一危険な調理台";
	}

	public static function getDescription(){
		return "美味しいチャーハンを作るための調理台が必要なんだぜ！\n材料になるマグマブロックの調達をお願いしたいんだぜ！";
	}

	public static function getQuestType(){
		return self::TYPE_DELIVERY;
	}

	public static function getTarget(){
		return [Item::MAGMA, 0];
	}

	public static function getReward(){
		return Item::get(Item::PUMPKIN_SEEDS, 0, 32);
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