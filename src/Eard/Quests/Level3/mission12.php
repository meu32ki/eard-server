<?php
namespace Eard\Quests\Level3;

use Eard\Quests\Quest;
use pocketmine\item\Item;
use pocketmine\item\Potion;

class mission12 extends Quest{
	const QUESTID = 12;
	const NORM = 1;
	public $achievement = 0;

	public static function getName(){
		return "調合士認定試験初級";
	}

	public static function getDescription(){
		return "調合の技能があるかどうかテストします。\n「回復のポーション」を作成し、納品してください。";
	}

	public static function getQuestType(){
		return self::TYPE_DELIVERY;
	}

	public static function getTarget(){
		return [Item::POTION, Potion::HEALING];
	}

	public static function getReward(){
		return Item::get(Item::CAKE, 0, 1);
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