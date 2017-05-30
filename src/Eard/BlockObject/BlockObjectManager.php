<?php
namespace Eard\BlockObject;


use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\utils\MainLogger;


/****
*
*	鯖内における特殊設置物
*/
class BlockObjectManager {

/*
	実際にオブジェクトを入れるのは $blocks
	オブジェクトにたどりつくためのキーを入れておくのが $index

	セーブ時 $index 書く $blocks をセーブしファイルに書き込む。
	ロード時 $index読む $blocks読まない 
	blocks タップ時に、indexをissetして、あればblocksに展開してから open する
*/

	/**
	*	ブロックが置かれた時
	*	trueが帰ると、キャンセルされる
	*	@param Block
	*	@return bool
	*/
	public static function Place(Block $block, Player $player){
		switch($block->getId()){
			case 245: //ストーンカッター
				//インデックス
				$x = $block->x; $y = $block->y; $z = $block->z;
				self::$index[$x][$y][$z] = self::$indexNo + 1;
				self::$indexNo += 1;
				//実際のオブジェクト
				$obj = new ItemExchanger;
				$obj->x = $x; $obj->y = $y; $obj->z = $z; $obj->objNo = self::$indexNo;
				self::$objects[self::$indexNo] = $obj;
				$obj->Place($player);
				return false;
			break;
			default:
				return false;
			break;
		}

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
		if( isset(self::$index[$x][$y][$z]) ){
			$obj = self::getObject(self::$index[$x][$y][$z]);
			if($obj){
				return $obj->Tap($player);
			}else{
				MainLogger::getLogger()->notice("§cBlockObjectManager: index {$x},{$y},{$z} has been deleted due to save failure.");
				unset(self::$index[$x][$y][$z]);
			}
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
			$obj = self::getObject(self::$index[$x][$y][$z]);
			if($obj){
				return $obj->StartBreak($player);
			}else{
				MainLogger::getLogger()->notice("§cBlockObjectManager: index {$x},{$y},{$z} has been deleted due to save failure.");
				unset(self::$index[$x][$y][$z]);
			}
		}
		return false;
	}

	/**
	*	ブロック長押しされ続け、壊された時
	*	trueが帰ると、キャンセルされる
	*	@param $x, $y, $z | 座標
	*	@param Item そのブロックをタップしたアイテム
	*	@return bool
	*/
	public static function Break(Block $block, Player $player){
		$x = $block->x; $y = $block->y; $z = $block->z;
		if( isset(self::$index[$x][$y][$z]) ){
			$result = self::getObject(self::$index[$x][$y][$z])->Break($player);
			if(!$result){
				self::getObject(self::$index[$x][$y][$z])->Delete();
				self::Delete($x, $y, $z);
			}
			return $result;
		}
		return false;
	}

	/**
	*	破壊された後の最終処理 インデックスとオブジェクトデータの破棄
	*	@return void
	*/
	public static function Delete($x, $y, $z){ //即壊す
		$index = self::$index[$x][$y][$z];
		unset(self::$objects[$index]);
		unset(self::$index[$x][$y][$z]);
	}

	public static function getObject($indexNo){
		if(!isset(self::$objects[$indexNo])){
			if($data = self::loadObjectData($indexNo) ){
				echo $indexNo; print_r($data);
				switch($data[0]){
					case 1: $obj = new ItemExchanger(); break;
				}
				self::$objects[$indexNo] = $obj;
			}else{
				//インデックスはあるが、オブジェクトの保存データがHDD上にない
				return null;
			}
		}
		return self::$objects[$indexNo];
	}

	public static function closeAll(){
		foreach(self::$objects as $obj){
			$data = $obj->getData();
		}
	}

	public static $objects = [];
	public static $index = []; //外部からgetはしてもいいがsetはするな
	public static $indexNo = 0;



	public static function loadObjectData($indexNo){
		$path = __DIR__."/../data/obj/";
		$filepath = "{$path}{$indexNo}.sra";
		$json = @file_get_contents($filepath);
		if($json){
			if($data = unserialize($json)){
				self::$indexNo = $data[0];
				self::$index = $data[1];
			}
		}
	}
	public static function saveObjectData($indexNo){
		$path = __DIR__."/../data/obj/";
		if(!file_exists($path)){
			@mkdir($path);
		}
		$filepath = "{$path}{$indexNo}.sra";
		$json = serialize([self::$indexNo, self::$index]);
		return file_put_contents($filepath, $json);
	}

	public static function load(){
		$path = __DIR__."/../data/";
		$filepath = "{$path}blockObject.sra";
		$json = @file_get_contents($filepath);
		if($json){
			if($data = unserialize($json)){
				self::$indexNo = $data[0];
				self::$index = $data[1];
				MainLogger::getLogger()->notice("§aBlockObjectManager: Successfully loaded");
			}
		}
	}
	public static function save(){
		$path = __DIR__."/../data/";
		if(!file_exists($path)){
			@mkdir($path);
		}
		$filepath = "{$path}blockObject.sra";
		$json = serialize([self::$indexNo, self::$index]);
		MainLogger::getLogger()->notice("§aBlockObjectManager: Successfully saved");
		return file_put_contents($filepath, $json);
	}

	public function onTime(){

	}


}