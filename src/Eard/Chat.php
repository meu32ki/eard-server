<?php
namespace Eard;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;


class Chat {

	/**
	 * Playerから指定半径内にいるプレイヤーを探す
	 * @param Player $player
	 * @return Array Player[]
	 */
	public static function searchTarget($player){
		// From PileUp
		$d = 30;//探す範囲
		$e = [];
		foreach($player->getLevel()->getPlayers() as $ent){//同じレベルからプレイヤー全員分取得
			$dis = sqrt(pow($player->x-$ent->x, 2)+pow($player->y-$ent->y, 2)+pow($player->z-$ent->z, 2));
			if($d > $dis && $ent->isAlive() && $ent !== $player){
				$e[] = $ent;
			}
		}
		return $e;
	}

	/**
	*	PlayerChatEventからの処理が直でやってくる。チャットの全処理をここで行う。
	*	@param string | プレイヤー名
	*	@return string | 最終的にできた参加時メッセージ 
	*/
	public static function chat($player, $e){
		$playerData = Account::get($player);
		$chatmode = $playerData->getChatMode();
		$msg = "";
		$consoleMsg = "";
		
		$e->setCancelled(true);
		switch($chatmode){
			case self::CHATMODE_VOICE:
				if($targets = self::searchTarget($player)){
					//じぶんもふくめてsearchTargetしている
					$msg = self::Format($player->getDisplayName(), "§a周囲", $e->getMessage());
					$consoleMsg = $msg;
					foreach($targets as $e){
						$e->sendMessage($msg);
					}
				}else{
					$msg = self::Format("§8システム", "周囲に誰もいません: ".$e->getMessage());
					$consoleMsg = self::Format($player->getDisplayName(), "§8システム", "周囲に誰もいません: ".$e->getMessage());
					$player->sendMessage($msg);
				}
				break;
			case self::CHATMODE_ALL:
				$msg = self::Format($player->getDisplayName(), "§b全体", $e->getMessage());
				Server::getInstance()->broadcastMessage($msg);
				break;
			case self::CHATMODE_PLAYER:
				$target = $playerData->getChatTarget();
				if($target && $target->isOnline()){
					//指定したターゲットがいまだイオンラインであったら
					$sendermsg = self::Format($player->getDisplayName(), "§6".$target->getDisplayName(), $e->getMessage());
					$consoleMsg = $sendermsg;
					$targetmsg = self::Format($player->getDisplayName(), "§6個人", $e->getMessage());
					$target->sendMessage($targetmsg);
					$player->sendMessage($sendermsg);
				}else{
					// すでにログアウトしていたら
					$msg = self::Format("§8システム", "§c指定の相手はいないようです。");
					$consoleMsg = self::Format($player->getDisplayName(), "§8システム", "§c指定の相手はいないようです。");
					$player->sendMessage($msg);
					$playerData->setChatMode(self::CHATMODE_VOICE); //きりかえ
				}
				break;
			case self::CHATMODE_ENTER:
				$msg = self::Format($player->getDisplayName(), "§eシステム", $e->getMessage());
				$consoleMsg = $msg;
				$player->sendMessage($msg);
				$obj = $playerData->getChatObject()->Chat($player, $e->getMessage());

				break;
		}
		if($consoleMsg) MainLogger::getLogger()->info($consoleMsg);
	}

	/**
	*	@param string | 発信者の名前
	*	@param string | 対象者 or message
	*	@param string | message
	*	@return string | 最終的に送るメッセージ 
	*/
	public static function Format($arg1, $arg2 = "", $arg3 = ""){
		$out = "{$arg1} §7>";
		if(!$arg3){
			$out .= " {$arg2}";
		}else{
			$out .= " {$arg2} §7> {$arg3}";
		}
		return $out;
	}

	/**
	*	@param string | message
	*	@return string | 最終的に送るメッセージ 
	*/
	public static function System($arg1){
		$out = "システム §7>　{$arg1}";
		return $out;
	}

	/**
	*	@param string | プレイヤー名
	*	@return string | 最終的にできた参加時メッセージ
	*/
	public static function getJoinMessage($name){
		return self::Format("§8システム", "{$name} がEardにやって来た");
	}

	/**
	*	@param string | プレイヤー名
	*	@return string | 最終的にできた退出時メッセージ
	*/
	public static function getQuitMessage($name){
		return self::Format("§8システム", "{$name} が地球へ戻っていった");
	}

	const CHATMODE_VOICE = 1;//30マスいない
	const CHATMODE_ALL = 2;//全体
	const CHATMODE_PLAYER = 3; //tell
	const CHATMODE_ENTER = 4;//設備などに文字打ち込み中
}