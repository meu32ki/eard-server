<?php
namespace Eard;


use pocketmine\item\Item;


/***
*
*	アイテムの、日本語名を入れておくためのやつ
*/
class ItemName{



	public static function getNameOf($id, $meta){
		$id = $item->getId();
		$meta = $item->getMetadata();

		if( isset(self::$listById[$id][$meta]) ){
			return self::$listById[$id][$meta]; //書いてあれば日本語名
		}else{
			return Item::get()->getName(); //書いてなければ英名
		}
	}

	public static function init(){

		// 変換
		$list = [];
		foreach(self::$listByName as $name => $i){
			$list[$i[0]][$i[1]] = $name;
		}
		self::$listById = $list;
	}


	private static $listByName = [
		"焼き石" => [1,0],
		"石" => [1,0],
		"花崗岩" => [1,1],
		"磨かれた花崗岩" => [1,2],
		"閃緑岩" => [1,3],
		"磨かれた閃緑岩" => [1,4],
		"安山岩" => [1,5],
		"磨かれた安山岩" => [1,6],
		"草ブロック" => [2,0],
		"土" => [3,0],

		//誰かこんな感じで追加していってください！おなしゃす！
	];
	private static $listById = [];
}