<?php

namespace Eard;


# Basic
use pocketmine\utils\MainLogger;


class Connection {
	
	//placeをオブジェクトごとに分ける作業 2017/7/19

	private static $urban_addr = "";
	private static $urban_port = "";

	private static $resource_addr = "";
	private static $resource_port = "";

	private static $placeNo = 0; //このサバは、どちらに当たるのかを指定
	private static $places = [];

/*	プレイヤーの鯖間転送
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
		if(!self::$placeNo){
			MainLogger::getLogger()->notice("§eConnection: Transfer has been canceled. You should set your 'placeNo' immedeately!");
			return -1;
		}

		// 転送先が開いているかチェック



		// 転送モードに移行、これをいれると、quitの時のメッセージが変わる
		$PlayerData->setNowTransfering(true);

		// 実際飛ばす処理
		$player->transfer(self::$resource_addr, self::$resource_port); // あらゆる処理の最後に持ってくるべき
		//$PlayerData->setNowTransfering(false);
	
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


/*	全般
*/

	/**
	*	今空いている、このさばの、placeを返す。webからは使うな。
	*	@return Place
	*/
	public static function getPlace(){
		return self::$places[self::$placeNo];
	}

	/**
	*	placeの番号からPlaceを取得する。
	*	@param int placeNo
	*	@return Place
	*/
	public static function getPlaceByNo($placeNo){
		return self::$places[$placeNo];
	}

	/**
	*	初期セットアップ。データベースのセットアップ。
	*/
	public static function setup(){
		$sql = "INSERT INTO statistics_server (name, place, stat) VALUES ('せいかつ', '1', '".self::STAT_PREPAREING."'); ";
		$sql2 = "INSERT INTO statistics_server (name, place, stat) VALUES ('しげん', '2', '".self::STAT_PREPAREING."'); ";
		$db = DB::get();
		$result = $db->query($sql);
		$result2 = $db->query($sql2);
	}

/*	プレイヤーのステータス
*/

	/**
	*	webで使うだろうからpublic
	*	該当プレイヤーが、ログインしていると記録してあるかをチェック。どちらかのサバに？
	*	@param String 	プレイヤー名
	*	@return Int  	-1 ~ 2 (-1...取得/接続不可 0...いない 1...生活区域 2...資源区域)
	*/
	public static function isLoggedIn($name){
		$sql = "SELECT * FROM statistics_player WHERE name = '{$name}'; ";
		$result = DB::get()->query($sql);
		if($result){
			$placeNo = 0;
			while($row = $result->fetch_assoc()){
				$placeNo = $row['place'];
			}
			return $placeNo;
		}else{
			return -1;
		}
	}


/*	クラスで使うデータ
*/

	public static function load(){
		$data = Settings::load('Connection');
		if($data){
			self::$placeNo = (int) $data[0];
			MainLogger::getLogger()->notice("§aConnection: place data has been loaded");
		}else{
			MainLogger::getLogger()->notice("§eConnection: Cannnot load place data. You should set your 'connection place' immedeately!");
		}

		self::$places[1] = new Place(1);
		self::$places[2] = new Place(2);

		$data = Settings::load('ConnectionAddr');
		if($data){

			$living = self::getPlaceByNo(1);
			$living->setAddr($data[0]);
			$living->setPort($data[1]);

			$resource = self::getPlaceByNo(2);
			$resource->setAddr($data[2]);
			$resource->setPort($data[3]);

			$flag = true;
			if( !$living->getAddr() || !$living->getPort() ){
				$flag = false;
				MainLogger::getLogger()->notice("§eConnection: addr data has been loaded, but it seems LIVING value is empty. It'll no longer work properly!");
			}
			if( !$resource->getAddr() || !$resource->getPort() ){
				$flag = false;
				MainLogger::getLogger()->notice("§eConnection: addr data has been loaded, but it seems RESOURCE value is empty. It'll no longer work properly!");
			}

			if($flag) MainLogger::getLogger()->notice("§aConnection: addr data has been loaded");
		}else{
			MainLogger::getLogger()->notice("§eConnection: Cannnot load addr data. You should set your 'connection addr' immedeately!");
		}
	}

	public static function writePlace($placeNo){
		self::$placeNo = $placeNo;
		//毎回セーブする必要はない。起動中に書き換わらないから。コマンドでセーブするのみ。
		$data = [
			self::$placeNo
		];

		$result = Settings::save('Connection', $data);
		if($result){
			MainLogger::getLogger()->notice("§aConnection: place data has been saved");
		}
	}

	public static function writeAddr($placeNo, $txt){
		$ar = explode(":", $txt);
		$addr = $ar[0];
		$port = isset($ar[1]) ? $ar[1] : 0;
		if($port && (int) $port > 0){
			$place = self::getPlaceByNo($placeNo);
			$place->setAddr($port);
			$place->setPort($addr);

			$living = self::getPlaceByNo(1);
			$resource = self::getPlaceByNo(2);
			$data2 = [
				$living->getAddr(),
				$living->getPort(),
				$resource->getAddr(),
				$resource->getPort()
			];
			$result = Settings::save('ConnectionAddr', $data2);
			if($result){
				MainLogger::getLogger()->notice("§aConnection: addr data has been saved");
				return true;
			}
			return false;
		}else{
			return false;
		}
	}
}