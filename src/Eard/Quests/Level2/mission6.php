<?php
namespace Eard\Quests\Level2;

use Eard\Quests\Quest;
use pocketmine\item\Item;

class mission6 extends Quest{
	const QUESTID = 6;
	const NORM = 5;
	public $achievement = 0;

	public static function getName(){
		return "釣果上々";
	}

	public static function getDescription(){
		return "今日は釣りをしていたんだが、何故かサーモンだけ全く釣れないんだ...\n頼む！釣ってきてくれ！";
	}

	public static function getQuestType(){
		return self::TYPE_DELIVERY;
	}

	public static function getTarget(){
		return [Item::RAW_SALMON, 0];
	}

	public static function getReward(){
		return Item::get(Item::GLASS_BOTTLE, 0, 3);
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