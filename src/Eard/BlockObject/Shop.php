<?php
namespace Eard\BlockObject;


# Basic
use pocketmine\Player;
use pocketmine\item\Item;

# Eard
use Eard\BlockObject\ItemName;
use Eard\Chat;


/****
*
*	ショップ
*/
class Shop implements BlockObject {

/********************
	BlockObject
********************/

	public $x, $y, $z;
	public $objNo;
	public static $kind = 2;

	public function Place(Player $player){
		$name = $player->getName();
		$player->sendMessage(Chat::Format("ショップ", "$name さんをオーナーとして設定しました"));
		if(!$this->owner){
			$this->owner = strtolower($name);
		}
		return false;
	}

	public function Tap(Player $player){
		$this->MenuTap($player);
		return true; //キャンセルしないと、手持ちがブロックだった時に置いてしまう
	}

	public function StartBreak(Player $player){
		$this->MenuLongTap($player);
		if($this->owner === $player){
			return false; //こわせる
		}
		return true;
	}

	public function Break(Player $player){
		if($this->owner === strtolower($player->getName()) ){
			return false; //こわせる
		}else{
			return true;
		}
	}

	public function Delete(){
		$this->removeTextParticleAll(); // blockMenu
	}

	public function getData(){
		return [];
	}
	public function setData($data){
		return true;
	}

	public function getObjNo(){
		return $this->objNo;
	}



	public function Chat(Player $player, String $txt){

	}


/********************
	BlockMenu
********************/

	use BlockMenu;

	public function getPageAr($no, $player){
		switch($no){
		case 1: // トップ
			if(isset( $this->flags[$player->getName()] )){
				unset( $this->flags[$player->getName()] );
			}
			$ar = [
				["ショップ", false],
				["買う", 2],
				["売る", 4],
			];
			if(strtolower($player->getName()) === $this->owner){
				$ar[] = ["管理画面へ", 50];
			}
		break;
		case 2: // 買う つぎへ
			if(!isset( $this->flags[$player->getName()] )){
				$this->flags[$player->getName()] = [1, 1];
			}else{
				$this->flags[$player->getName()][1] += 1;				
			}
			$ar = $this->getPriceList($player);
		break;
		case 3: // 買う もどる
			if(!isset( $this->flags[$player->getName()] )){
				$this->flags[$player->getName()] = [1, 1];
			}else{
				$this->flags[$player->getName()][1] -= 1;
			}
			$ar = $this->getPriceList($player);
		break;
		case 4: // うる つぎへ
			if(!isset( $this->flags[$player->getName()] )){
				$this->flags[$player->getName()] = [2, 1];
			}else{
				$this->flags[$player->getName()][1] += 1;				
			}
			$ar = $this->getPriceList($player);
		break;
		case 5: // うる もどる
			if(!isset( $this->flags[$player->getName()] )){
				$this->flags[$player->getName()] = [2, 1];
			}else{
				$this->flags[$player->getName()][1] -= 1;
			}
			$ar = $this->getPriceList($player);
		break;
		case 6: case 7: case 8: case 9: case 10: case 11: case 12: case 13:
			$indexOfPriceList = $this->flags[$player->getName()][1] * 8 + ($no - 6);
			$flag = $this->flags[$player->getName()][0];
			$this->getPrice( $flag, $indexOfPriceList );
		break;
		case 50: // 管理画面

		break;
		default: 
			$ar = [
				["アイテム交換", false],
				["ページがありません",1],
			];
		break;
		}
		return $ar;
	}


	/*
		$this->flags = [
			$name => 
				[
					0 => 画面の種類 (1 = かう 2 = うる)
					1 => ページ数
				],
		];
		$this->price = [
			1 => // 買うときのリスト
				[


				],
			2 => // 売るときのリスト
				[


				],
			
		]
	*/
	public function getPriceList($player){
		$flag = $this->flags[$player->getName()][0]; //買うモードか売るモード
		if(isset($this->price[$flag])){
			echo "ｓ";
			$startIndex = ( $this->flags[$player->getName()][1] - 1 ) * 8;
			$itemPriceList = array_slice($this->price[$flag], $startIndex, 8);
			$out = [];

			//　次へ ボタン
			if(count($itemPriceList) === 8){
				$nextNo = $this->flags[$player->getName()][0] === 1 ? 2 : 4;
				$out[] = ["次へ", $nextNo];
			}

			// 戻る ボタン
			if( $this->flags[$player->getName()][1] === 1){ // ページが1、つまり最初のページ
				$out = ["戻る", 1];
			}else{
				$backNo = $this->flags[$player->getName()][0] === 1 ? 3 : 5;
				$out[] = ["戻る", $backNo];
			}

			// アイテムぼたん
			if($itemList){
				$num = 6; //getPageArのcaseの最初 - 1;
				foreach($itemPriceList as $key => $d){
					list($id, $meta) = explode(":", $key);
					$name = ItemName::getNameOf($id, $meta);
					$out[] = [$name, $num];
					++ $num;
				}
			}
			return $out;
		}else{
			$out = [
				["アイテム交換", false],
				["リストがありません",false],
				["戻る",1],
			};
			return $out;
		}
	}

	public function getPrice( $flag, $index ){
		array_slice($this->price[$flag], $index, 1);
	}






	private $owner; //string

	public function sellToPlayer(Player $player, Item $item){

	}
	public function buyFromPlayer(Player $player, Item $item){

	}


	public function setPrice($id, $meta, $flag, $price){

	}

	private $price = [];
}