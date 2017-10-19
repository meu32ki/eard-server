<?php
namespace Eard\Quests\Level4;

use Eard\Quests\Quest;
use pocketmine\item\Item;
use pocketmine\item\Potion;

class mission21 extends Quest{
	const QUESTID = 21;
	const NORM = 15;
	public $achievement = 0;

	public static function getName(){
		return "紙面疎過";
	}

	public static function getDescription(){
		return "ピンチだ...とてつもなくピンチだ...\n詳しくは言えないが、紙が無いせいで狭い個室から出られないでいる...\n頼むよ...紙を...紙をくれ......";
	}

	public static function getQuestType(){
		return self::TYPE_DELIVERY;
	}

	public static function getTarget(){
		return [Item::PAPER, 0];
	}

	public static function getReward(){
		return Item::get(Item::ENDER_PEARL, 0, 5);
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