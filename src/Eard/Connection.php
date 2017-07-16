<?php

namespace Eard;


# Basic
use pocketmine\utils\MainLogger;

# Packet
use pocketmine\network\protocol\TransferPacket;


class Connection {
	

	private static $urban_addr = "play.32ki.net";
	private static $urban_port = "19139";

	private static $resource_addr = "xxhunter.ddns.net";
	private static $resource_port = "19132";

	private static $place = 0; //このサバは、どちらに当たるのかを指定


	const STAT_ONLINE     = 1;
	const STAT_OFFLINE    = 2;
	const STAT_PREPAREING = 3;
	const STAT_UNKNOWN	  = 4;

/*
	プレイヤーの鯖間転送
*/

	/**
	*	生活側で使うメソッド。飛ばせるか確認の処理などをすべてこちらで行う。
	*	@return Int 	-1 ~ 1 (-1..エラー発生, 0...不一致のため入れず 1...はいれる)
	*/
	public static function goToResourceArea(Account $PlayerData){
		$player = $PlayerData->getPlayer();
		if(!$player){
			MainLogger::getLogger()->notice("§cConnection: Player not found...");
			return -1;
		}

		// この辺にインベントリを圧縮する処理?


		// このplaceがセットされているか？
		if(!self::$place){
			MainLogger::getLogger()->notice("§eConnection: Transfer has been canceled. You should set your 'place' immedeately!");
			return -1;
		}

		// 転送先が開いているかチェック


		// 実際飛ばす処理
		$pk = new TransferPacket;
		$pk->address = self::$resource_addr;
		$pk->port = self::$resource_port;
		$player->directDataPacket($pk);

		// quitが呼ばれてしまう問題の修正をせねば
	}


	/**
	*	資源側で使うメソッド。1以外が帰った場合には、ログイン不可画面を出す。
	*	@return Int 	-1 ~ 1 (-1..エラー発生, 0...不一致のため入れず 1...はいれる)
	*/
	public static function canLoginToResourceArea(Account $PlayerData){
		$player = $PlayerData->getPlayer();
		if(!$player){
			MainLogger::getLogger()->notice("§cConnection: Player not found...");
			return -1;
		}

		// 直前まで、古いほうにログインしてたか
		$result = self::isLoggedIn($name);
		if($result === 1){
			return 1;
		}else{
			if($result === -1){
				return -1;
			}else{
				return 0;
			}
		}
	}



/*
	サーバーのステータス
*/

	//初期セットアップ。データベースのセットアップ。
	public static function setup(){
		$sql = "INSERT INTO statistics_server (name, place, stat) VALUES ('せいかつ', '1', '".self::STAT_PREPAREING."'); ";
		$sql2 = "INSERT INTO statistics_server (name, place, stat) VALUES ('しげん', '2', '".self::STAT_PREPAREING."'); ";
		$db = DB::get();
		$result = $db->query($sql);
		$result2 = $db->query($sql2);
	}

	/**
	*	鯖をつけたとき、オンラインだよーって記録するやつ。
	*	placeの値が設定されていないときは、何もしない。
	*/
	public static function makeOnline(){
		$stat = self::STAT_ONLINE;
		$place = self::$place;
		if($place){
			$sql = "UPDATE statistics_server SET stat = {$stat} WHERE place = {$place}; ";
			$result = DB::get()->query($sql);
			if($result){
				MainLogger::getLogger()->notice("§aConnection: §fサーバーを「§aオンライン状態§f」と記録しました");
			}else{
				MainLogger::getLogger()->notice("§cConnection: エラー。サーバーの状態は記録されていません");
			}
		}else{
			MainLogger::getLogger()->notice("§eConnection: 値が設定されていません。サーバーの状態は記録されていません");
		}
	}

	/**
	*	鯖をけしたとき、オフラインだよーって記録するやつ。
	*	placeの値が設定されていないときは、何もしない。
	*/
	public static function makeOffline(){
		$stat = self::STAT_OFFLINE;
		$place = self::$place;
		if($place){
			$sql = "UPDATE statistics_server SET stat = {$stat} WHERE place = {$place}; ";
			$result = DB::get()->query($sql);
			if($result){
				MainLogger::getLogger()->notice("§aConnection: §fサーバーを「§cオフライン状態§f」と記録しました");
			}else{
				MainLogger::getLogger()->notice("§cConnection: エラー。サーバーの状態は記録されていません");
			}
		}else{
			MainLogger::getLogger()->notice("§eConnection: 値が設定されていません。サーバーの状態は記録されていません");
		}
	}


/*
	プレイヤーのステータス
*/

	/**
	*	そのプレイヤーがログインしたよーってことをDBに記録する。
	*	webから、オンラインかオフラインか見れるようにするため
	*	@param String 	プレイヤー名
	*	@return bool 	クエリが成功したか
	*/
	public static function recordLogin($name){
		$place = self::$place; // 場所番号/鯖番号 (生活区域 = 1, 資源区域 = 2)
		if($place){
			$sql = "INSERT INTO statistics_player (name, place, date) VALUES ('{$name}', '{$place}', now() ); ";
			$result = DB::get()->query($sql);
			return $result;
		}else{
			MainLogger::getLogger()->notice("§eConnection: Cannot record player. You should set your 'place' immedeately!");
		}
	}


	/**
	*	そのプレイヤーがログアウトしたよーってことをDBに記録する。(削除する)
	*	@param String 	プレイヤー名
	*	@param Int 		場所番号(生活区域 = 1, 資源区域 = 2)
	*	@return int 	-1 ~ 1 (-1...取得/接続不可 0...クエリ失敗 1...クエリ成功)
	*/
	public static function recordLogout($name){
		if($place){
			$sql = "DELETE FROM statistics_player WHERE name = '{$name}'; ";
			$result = DB::get()->query($sql);
			return $result;
		}
	}


	/**
	*	プレイヤーのいる場所が移った時に、記録する。Transferの場合のみなのでprivate
	*	@param String 	プレイヤー名
	*	@param Int 		場所番号(生活区域 = 1, 資源区域 = 2)
	*	@return bool 	クエリが成功したか
	*/
	private static function recordUpdate($name){
		$place = self::$place; // 場所番号/鯖番号 (生活区域 = 1, 資源区域 = 2)
		$sql = "UPDATE statistics_player SET place = {$place} WHERE name = '{$name}'; ";
		$result = DB::get()->query($sql);
		return $result;
	}


	/**
	*	webで使うだろうからpublic
	*	該当プレイヤーが、ログインしていると記録してあるかをチェック。
	*	@param String 	プレイヤー名
	*	@return Int  	-1 ~ 2 (-1...取得/接続不可 0...いない 1...生活区域 2...資源区域)
	*/
	public static function isLoggedIn($name){
		$sql = "SELECT * FROM statistics_player WHERE name = '{$name}'; ";
		$result = DB::get()->query($sql);
		if($result){
			$place = 0;
			while($row = $result->fetch_assoc()){
				$place = $row['place'];
			}
			return $place;
		}else{
			return -1;
		}
	}


/*
	クラスで使うデータ
*/

	public static function load(){
		$data = Settings::load('Connection');
		if($data){
			self::$place = $data[0];
			MainLogger::getLogger()->notice("§aConnection: data has been loaded");
		}else{
			MainLogger::getLogger()->notice("§eConnection: Cannnot load data. You should set your 'place' immedeately!");
		}
	}


	//このサーバーの場所番号を書き込み、番号をせーぶする。
	public static function write($place){
		self::$place = $place;
		//毎回セーブする必要はない。起動中に書き換わらないから。コマンドでセーブするのみ。
		$data = [self::$place];
		$result = Settings::save('Connection', $data);
		if($result){
			MainLogger::getLogger()->notice("§aConnection: data has been saved");
		}
	}
}