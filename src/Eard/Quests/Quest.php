<?php
namespace Eard\Quests;

use Eard\Utils\Chat;

class Quest{
	public static $allQuests = [];

	const TYPE_MEU = 0;
	const TYPE_ITEM = 1;

	const QUESTID = 0;
	const NORM = 0;
	public $achievement = 0;

	const TYPE_SUBJUGATION = 1;//討伐系
	const TYPE_DELIVERY = 2;//納品系
	const TYPE_SPECIAL = 3;//特殊なやつ

	public static function getAllQuests(){
		return self::$allQuests;
	}

	public static function get(int $id, int $achievement = 0){
		if(isset(self::$allQuests[$id])){
			$class = self::$allQuests[$id];
			$quest = new $class(); 
			$quest->achievement = $achievement;
			return $quest;
		}
		return null;
	}

	public function getQuestId(){
		return static::QUESTID;
	}

	/*目的達成するたびに+1
	*/
	public function addAchievement(){
		$this->achievement++;
		if($this->checkAchievement()){
			return true;
		}else{
			return false;
		}
	}

	/*現在の達成状況を返す
	*/
	public function getAchievement(){
		return $this->achievement;
	}

	public function checkAchievement(){
		return (static::NORM <= $this->achievement);
	}

	public function sendRewardMeu($player, $amount){
		//Meu送金処理
		$player->sendMessage(Chat::SystemToPlayer("§e報酬金 {$amount}μ 獲得しました"));
	}

	public function checkDelivery($player){
		$inv = $player->getInventory();
		$delitem = static::getTarget();
		$delid = $delitem[0];
		$deldamage = $delitem[1];
		$delamount = static::getNorm();
		$contents = $inv->getContents();
		$sendc = $contents;
		foreach ($contents as $index => $item) {
			if($item->getId() === $delid && $item->getDamage() === $deldamage){
				if($delamount >= $item->getCount()){
					unset($sendc[$index]);
					$delamount -= $item->getCount();
				}else{
					$sendc[$index] = $item->setCount($item->getCount() - $delamount);
					$delamount = 0;
				}
				if($delamount === 0){
					break;
				}
			}
		}
		if($delamount === 0){
			$inv->setContents(array_values($sendc));
			return true;
		}else{
			return false;
		}
	}

	public function sendRewardItem($player, $item){
		//アイテム送信処理
		$player->sendMessage(Chat::SystemToPlayer("§6報酬アイテム送信チェック"));
	}
}