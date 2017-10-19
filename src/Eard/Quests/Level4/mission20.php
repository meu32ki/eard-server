<?php
namespace Eard\Quests\Level4;

use Eard\Quests\Quest;
use pocketmine\item\Item;
use pocketmine\item\Potion;

class mission20 extends Quest{
	const QUESTID = 20;
	const NORM = 4;
	public $achievement = 0;

	public static function getName(){
		return "ハロウィンデリバリー";
	}

	public static function getDescription(){
		return "ハロウィンパーティーをしようと思ったんだけど、肝心のパンプキンパイを作り忘れてたのよ。\n誰か届けてくれないかしら？";
	}

	public static function getQuestType(){
		return self::TYPE_DELIVERY;
	}

	public static function getTarget(){
		return [Item::PUMPKIN_PIE, 0];
	}

	public static function getReward(){
		return Item::get(Item::ENCHANTING_TABLE, 0, 1);
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