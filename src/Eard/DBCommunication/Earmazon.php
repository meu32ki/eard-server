<?php
namespace Eard\DBCommunication;


# Basic
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\utils\MainLogger;

# Eard
use Eard\MeuHandler\Government;
use Eard\Utils\ItemName;
use Eard\Utils\Chat;


/***
*	政府がアイテムを売買する場所をEarmazonと呼ぶ
*/
class Earmazon {


	public static function init(){
		self::setup();
	}


	public static function reset(){
		$itemListById = ItemName::getListById();
		$db = DB::get();

		// ぶん回ししてセットアップする
		MainLogger::getLogger()->notice("§aEarmazon: セットアップを開始します、、、");


		// itemstorageりせっと
		$db->query("truncate earmazon_itemstorage;");
		// itemstorage格納
		$sql = "";
		foreach($itemListById as $id => $m){
			foreach($m as $meta => $name){
				$firstchar = mb_substr($name, 0, 1);
				// ↓　itemNameのうち、名前が（で始まっているものは、設置専用のブロック。tile:で始まるものも同じなので、のぞいておく必要がある
				if( $firstchar !== "t" || $firstchar !== "("){
					$sql = "INSERT INTO earmazon_itemstorage (id, meta, amount) VALUES ('{$id}', '{$meta}', '0'); ";
					$result = $db->query($sql);
				}
			}
		}


		// アイテムを数個格納
		MainLogger::getLogger()->notice("§aEarmazon: アイテムを入れます、、、");

		self::addIntoStorage(Item::LOG, 0, 1000);
		self::addIntoStorage(Item::WHEAT_SEEDS, 0, 10000);

		self::addIntoStorage(Item::IRON_INGOT, 0, 2000);
		self::addIntoStorage(Item::COAL, 0, 2000);
		self::addIntoStorage(Item::EMERALD, 0, 10000);

		self::addIntoStorage(Item::ENDER_CHEST, 0, 1000);
		self::addIntoStorage(Item::BREWING_STAND, 0, 1000);


		// 売却できる
		$db->query("truncate earmazon_itemselllist;");

		self::addSellUnit(Item::LOG, 0, 100, 10);
		self::addSellUnit(Item::WHEAT, 0, 8000, 30);
		self::addSellUnit(Item::WHEAT, 0, 2000, 45);
		self::addSellUnit(Item::BREAD, 0, 100, 150);

		self::addSellUnit(Item::IRON_INGOT, 0, 1000, 400);
		self::addSellUnit(Item::IRON_INGOT, 0, 100, 450);
		self::addSellUnit(Item::IRON_ORE, 0, 1000, 300);
		self::addSellUnit(Item::COAL, 0, 1000, 50);
		self::addSellUnit(Item::COAL, 0, 1000, 30);
		self::addSellUnit(Item::EMERALD, 0, 5000, 4000);


		// プレイヤーがこの価格で購入できる
		$db->query("truncate earmazon_itembuylist;");

		self::addBuyUnit(Item::LOG, 0, 100, 50);
		self::addBuyUnit(Item::WHEAT_SEEDS, 0, 10000, 2);

		self::addBuyUnit(Item::IRON_ORE, 0, 1000, 470);
		self::addBuyUnit(Item::IRON_INGOT, 0, 100, 500);
		self::addBuyUnit(Item::IRON_INGOT, 0, 1000, 600);
		self::addBuyUnit(Item::COAL, 0, 2000, 100);
		self::addBuyUnit(Item::EMERALD, 0, 5000, 5000);

		self::addBuyUnit(Item::ENDER_CHEST, 0, 100, 10000);
		self::addBuyUnit(Item::BREWING_STAND, 0, 5, 10000);


		MainLogger::getLogger()->notice("§bEarmazon: Reset 完了");
		self::check();
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
		// echo $sql."\n";
		$result = DB::get()->query($sql);
		// echo $result;
		return $result."\n";
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
		if($result){
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
		$sql = "UPDATE earmazon_itembuylist SET leftamount = leftamount - {$amount} WHERE no = {$unitno};";
		$result = DB::get()->query($sql);
		return $result;
	}
	public static function addIntoBuyUnit($unitno, $amount){
		$sql = "UPDATE earmazon_itembuylist SET leftamount = leftamount + {$amount} WHERE no = {$unitno};";
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
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itembuylist WHERE leftamount != 0;";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if($result){
			while( $row = $result->fetch_assoc() ){
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
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itembuylist WHERE id = {$id} and meta = {$meta} and leftamount != 0;";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if($result){
			while( $row = $result->fetch_assoc() ){
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
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itembuylist WHERE category = {$category} and leftamount != 0;";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if($result){
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
		$sql = "SELECT id, meta, leftamount, price FROM earmazon_itembuylist WHERE no = {$unitno};";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if($result){
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

		$category = ItemName::getCategoryOf($id, $meta);
		if(!$category){
			return false; //カテゴリーが0 = 販売禁止 のアイテムは売れない
		}

		$flag = $tokka ? 1 : 0; // tokkaは、つけると上の辺に表示させるとかして
		$db = DB::get();
		$sql = "INSERT INTO earmazon_itembuylist (category, flag, id, meta, baseamount, leftamount, price, date) ".
					"VALUES ('{$category}', '{$flag}', '{$id}', '{$meta}', '{$amount}','{$amount}', '{$price}', now() ); ";
		$result = $db->query($sql);

		// echo $sql.": {$result}\n";
		// echo $db->error;
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
		$player = ($playerData->getPlayer() instanceof Player) ? $playerData->getPlayer() : null;		

		// リストにあったものがまだ売れ残っているか
		if(!$unitData){
			if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "ユニットの情報が見当たりませんでした"));
			return false;
		}

		$id = $unitData[0];
		$meta = $unitData[1];
		$unitamount = $unitData[2];
		$price = $unitData[3];
		$storageamount = self::getStorageAmount($id, $meta);

		// ストレージに在庫がない 販売リストの点数が切れた ら売れないよね
		if($storageamount == -1){
			if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§cエラー。§7そんなアイテムありません。"));			
		}

		if(!$storageamount or !$unitamount){
			if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§cエラー。§7Earmazonのストレージに在庫がありません。販売再開には、誰かがそのアイテムを売る必要があります。"));
			return false;
		}

		// 64個以上のまとめうりは無理よ(おもにインベントリ関係がめんどくさいから)
		if(64 < $amount){
			if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§c出るべきでないエラー(報告してください)。§71スタック以上のまとめ買いはできません。"));
			return false;
		}

		// 在庫のほうが少なかったら売れないよね
		// 販売リストのチェックも兼ねている
		if($unitamount < $amount or $storageamount < $amount){
			if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§cエラー。§7あなたは、在庫にあるよりも多くの数量を指定しています。"));
			return false;
		}

		if($playerData->isOnline()){
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
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§cエラー。§7手持ちのアイテムがいっぱいです。"));
				// 手持ちのアイテムいっぱいだったらかえないよね
				return false;
			}

			# 渡す処理

			// payが足りているかの確認 プレイヤーからもらう処理
			$pay = $price * $amount;	
			$itemname = ItemName::getNameOf($id, $meta);
			if(!Government::receiveMeu($playerData, $pay, "Earmazon: アイテム購入 {$itemname} x{$amount}")){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§cエラー。§7お金が足りません。"));
				return false;
			}

			// 販売りすとの点数からひいてく
			if(!self::removeFromBuyUnit($unitno, $amount)){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§c出るべきでないエラー(報告してください)。§7販売リストから残数を減らす処理に失敗しました。"));
				Government::giveMeu($playerData, $pay, "Earmazon: エラーのため返金"); // 送金処理を戻す
				return false;
			}

			// ストレージから減らす
			if(!self::removeFromStorage($id, $meta, $amount)){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§c出るべきでないエラー(報告してください)。§7ストレージの在庫を減らす処理に失敗しました。"));
				self::addIntoBuyUnit($unitno, $amount); // 販売リストの点数戻す
				Government::giveMeu($playerData, $pay, "Earmazon: エラーのため返金"); // 送金処理を戻す
				return false;
			}

			if($player) {
				$player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "完了。購入した {$itemname}({$price}μ/個) x{$amount} はItemBoxに送られました。"));
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
				Government::giveMeu($playerData, $pay); // 送金処理を戻す
				return false;
			}

			// ストレージから減らす
			if(self::removeFromStorage($id, $meta, $amount)){
				self::addIntoBuyUnit($unitno, $amount); // 販売リストの点数戻す
				Government::giveMeu($playerData, $pay); // 送金処理を戻す
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
		$sql = "UPDATE earmazon_itemselllist SET leftamount = leftamount - {$amount} WHERE no = {$unitno};";
		$result = DB::get()->query($sql);
		return $result;
	}
	public static function addIntoSellUnit($unitno, $amount){
		$sql = "UPDATE earmazon_itemselllist SET leftamount = leftamount + {$amount} WHERE no = {$unitno};";
		$result = DB::get()->query($sql);
		return $result;
	}


	/**
	*	販売リストをすべて取得
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@return Array [ [$id, $meta, $amount, $price, $no] ]
	*/
	public static function searchSellUnit(){
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itemselllist WHERE leftamount != 0;";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if($result){
			while( $row = $result->fetch_assoc() ){
				$unitdata[] = [ $row['id'], $row['meta'], $row['leftamount'], $row['price'], $row['no'] ];
			}
		}
		return $unitdata;
	}


	/**
	*	idとmetaから買取リストにあるunitnoをさがす
	*	@param Int ItemId
	*	@param Int ItemDamage
	*	@return Array [ [$id, $meta, $amount, $price, $no] ]
	*/
	public static function searchSellUnitById($id, $meta){
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itemselllist WHERE id = {$id} and meta = {$meta} and leftamount != 0;";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if($result){
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
		$sql = "SELECT no, id, meta, leftamount, price FROM earmazon_itemselllist WHERE category = {$category} and leftamount != 0;";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if($result){
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
		$sql = "SELECT id, meta, leftamount, price FROM earmazon_itemselllist WHERE no = {$unitno};";

		$unitdata = [];
		$result = DB::get()->query($sql);
		if($result){
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
		$db = DB::get();
		$sql = "INSERT INTO earmazon_itemselllist (category, flag, id, meta, baseamount, leftamount, price, date) ".
					"VALUES ('{$category}', '{$flag}', '{$id}', '{$meta}', '{$amount}','{$amount}', '{$price}', now() ); ";
		$result = $db->query($sql);

		// echo $sql."\n";
		// echo $db->error;

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
		$player = ($playerData->getPlayer() instanceof Player) ? $playerData->getPlayer() : null;		

		// リストにあったものがまだ売れ残っているか
		if(!$unitData){
			if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "ユニットの情報が見当たりませんでした"));
			return false;
		}

		$id = $unitData[0];
		$meta = $unitData[1];
		$unitamount = $unitData[2];
		$price = $unitData[3];

		// echo "{$id} {$meta} {$unitamount} {$price}";

		if($itemBox = $playerData->getItemBox()){
			// PMMPから

			// playerオブジェクトが入っていなければ終了
			$player = $playerData->getPlayer();
			if(!($player instanceof Player) or !$player->isOnline()){
				return false;
			}

			// idがしぬ
			if(!$id){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§c出るべきでないエラー(報告してください)。§7売りに出すアイテムidが0です。"));
				return false;
			}

			// もう売れないじゃん
			if(!$unitamount){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "申し訳ありませんが、そのアイテムの買取は終了しました。"));
				return false;
			}

			// 64個以上のまとめうりは無理よ(おもにインベントリ関係がめんどくさいから)
			if(64 < $amount){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§cエラー。§7売る数量として、無効な値が選択されています。"));
				return false;
			}

			$itemname = ItemName::getNameOf($id, $meta);

			// 在庫のほうが少なかったら売れないよね
			// 販売リストのチェックも兼ねている
			if($unitamount < $amount){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§e{$itemname} は買取可能な個数を超えているため、{$amount}個のうち{$unitamount}個を売りに出します。"));
				$amount = $unitamount;
			}

			$inv = $player->getInventory();

			// 売るアイテムが、売る個数ぶんはいっているか？
			// removeの確認
			$item = Item::get($id, $meta, $amount); // 売りに出すアイテム プレイヤーからcontainsをかける

			// var_dump( $item );

			if(!$inv->contains($item)){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§cエラー。§7売りに出すアイテムが手持ちに含まれていません。"));
				return false; 
			}

			# 買取の処理

			// 政府に金があるかチェック プレイヤーに金渡す
			$pay = $price * $amount;
			if(!Government::giveMeu($playerData, $pay, "Earmazon: アイテム売却 {$itemname} x{$amount}")){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§cエラー。§7政府には、あなたのアイテムを買い取るだけの予算が残っていません。"));
				return false;
			}

			// 販売りすとの点数からひいてく
			if(!self::removeFromSellUnit($unitno, $amount)){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§c出るべきでないエラー(報告してください)。§7買取リストから残数を減らす処理に失敗しました。"));
				Government::receiveMeu($playerData, $pay, "Earmazon: エラーのため返金");
				return false;
			}

			// ストレージに入れる
			if(!self::addIntoStorage($id, $meta, $amount)){
				if($player) $player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "§c出るべきでないエラー(報告してください)。§7ストレージの在庫を増やす処理に失敗しました。"));
				self::addIntoBuyUnit($unitno, $amount);
				Government::receiveMeu($playerData, $pay, "Earmazon: エラーのため返金");
				return false;				
			}

			try{
				$inv->removeItem($item); //trueはすぐに反映させるかどうか
				if($player){
					$player->sendMessage(Chat::Format("§7Earmazon", "§6個人", "完了。{$itemname}({$price}μ/個) x{$amount} を買取しました。"));
				}
				return true;
			}catch(\InvalidArgumentException $e){
				self::addIntoBuyUnit($unitno, $amount, "Earmazon: エラーのため返金");
				Government::receiveMeu($playerData, $pay);
				self::removeFromStorage($id, $meta, $amount);
				return false;
			}
		}else{
			// Webから
			return false; // todo ちゃんと処理追加しろ
		}
	}

/*	確認
*/

	public static function check(){
		$db = DB::get();


		echo "*****\nitem storage\n*****\n";
		$sql = "SELECT * FROM earmazon_itemstorage WHERE amount != 0;";
		$result = $db->query($sql);
		if($result){
			while( $row = $result->fetch_assoc() ){
				foreach($row as $key => $d){
					echo "{$key}:{$d} ";
				}
				echo "\n";
			}
		}else{
			echo "skipped\n";
		}
		echo "\n\n";


		echo "*****\nsell list\n*****\n";
		$sql = "SELECT * FROM earmazon_itemselllist;";
		$result = $db->query($sql);
		if($result){
			while( $row = $result->fetch_assoc() ){
				foreach($row as $key => $d){
					echo "{$key}:{$d} ";
				}
				echo "\n";
			}
		}else{
			echo "skipped\n";
		}
		echo "\n\n";


		echo "*****\nbuy list\n*****\n";
		$sql = "SELECT * FROM earmazon_itembuylist;";
		$result = $db->query($sql);
		if($result){
			while( $row = $result->fetch_assoc() ){
				foreach($row as $key => $d){
					echo "{$key}:{$d} ";
				}
				echo "\n";
			}
		}else{
			echo "skipped\n";
		}
		echo "\n\n";
	}

}