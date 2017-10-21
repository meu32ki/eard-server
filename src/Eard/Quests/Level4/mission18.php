<?php
namespace Eard\Quests\Level4;

use Eard\Quests\Quest;
use pocketmine\item\Item;
use pocketmine\item\Potion;

class mission18 extends Quest{
	const QUESTID = 18;
	const NORM = 5;
	public $achievement = 0;

	public static function getName(){
		return "探し物は魚のペット";
	}

	public static function getDescription(){
		return "私の歯科でクマノミを飼いたいんだ\n5匹ほど捕まえてきてくれないか？";
	}

	public static function getQuestType(){
		return self::TYPE_DELIVERY;
	}

	public static function getTarget(){
		return [Item::CLOWN_FISH, 0];
	}

	public static function getReward(){
		return Item::get(Item::BLAZE_ROD, 0, 5);
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