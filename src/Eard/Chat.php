<?php
namespace Eard;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;


class Chat {

	/**
	 * Playerから指定半径内にいるプレイヤーを探す。複数いる場合は複数返す。
	 * @param Player $player
	 * @return Player[]
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
	*	@param PlayerChatEvent 
	*	@return bool | チャットを表示できるかできないか…boolを返す意味がいまいち 20170709
	*/
	public static function chat($player, $e){
		$playerData = Account::get($player);
		$chatmode = $playerData->getChatMode();
		$msg = "";
		$consoleMsg = "";
		

		// ターゲット選択楽々チャット
		$message = $e->getMessage();
		$start = substr($message, 0, 1);
		if( $start === "@" ){
			$posblank = strpos($message," ");
			$targetName = substr($message, 1, $posblank - 1);
			$message = substr($message, $posblank + 1, 100);
			//echo "posblank: {$posblank}, targetname: {$targetName}, message: {$message}\n";
			if($targetName === "a"){
				// @a
				$chatmode = self::CHATMODE_ALL;
			}else{
				// @32ki とか @32ki,wakame,m0
				$targetNames = explode(",", $targetName);
				//$targetNamesの中身は array かもしれないし string かもしれない
				if(1 < count($targetNames)){
					$chatmode = self::CHATMODE_PLAYERS;
				}else{
					$chatmode = self::CHATMODE_PLAYER;
					$target = Server::getInstance()->getPlayer($targetName);//後の処理用
				}
			}
		}elseif( $start === "/" ){
			//コマンドなので、表示しない
			return false;
		}

		// モードに従ったチャット
		$e->setCancelled(true);
		switch($chatmode){
			// 周囲、みどりいろ
			case self::CHATMODE_VOICE:
				if($targets = self::searchTarget($player)){ // $targets = Player[]
					// 周囲に誰かいる
					$msg = self::Format($player->getDisplayName(), "§a周囲", $message);
					$consoleMsg = $msg;
					foreach($targets as $e){
						$e->sendMessage($msg);
					}
					$player->sendMessage($msg);	//じぶんもふくめてsearchTargetしていないため
				}else{
					// 周囲に誰一人いない
					$msg = self::Format($player->getDisplayName(), "§a周囲", "§8周囲に誰もいません: ".$message);
					$consoleMsg = $msg;
					$player->sendMessage($msg);
				}
				break;
			// 全体、みずいろ
			case self::CHATMODE_ALL:
				$msg = self::Format($player->getDisplayName(), "§b全体", $message);
				Server::getInstance()->broadcastMessage($msg);
				break;
			// 個人、オレンジ色
			case self::CHATMODE_PLAYER:
				if(!isset($target)) $target = $playerData->getChatTarget();
				if($target && $target->isOnline()){
					//　指定したターゲットがいまだイオンラインであったら
					$msg = self::Format($player->getDisplayName(), "§6個人(".$target->getDisplayName().")", $message);
					$consoleMsg = $msg;
					$target->sendMessage($msg);
					$player->sendMessage($msg);
				}else{
					// 指定playerがすでにログアウトしていたら
					$name = $target ? $target->getDisplayName() : $targetName;
					$msg = self::Format($player->getDisplayName(), "§6個人({$name})", "§8相手はEardにいません: ".$message);
					$consoleMsg = $msg;
					$player->sendMessage($msg);
					/*
						このいっかいだけchatmodeが変わっている可能性があるので、切りかえる際には、
						今のチャットモードとの比較をする
					*/
					if($playerData->getChatMode() == $chatmode){
						$playerData->setChatMode(self::CHATMODE_VOICE); //きりかえ
					}
				}
				break;
			// 複数人、オレンジ色
			case self::CHATMODE_PLAYERS:
				$senderName = $player->getDisplayName();
				$displayTargetNames = "";
				foreach($targetNames as $name){
					$target = Server::getInstance()->getPlayer($name);
					if($target && $target->isOnline()){
						$msg = self::Format($senderName, "§6複数", $message);
						$target->sendMessage($msg);
						$displayTargetNames .= $target->getDisplayName;
					}
				}
				if(!$displayTargetNames){
					//誰にも送信していなければ
					$message = "§8相手はEardにいません: {$message}";
					$displayTargetNames = $targetName;
				}
				$msg = self::Format($senderName, "§6複数({$displayTargetNames})", $message);
				$consoleMsg = $msg;
				$player->sendMessage($msg);
				break;
			// しすてむ、ピンク色
			case self::CHATMODE_ENTER:
				$msg = self::Format($player->getDisplayName(), "§dシステム", $message);
				$consoleMsg = $msg;
				$player->sendMessage($msg);
				$obj = $playerData->getChatObject()->Chat($player, $message);
				break;
		}
		if($consoleMsg) MainLogger::getLogger()->info($consoleMsg);
		return true;
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
	public static function SystemToPlayer($arg1){
		$out = "§8システム §7> §6個人 §7> {$arg1}";
		return $out;
	}

	public static function System($arg1, $arg2 = ""){
		$arg2 = $arg2 ? " §7> {$arg2}" : "";
		$out = "§8システム §7> {$arg1}{$arg2}";
		return $out;
	}

	/**
	*	@param string | プレイヤー名
	*	@return string | 最終的にできた参加時メッセージ
	*/
	public static function getJoinMessage($name){
		$placeName = Connection::getPlace()->getName();
		return self::System("§bお知らせ", "§f{$name} §7がEardの {$placeName} にやって来た");
	}

	/**
	*	@param string | プレイヤー名
	*	@return string | 最終的にできた退出時メッセージ
	*/
	public static function getQuitMessage($name){
		return self::System("§bお知らせ", "§f{$name} §7が地球へ戻っていった");
	}

	/**
	*	別のサバ(ワールド)へ飛ぶ場合のメッセージを取得する
	*/
	public static function getTransferMessage($name){
		if(Connection::getPlace()->isLivingArea()){
			$placeName = Connection::getPlaceByNo(2)->getName();
		}elseif(Connection::getPlace()->isResourceArea()){
			$placeName = Connection::getPlaceByNo(1)->getName();
		}
		return self::System("§bお知らせ", "{$name} はEardの {$placeName} へと向かった");
	}

	const CHATMODE_VOICE = 1;//30マスいない
	const CHATMODE_ALL = 2;//全体
	const CHATMODE_PLAYER = 3; //tell
	const CHATMODE_PLAYERS = 4; //プレイヤー複数
	const CHATMODE_ENTER = 5;//設備などに文字打ち込み中
}