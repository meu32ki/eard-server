<?php
namespace Eard\BlockObject;


# Basic
use pocketmine\Player;
use pocketmine\item\Item;

# Eard
use Eard\BlockObject\ItemName;
use Eard\Chat;
use Eard\Account;
use Eard\ChestIO;

/****
*
*	ショップ
*/
class Shop implements BlockObject {

/********************
	BlockObject
********************/

	public $x, $y, $z;
	public $indexNo;
	public static $objNo = 2;

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
		if($this->owner === strtolower($player->getName()) ){
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
		echo "shop: getdata\n";
		if($this->inv){ // 一度でも、管理画面が開かれているようなら
			$this->itemArray = $this->inv->getItemArray();
		}
		$data = [
			$this->owner,
			$this->itemArray
		];
		return $data;
	}
	public function setData($data){
		$this->owner = $data[0];
		$this->itemArray = $data[1];
		return true;
	}

	public function getObjIndexNo(){
		return $this->indexNo;
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
			$ar = [
				["ショップ 管理画面", false],
				["アイテムを出し入れする",51],
				["トップへ戻る",1],
			];
		break;
		case 51: // 管理画面 アイテム出し入れ
			if(!$this->inv){
				$this->inv = new ChestIO($player);
				$this->inv->setItemArray($this->itemArray);
			}
			$player->addWindow($this->inv); // インヴェントリ画面送る
			$ar = [
				["ショップ 管理画面", false],
				["管理トップへ",50],
			];		
		break;
		default: 
			$ar = [
				["ショップ", false],
				["ページがありません",1],
			];
		break;
		}
		return $ar;
	}


/********************
	Shop関連
********************/

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
			];
			return $out;
		}
	}

	public function setPriceList($pricelist){
		// 今いじってる人ががいれば、更新したとの通知 
		foreach($this->flags as $name => $data){
			$player = Account::get($name)->getPlayer();
			if($player instanceof Player && $player->isOnline()){
				$player->sendMessage(Chat::Format("ショップ", "個人", "ショップの価格リストが更新されました。"));
			}
		}

		// 更新
		$this->price = $pricelist;
		$this->flags = [];
		return true;
	}

	public function getPrice( $flag, $index ){
		array_slice($this->price[$flag], $index, 1);
	}

	public function setPrice($id, $meta, $flag, $price){

	}

	public function sellToPlayer(Player $player, Item $item){

	}
	public function buyFromPlayer(Player $player, Item $item){

	}

	private $owner; //string
	private $price = [];
	private $flags = [];

	private $inv = null; // ChestIO
	private $itemArray = []; // inv保存用
}