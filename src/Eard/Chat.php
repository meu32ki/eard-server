<?php
namespace Eard;

# Basic
use pocketmine\Player;
use pocketmine\Server;



class Chat {

	/**
	 * Playerから指定半径内にいるプレイヤーを探す
	 *
	 * @param Player $player
	 * @return Array Player[]
	 */
	public static function searchTarget($player){
		// From PileUp
		$d = 30;//探す範囲
		$e = [];
		foreach($player->getLevel()->getPlayers() as $ent){//同じレベルからプレイヤー全員分取得
			$dis = sqrt(pow($player->x-$ent->x, 2)+pow($player->y-$ent->y, 2)+pow($player->z-$ent->z, 2));
			if($d > $dis && $ent->isAlive()){
				$e[] = $ent;
			}
		}
		return $e;
	}

	public static function chat($player, $e){
		$playerData = Account::get($player);
		$chatmode = $playerData->getChatMode();
		switch($chatmode){
			case self::CHATMODE_VOICE:
				$e->setCancelled(true);
				if($targets = self::searchTarget($player)){
					//じぶんもふくめてsearchTargetしている
					$msg = self::Format($player->getDisplayName(), "§f周囲", "§f".$e->getMessage());
					foreach($targets as $e){
						$e->sendMessage($msg);
					}
				}else{
					$msg = self::Format("§8システム", "周囲に誰もいません: ".$e->getMessage());
					$player->sendMessage($msg);
				}
			break;
			case self::CHATMODE_ALL:
				$msg = self::Format($player->getDisplayName(), "§f全体", $e->getMessage());
				$e->setMessage($msg);
			break;
			case self::CHATMODE_PLAYER:

			break;
			case self::CHATMODE_ENTER:

			break;
		}
	}

	public static function Format($arg1, $arg2 = "", $arg3 = ""){
		$out = "{$arg1} §7>";
		if(!$arg3){
			$out .= " {$arg2}";
		}else{
			$out .= " {$arg2} §7> {$arg3}";
		}
		return $out;
	}

	
	public static function getJoinMessage($name){
		return self::Format("§8システム", "{$name} がEardにやって来た");
	}
	public static function getQuitMessage($name){
		return self::Format("§8システム", "{$name} が地球へ戻っていった");
	}

	const CHATMODE_VOICE = 1;//30マスいない
	const CHATMODE_ALL = 2;//全体
	const CHATMODE_PLAYER = 3; //tell
	const CHATMODE_ENTER = 4;//文字打ち込み中
}