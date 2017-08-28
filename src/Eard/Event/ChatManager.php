<?php
namespace Eard\Event;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;

# Eard
use Eard\DBCommunication\Connection;
use Eard\MeuHandler\Account;
use Eard\Utils\Chat;


class ChatManager {
	private static $lastTime = null;
	private static $day = [
		0 => "日",
		1 => "月",
		2 => "火",
		3 => "水",
		4 => "木",
		5 => "金",
		6 => "土"
	];

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
					$msg = Chat::Format($player->getDisplayName(), "§a周囲", $message);
					$consoleMsg = $msg;
					foreach($targets as $e){
						$e->sendMessage($msg);
					}
					$player->sendMessage($msg);	//じぶんもふくめてsearchTargetしていないため
				}else{
					// 周囲に誰一人いない
					$msg = Chat::Format($player->getDisplayName(), "§a周囲", "§8周囲に誰もいません: ".$message);
					$consoleMsg = $msg;
					$player->sendMessage($msg);
				}
				break;
			// 全体、みずいろ
			case self::CHATMODE_ALL:
				$msg = Chat::Format($player->getDisplayName(), "§b全体", $message);
				Server::getInstance()->broadcastMessage($msg);
				break;
			// 個人、オレンジ色
			case self::CHATMODE_PLAYER:
				if(!isset($target)) $target = $playerData->getChatTarget();
				if($target && $target->isOnline()){
					//　指定したターゲットがいまだイオンラインであったら
					$msg = Chat::Format($player->getDisplayName(), "§6個人(".$target->getDisplayName().")", $message);
					$consoleMsg = $msg;
					$target->sendMessage($msg);
					$player->sendMessage($msg);
				}else{
					// 指定playerがすでにログアウトしていたら
					$name = $target ? $target->getDisplayName() : $targetName;
					$msg = Chat::Format($player->getDisplayName(), "§6個人({$name})", "§8相手はEardにいません: ".$message);
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
						$msg = Chat::Format($senderName, "§6複数", $message);
						$target->sendMessage($msg);
						$displayTargetNames .= $target->getDisplayName;
					}
				}
				if(!$displayTargetNames){
					//誰にも送信していなければ
					$message = "§8相手はEardにいません: {$message}";
					$displayTargetNames = $targetName;
				}
				$msg = Chat::Format($senderName, "§6複数({$displayTargetNames})", $message);
				$consoleMsg = $msg;
				$player->sendMessage($msg);
				break;
			// しすてむ、ピンク色
			case self::CHATMODE_ENTER:
				$msg = Chat::Format($player->getDisplayName(), "§dシステム", $message);
				$consoleMsg = $msg;
				$player->sendMessage($msg);
				$obj = $playerData->getChatObject()->Chat($player, $message);
				break;
		}
		if($consoleMsg) MainLogger::getLogger()->info($consoleMsg);
		return true;
	}

	/**
	 * 時報を送信
	 * @return bool | 時報を送信したかどうか
	 */
	public static function timeSignal(){
		if(self::$lastTime === null){
			self::$lastTime = getdate();//初期化
			return false;
		}
		$now = getdate();
		if($now["hours"] !== self::$lastTime["hours"]){
			if($now["hours"] === 0){//日付が変わったら
				$message = $now["mon"]."月".$now["mday"]."日(".self::$day[$now["wday"]].") ";
			}else{
				$message = $now["mon"]."月".$now["mday"]."日(".self::$day[$now["wday"]].") ";
			}
			$message .= $now["hours"]."時になりました";
			$msg = Chat::Format("§8システム", "§a時報", $message);
			Server::getInstance()->broadcastMessage($msg);
			self::$lastTime = getdate();
			return true;
		}
		self::$lastTime = getdate();
		return false;
	}

	const CHATMODE_VOICE = 1;//30マスいない
	const CHATMODE_ALL = 2;//全体
	const CHATMODE_PLAYER = 3; //tell
	const CHATMODE_PLAYERS = 4; //プレイヤー複数
	const CHATMODE_ENTER = 5;//設備などに文字打ち込み中
}