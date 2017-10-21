<?php
namespace Eard\Quests\Level4;

use Eard\Quests\Quest;
use pocketmine\item\Item;
use pocketmine\item\Potion;

class mission19 extends Quest{
	const QUESTID = 19;
	const NORM = 64;
	public $achievement = 0;

	public static function getName(){
		return "砂利夢中";
	}

	public static function getDescription(){
		return "私は今ッ！！！！！砂利に夢中である！！！！！！！！！！\n粗さと細かさが混じる絶妙な手触りッ！！！！！\n耳が孕むような破壊音ッ！！！！！！！！！！！\n...........気が付いたら全て火打ち石になっていたんだ...砂利を...砂利をくれ..........。";
	}

	public static function getQuestType(){
		return self::TYPE_DELIVERY;
	}

	public static function getTarget(){
		return [Item::GRAVEL, 0];
	}

	public static function getReward(){
		return Item::get(Item::BOOK, 0, 15);
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