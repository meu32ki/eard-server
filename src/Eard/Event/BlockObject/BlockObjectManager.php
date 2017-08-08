<?php
namespace Eard\Event\BlockObject;


use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\utils\MainLogger;

# Eard
use Eard\Utils\DataIO;

/****
*
*	鯖内における特殊設置物
*/
class BlockObjectManager {

/*
	実際にオブジェクトを入れるのは $blocks
	オブジェクトにたどりつくためのキーを入れておくのが $index

	セーブ時 $index 書く $blocks をセーブしファイルに書き込む。
	ロード時 $index読む $blocks　読まない 
	blocks タップ時に、indexをissetして、あればblocksに展開してから open する
*/

	/**
	*	ブロックが置かれた時
	*	trueが帰ると、キャンセルされる
	*	@param Block
	*	@return bool
	*/
	public static function Place(Block $block, Player $player){
		$objNo = 0;
		switch($block->getId()){
			case 245: $objNo = 1; break; // ストーンカッター
			case 247: $objNo = 2; break; // リアクターコア
			case 379: $objNo = 3; break; // 調合台
			case 117: $objNo = 3; break; // 調合台
		}
		echo $objNo;
		if($objNo){
			$obj = self::makeObject($block->x, $block->y, $block->z, $objNo);
			$obj->Place($player);
			return false;
		}
		return false;

	}

	/**
	*	ブロックがタップされた時
	*	trueが帰ると、キャンセルされる
	*	@param Block タップした対象
	*	@param Item そのブロックをタップしたアイテム
	*	@return bool
	*/
	public static function Tap(Block $block, Player $player){
		$x = $block->x; $y = $block->y; $z = $block->z;
		$obj = self::getObject($x, $y, $z);
		if($obj){
			return $obj->Tap($player);
		}
		return false;
	}

	/**
	*	ブロック長押しされた時　キャンセルは不可
	*	@param $x, $y, $z | 座標
	*	@param Item そのブロックをタップしたアイテム
	*	@return bool
	*/
	public static function StartBreak(Int $x, Int $y, Int $z, Player $player){
		if( isset(self::$index[$x][$y][$z]) ){
			$obj = self::getObject($x, $y, $z);
			if($obj){
				return $obj->StartBreak($player);
			}
		}
		return false;
	}

	/**
	*	ブロック長押しされ続け、壊された時
	*	trueが帰ると、キャンセルされる
	*	@param Block | $x, $y, $z がはいってる座標
	*	@param Item | そのブロックをタップしたアイテム
	*	@return bool
	*/
	public static function Break(Block $block, Player $player){
		$x = $block->x; $y = $block->y; $z = $block->z;
		$obj = self::getObject($x, $y, $z);
		if($obj){
			$result = $obj->Break($player);

			// 重要、これがないと、処理が重かった場合に死ぬ
			$player->lastBreak = $player->lastBreak - 1.4;
			
			if(!$result){
				$obj->Delete();
				self::deleteObject($x, $y, $z);
			}
			return $result;
		}
		return false;
	}

	/**
	*	破壊された後の最終処理 インデックスとオブジェクトデータの破棄
	*	@return void
	*/
	public static function deleteObject($x, $y, $z){ //即壊す
		$index = self::$index[$x][$y][$z];
		unset(self::$objects[$index]);
		unset(self::$index[$x][$y][$z]);
	}

	/**
	*	オブジェクト番号
	*/
	private static function getEmptyObject($objNo){
		$obj = null;
		switch($objNo){
			case 1: $obj = new ItemExchanger(); break;
			case 2: $obj = new Shop(); break;
			case 3: $obj = new EarmazonShop(); break;
		}
		return $obj;
	}

	/**
	*	置く前の準備
	*/
	private static function makeObject($x, $y, $z, $objNo){
		// オブジェクト自体
		$obj = self::getEmptyObject($objNo);

		// Managerがわ(こちらがわ)に必要な情報
		self::$index[$x][$y][$z] = self::$indexNo + 1;
		self::$indexNo += 1;
		self::$objects[self::$indexNo] = $obj;

		// obj側に必要な情報
		$obj->x = $x;
		$obj->y = $y;
		$obj->z = $z;
		$obj->indexNo = self::$indexNo;
		return $obj;
	}

	/**
	*	指定されたインデックスにm格納されている(べき)BlockObjectを返す
	*	見つからない場合はHDDからデータを読み出し、格納したうえで返す
	*	@param int | $indexNo
	*	@return BlockObject | interface::BlockObjectを使っているクラス
	*/
	public static function getObject($x, $y, $z){
		if( isset(self::$index[$x][$y][$z]) ){
			$indexNo = self::$index[$x][$y][$z];
			if( isset(self::$objects[$indexNo]) ){
				// 1...インデックスが張られている、読まれている
				return self::$objects[$indexNo];
			}else{
				//-1...インデックスが張られている、読まれていない

				// セーブしてあるデータを引っ張ってくる
				$objData = self::loadObjectData($indexNo);
				if($objData){

					// オブジェクトに関するデータが取得できたら、データからオブジェクトを復元する
					$objNo = $objData[0];
					$obj = self::getEmptyObject($objNo);
					self::$objects[$indexNo] = $obj;

					$obj->x = $x;
					$obj->y = $y;
					$obj->z = $z;
					$obj->indexNo = $indexNo;

					$obj->setData($objData[1]);
					return $obj;
				}else{
					// self::$blocks上にうまくオブジェクトのデータの読み出しができない
					MainLogger::getLogger()->notice("§cBlockObjectManager: index {$x},{$y},{$z} has been deleted due to save failure.");
					self::deleteObject($x, $y, $z);
					return null;			
				}
			}
		}else{
			// 0　...インデックスが張られていない、オブジェクトがない
			return null;
		}
	}



	public static $objects = [];
	public static $index = []; //外部からgetはしてもいいがsetはするな
	public static $indexNo = 0;




	/*
		public static function loadAllObjects(){
			//この関数は作る必要がない
			//いきなりすべての読み込みはしないから
		}
	*/
	public static function saveAllObjects(){
		foreach(self::$objects as $indexNo => $obj){
			self::saveObjectData($indexNo, $obj);
		}
	}

	/**
	*	@param int | $indexNo = self::index[$x][$y][$z]の中身
	*	@return array $objData
	*/
	public static function loadObjectData($indexNo){
		$path = DataIO::getPath()."obj/";
		$filepath = "{$path}{$indexNo}.sra";
		$json = @file_get_contents($filepath);
		if($json){
			if($objData = unserialize($json)){
				/*
					// objectDataの構造
					array => [
						0 => int,　($objNo)
						1 => array => [
							ばばばばば
						]
					]
				*/
				return $objData;
			}
		}
	}

	/**
	*	@param int | $indexNo = self::index[$x][$y][$z]の中身
	*	@param obj | blockObjectをextendsしているやつ
	*	@return bool
	*/
	public static function saveObjectData($indexNo, $obj){
		$path = DataIO::getPath()."obj/";
		if(!file_exists($path)){
			@mkdir($path);
		}
		$filepath = "{$path}{$indexNo}.sra";
		$objNo = $obj::$objNo;
		$data = $obj->getData();
		$json = serialize([$objNo, $data]);
		return file_put_contents($filepath, $json);
	}


	/*
	*	このclass::BlockObjectmanagerで使っている変数を保存
	*/
	public static function load(){
		$data = DataIO::load('BlockObjectManager');
		if($data){
			self::$indexNo = $data[0];
			self::$index = $data[1];
			MainLogger::getLogger()->notice("§aBlockObjectManager: Successfully loaded");
		}else{
			MainLogger::getLogger()->notice("§eBlockObjectManager: data will be automatically created.");
		}
	}

	public static function save(){
		$data = [self::$indexNo, self::$index];
		$result = DataIO::save('BlockObjectManager', $data);
		if($result){
			MainLogger::getLogger()->notice("§aBlockObjectManager: Successfully saved");
		}
	}

	public function onTime(){

	}


}