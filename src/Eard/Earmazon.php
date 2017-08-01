<?php

Namespace Eard;

use pocketmine\item\Item;
use pocketmine\utils\MainLogger;

# Eard
use Eard\Utils\ItemName;


/***
*	政府がアイテムを売買する場所をEarmazonと呼ぶ
*/
class Earmazon {


	public static function init(){
		self::setup();
	}


	public static function setup (){
		$itemListById = ItemName::getListById();

		// ぶん回ししてセットアップする
		MainLogger::getLogger()->notice("§aEarmazon: セットアップを開始します、、、");

		$db = DB::get();
		$sql = "";
		foreach($itemListById as $id => $m){
			foreach($m as $meta => $name){
				$firstchar = mb_substr($name, 0, 1);
				// ↓　itemNameのうち、名前が（で始まっているものは、設置専用のブロック。tile:で始まるものも同じなので、のぞいておく必要がある
				if( $firstchar !== "t" || $firstchar !== "("){
					$sql .= "INSERT INTO earmazon_itemstorage (id, meta, amount) VALUES ('{$id}', '{$meta}', '0'); ";
				}
			}
		}
		$result = $db->query($sql);

		// アイテムを数個格納

		MainLogger::getLogger()->notice("§aEarmazon: アイテムを入れます、、、");
		
		self::addIntoStorage(Item::LOG, 0, 1000);
		self::addIntoStorage(Item::WHEAT_SEEDS, 0, 10000);

		// 売却できる
		self::addSellUnit(Item::LOG, 0, 100, 10);
		self::addBuyUnit(Item::WHEAT, 0, 8000, 30);
		self::addBuyUnit(Item::WHEAT, 0, 2000, 50);
		self::addBuyUnit(Item::BREAD, 0, 100, 150);

		self::addSellUnit(Item::IRON_INGOT, 0, 100, 400);
		self::addSellUnit(Item::IRON_INGOT, 0, 100, 300);
		self::addSellUnit(Item::IRON_INGOT, 0, 100, 250);
		self::addSellUnit(Item::COAL, 0, 100, 50);
		self::addSellUnit(Item::COAL, 0, 100, 30);
		self::addSellUnit(Item::EMERALD, 0, 50, 5000);

		// プレイヤーがこの価格で購入できる
		self::addBuyUnit(Item::LOG, 0, 100, 50);
		self::addBuyUnit(Item::WHEAT_SEEDS, 0, 10000, 2);

		self::addBuyUnit(Item::IRON_INGOT, 0, 100, 500);
		self::addBuyUnit(Item::IRON_INGOT, 0, 100, 800);
		self::addBuyUnit(Item::COAL, 0, 200, 100);
		self::addBuyUnit(Item::EMERALD, 0, 10, 10000);

		self::addBuyUnit(Item::ENDER_CHEST, 0, 5, 10000);
		
		MainLogger::getLogger()->notice("§aEarmazon: 完了");
	}


/*	Storage
*/

	/**
	*	Earmazonのアイテムストレージに、アイテムをぶち込む(つまり、買い取る)
	*	通貨決済の処理などは行わないので、それがしたいのであれば buyをつかうこと
	*	主に、政府が鯖に出現しないアイテムを出すためのようそとして作った
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@param Int 増やす量 入れる量
	*	@return bool
	*/
	public static function addIntoStorage($id, $meta, $amount){
		$sql = "UPDATE earmazon_itemstorage SET amount = amount + {$amount} WHERE id = {$id} and meta = {$meta};";
		$result = DB::get()->query($sql);
		return $result;
	}


	/**
	*	Earmazonのアイテムストレージから、アイテムを減らす(つまり、売りに出す)
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@param Int 減らす量 入れる量
	*	@return bool
	*/
	public static function removeFromStorage($id, $meta, $amount){
		$sql = "UPDATE earmazon_itemstorage SET amount = amount - {$amount} WHERE id = {$id} and meta = {$meta};";
		$result = DB::get()->query($sql);
		return $result;
	}


	/**
	*	そのIDのアイテムがいくつあるかみる
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@return Int -1 ~
	*/
	public static function getStorageAmount($id, $meta){
		$sql = "SELECT * FROM earmazon_itemstorage WHERE id = {$id} and meta = {$meta};";
		$result = DB::get()->query($sql);
		if(!$result){
			if( $row = $result->fetch_assoc() ){
				return $row['amount'];
			}
		}
		return -1;
	}


/*	Unit プレイヤー:買う Earmazon:売る
*/


	/**
	*	販売したとき、販売リストにあるそのユニットの、残り可能買取数を減らす。
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@param Int 減らす量 入れる量
	*	@return bool
	*/
	public static function removeFromBuyUnit($unitno, $amount){
		$sql = "UPDATE earmazon_itembuylist SET leftamount = leftamount - {$amount} WHERE unitno = {$unitno};";
		$result = DB::get()->query($sql);
		return $result;
	}


	/**
	*	すべての売られているunitnoをさがす
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@return Array [ [$id, $meta, $amount, $price, $no] ]
	*/
	public static function searchBuyUnit(){
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itembuylist;";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if(!$result){
			while( $row = $result->fetch_assoc() ){
				$no = $row['no'];
				$unitdata[] = [ $row['id'], $row['meta'], $row['leftamount'], $row['price'], $row['no'] ];
			}
		}
		return $unitdata;
	}


	/**
	*	idとmetaから売られているunitnoをさがす
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@return Array [ [$id, $meta, $amount, $price, $no] ]
	*/
	public static function searchBuyUnitById($id, $meta){
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itembuylist WHERE id = {$id} and meta = {$meta};";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if(!$result){
			while( $row = $result->fetch_assoc() ){
				$no = $row['no'];
				$unitdata[] = [ $row['id'], $row['meta'], $row['leftamount'], $row['price'], $row['no'] ];
			}
		}
		return $unitdata;
	}


	/**
	*	categoryから売られているunitnoを探す
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@return Array [ [$id, $meta, $amount, $price, $no] ]
	*/
	public static function searchBuyUnitByCategory($category){
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itembuylist WHERE category = {$category}";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if(!$result){
			while( $row = $result->fetch_assoc() ){
				$unitdata[] = [ $row['id'], $row['meta'], $row['leftamount'], $row['price'], $row['no'] ];
			}
		}
		return $unitdata;
	}


	/**
	*	与えられたナンバーから、販売リストの残り個数と値段を調達
	*	@return Array [ $id, $meta, $amount, $price ]
	*/
	public static function getBuyUnit($unitno){
		$sql = "SELECT id, meta, leftamount, price FROM earmazon_itembuylist WHERE unitno = {$unitno};";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if(!$result){
			if( $row = $result->fetch_assoc() ){
				return [ $row['id'], $row['meta'], $row['leftamount'], $row['price'] ];
			}
		}
		return [];
	}


	/**
	*	リストにユニットを追加する。ユニットとは、アイテムの値段や残り個数が決まった、「アイテム販売点数」と「その価格」のこと。
	*	@param Int アイテムのid
	*	@param Int アイテムのMeta値
	*	@return bool
	*/
	public static function addBuyUnit($id, $meta, $amount, $price, $tokka = false){

		$category = Itemname::getCategoryOf($id, $meta);
		if(!$category){
			return false; //カテゴリーが0 = 販売禁止 のアイテムは売れない
		}
		
		$flag = $tokka ? 1 : 0; // tokkaは、つけると上の辺に表示させるとかして
		$sql = "INSERT INTO earmazon_itembuylist (category, flag, id, meta, baseamount, leftamount, price) ".
					"VALUES ('{$category}', '{$flag}', '{$id}', '{$meta}', '{$amount}','{$amount}', '{$price}'); ";
		echo $sql;
		$result = $db->query($sql);
		return $result;
	}


	/**
	*	購入処理。
	*	@param Int $unitno 0以上の自然数、キー。searchBuyUnitで検索して。
	*	@param Int $amount 1-64であるべき プレイヤーがそのアイテムをいくつかうか
	*	@param Account $playerData
	*	@return bool trueであれば成功、trueは処理の終わりを意味する
	*/
	public static function playerBuy($unitno, $amount, $playerData){
		$unitData = self::getBuyUnit($unitno);
		
		// リストにあったものがまだ売れ残っているか
		if(!$unitData){
			return false;
		}

		$id = $unitdata[0];
		$meta = $unitdata[1];
		$unitamount = $unitData[2];
		$price = $unitData[3];
		$storageamount = self::getStorageAmount($id, $meta);

		// ストレージに在庫がない 販売リストの点数が切れた ら売れないよね
		if(!$storageamount or !$unitamount){
			return false;
		}

		// 64個以上のまとめうりは無理よ(おもにインベントリ関係がめんどくさいから)
		if(64 < $amount){
			return false;
		}

		// 在庫のほうが少なかったら売れないよね
		// 販売リストのチェックも兼ねている
		if($unitamount < $amount or $storageamount < $amount){
			return false;
		}

		if($itemBox = $playerData->getItemBox()){
			// アイテムボックスが展開されている = pmmp
			/*
				インベントリにItemをaddItemで投げてもいいんだけど、なんか統一性ないし、
				結局webからの場合はarrayいじるんだからItemつかうだけもったいないかなっておもって
				やっぱり、getitemArrayするだけforeach回さなきゃいけないのも苦だなと思うからItemつくってやることにする
				これ、getPlayerしてインベントリに直接投げたほうがいいのでは？
			*/
			$inv = $playerData->getItemBox();
			$item = Item::get($id, $meta, $amount);
			if(!$inv->canAddItem($item)){
				// 手持ちのアイテムいっぱいだったらかえないよね
				return false;
			}

			# 渡す処理

			// payが足りているかの確認 プレイヤーからもらう処理
			$pay = $price * $amount;
			if(!Government::receiveMeu($playerData, $pay)){
				return false;
			}

			// 販売りすとの点数からひいてく
			if(self::removeFromBuyUnit($unitno, $amount)){
				return false;
			}

			$inv->addItem($item); // 追加しとけば鯖出るときに勝手にセーブされるから安心
			return true;
		}else{
			// アイテムボックスが展開されていない = web
			/*
				webであれば直接プレイヤーのitemArrayをいじる。
			*/
			$itemArray = $playerData->getItemArray();

			$cnt = count($itemArray);
			if($cnt === 27 or $cnt === 54){
				// ラージチェスト スモールチェスト の最大数ピッタリだったら追加できない いっぱいだから
				// 手持ちのアイテムいっぱいだったらかえないよね
				return false;
			}

			# 渡す処理

			// payが足りているかの確認 プレイヤーからもらう処理
			$pay = $price * $amount;
			if(!Government::receiveMeu($playerData, $pay)){
				return false;
			}

			// 販売りすとの点数からひいてく
			if(self::removeFromBuyUnit($unitno, $amount)){
				return false;
			}

			$itemArray[] = [$id, $meta, $amount];
			$playerData->setItemArray($itemArray);
			return true;
		}
	}

/*	Unit プレイヤー:売る Earmazon:買う
*/

	/**
	*	買取したとき、買取リストにあるそのユニットの、残り可能買取数を減らす。
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@param Int 減らす量 入れる量
	*	@return bool
	*/
	public static function removeFromSellUnit($unitno, $amount){
		$sql = "UPDATE earmazon_itemselllist SET leftamount = leftamount - {$amount} WHERE unitno = {$unitno};";
		$result = DB::get()->query($sql);
		return $result;
	}


	/**
	*	idとmetaから買取リストにあるunitnoをさがす
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@return Array [ [$id, $meta, $amount, $price, $no] ]
	*/
	public static function searchSellUnit($id, $meta){
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itemselllist WHERE id = {$id} and meta = {$meta};";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if(!$result){
			while( $row = $result->fetch_assoc() ){
				$unitdata[] = [ $row['id'], $row['meta'], $row['leftamount'], $row['price'], $row['no'] ];
			}
		}
		return $unitdata;
	}


	/**
	*	categoryから買取リストにあるunitnoをさがす
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@return Array [ [$id, $meta, $amount, $price, $no] ]
	*/
	public static function searchSellUnitByCategory($category){
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itemselllist WHERE category = {$category}";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if(!$result){
			while( $row = $result->fetch_assoc() ){
				$unitdata[] = [ $row['id'], $row['meta'], $row['leftamount'], $row['price'], $row['no'] ];
			}
		}
		return $unitdata;
	}


	/**
	*	与えられたナンバーから、買取リストの残り個数と値段を調達
	*	@return Array [$id, $meta, $amount, $price ]
	*/
	public static function getSellUnit($unitno){
		$sql = "SELECT leftamount, price FROM earmazon_itemselllist WHERE unitno = {$unitno};";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if(!$result){
			if( $row = $result->fetch_assoc() ){
				return [ $row['id'], $row['meta'], $row['leftamount'], $row['price'] ];
			}
		}
		return [];
	}


	/**
	*	リストにユニットを追加する。ユニットとは、アイテムの値段や残り個数が決まった、「アイテム販売点数」と「その価格」のこと。
	*	@param Int アイテムのid
	*	@param Int アイテムのMeta値
	*	@return bool
	*/
	public static function addSellUnit($id, $meta, $amount, $price, $tokka = false){

		$category = Itemname::getCategoryOf($id, $meta);
		if(!$category){
			return false; //カテゴリーが0 = 販売禁止 のアイテムは売れない
		}
		
		$flag = $tokka ? 1 : 0; // tokkaは、つけると上の辺に表示させるとかして
		$sql = "INSERT INTO earmazon_itemselllist (category, flag, id, meta, baseamount, leftamount, price) ".
					"VALUES ('{$category}', '{$flag}', '{$id}', '{$meta}', '{$amount}','{$amount}', '{$price}'); ";
		$result = DB::get()->query($sql);
		return $result;
	}


	/**
	*	買取処理。
	*	@param Int $unitno 0以上の自然数、キー。searchBuyUnitで検索して。
	*	@param Int $amount 1 - 64
	*	@param Account $playerData
	*	@return bool trueであれば成功、trueは処理の終わりを意味する
	*/
	public static function playerSell($unitno, $amount, $playerData){
		$unitData = self::getSellUnit($unitno);
		
		// リストにあったものがまだ買取可能か
		if(!$unitData){
			return false;
		}

		$id = $unitdata[0];
		$meta = $unitdata[1];
		$unitamount = $unitData[2];
		$price = $unitData[3];

		if($itemBox = $playerData->getItemBox()){
			// PMMPから

			// playerオブジェクトが入っていなければ終了
			$player = $playerData->getPlayer();
			if(!$player instanceof Player or !$player->isOnline()){
				return false;
			}

			// 64個以上のまとめうりは無理よ(おもにインベントリ関係がめんどくさいから)
			if(64 < $amount){
				return false;
			}

			// 在庫のほうが少なかったら売れないよね
			// 販売リストのチェックも兼ねている
			if($unitamount < $amount){
				return false;
			}

			$inv = $player->getInventory();

			// 売るアイテムが、売る個数ぶんはいっているか？
			// removeの確認
			$item = Item::get($id, $meta, $amount); // 売りに出すアイテム プレイヤーからcontainsをかける
			if(!$inv->contains($item)){
				return false; 
			}

			# 買取の処理

			// 政府に金があるかチェック プレイヤーに金渡す
			if(!Government::giveMeu($playerData, $pay)){
				return false;
			}

			// 販売りすとの点数からひいてく
			if(self::removeFromBuyUnit($unitno, $amount)){
				return false;
			}

			$inv->remove($item, true); //trueはすぐに反映させるかどうか
		}else{
			// Webから
			return false; // todo ちゃんと処理追加しろ
		}
	}

}