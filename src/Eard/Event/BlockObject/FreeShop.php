<?php
namespace Eard\Event\BlockObject;


# Basic
use pocketmine\Player;
use pocketmine\item\Item;

# Eard
use Eard\Utils\ItemName;
use Eard\MeuHandler\Account;
use Eard\Utils\ChestIO;
use Eard\Utils\Chat;

/****
*
*	ショップ
*/
class FreeShop implements BlockObject {

/********************
	BlockObject
********************/

	const SHOP_100MEU = 0;
	const SHOP_500MEU = 1;
	const SHOP_1000MEU = 2;
	const SHOP_2500MEU = 3;
	const SHOP_5000MEU = 4;
	const SHOP_10000MEU = 5;
	const SHOP_25000MEU = 6;
	const SHOP_50000MEU = 7;
	const SHOP_100000MEU = 8;
	
	public $x, $y, $z;
	public $indexNo;
	public static $objNo = 4;
	public $owner = null;
	public $ownerName = null;

	public $name = "FreeShop";

	public $inventory = null;
	public $itemArray = [];
	public $shopType = 0;
	public $soldout = false;
	
	public static $buyCheck = [];

	public static $price_index = [
		self::SHOP_100MEU => 100,
		self::SHOP_500MEU => 500,
		self::SHOP_1000MEU => 1000,
		self::SHOP_2500MEU => 2500,
		self::SHOP_5000MEU => 5000,
		self::SHOP_10000MEU => 10000,
		self::SHOP_25000MEU => 25000,
		self::SHOP_50000MEU => 50000,
		self::SHOP_100000MEU => 100000,
	];

	/**
	*	ブロックが置かれた時
	*	trueが帰ると、BlockPlaceEventがキャンセルされる
	*	@return bool
	*/
	public function Place(Player $player){
		$inv = $player->getInventory();
		$this->shopType = $inv->getItemInHand()->getDamage();
		$this->ownerName = $player->getName();
		$this->inventory = new ChestIO($player);
		$this->inventory->setItemArray($this->itemArray);
		$this->owner = $player;
		$this->ownerName = $player->getName();
		$this->inventory->setName($this->getShopName());
		$player->addWindow($this->inventory);
		self::$buyCheck[$this->indexNo] = [];
	}

	/**
	*	ブロックがタップされた時
	*	trueが帰ると、PlayerInteractEventがキャンセルされる
	*	@param Item そのブロックをタップしたアイテム
	*	@return bool
	*/
	public function Tap(Player $player){
		if(!$this->inventory){
			$this->inventory = new ChestIO($player);
			$this->inventory->setItemArray($this->itemArray);
			$this->inventory->setName($this->getShopName());
		}
		$this->itemArray = $this->inventory->getItemArray();
		if($player->getName() === $this->ownerName){
			$price = $this->getPrice();
			if($this->soldout){
				//購入価格を送金する処理 #todo 購入で即支払われるように変更したのでいらない(2017/8/30) by moyasan
				$player->sendMessage(Chat::SystemToPlayer("あなたの".$price."μショップです"));
				$player->sendMessage(Chat::SystemToPlayer("この商品は既に購入されました。商品を補充してください"));
				$this->soldout = false;
			}else{				
				$player->addWindow($this->inventory);
			}
		}else{
			$price = $this->getPrice();
			$list = $this->getItemNameList();	
			switch($this->buyCheck($player)){
				case -2:
					$message = "この商品は売り切れています";
				break;
				case -1:
					$message = "所持金が不足しているため購入出来ません";
				break;
				case 0:
					if(!$list){
						$message = "商品が入っていないため、購入出来ません";
					}else{	
						$player->sendMessage(Chat::SystemToPlayer($this->ownerName."さんの".$price."μショップです"));
						$message = $list."を".$price."μで購入しますか?";
					}
				break;
				case 1:
					//購入処理 #todo
					$ownerName = $this->ownerName;
					$ownerAC = Account::getByName($ownerName, true);
					$ownerMeu = $ownerAC->getMeu();
					if($ownerMeu === null){
						$message = "このショップは準備中のため、購入出来ません";
					}else{
						$meu = Account::get($player)->getMeu()->spilit($price);
						$ownerMeu->merge($meu, "個人ショップ: {$player->getName()}への売却・μ受取", "個人ショップ: {$ownerName}からの購入・μ支払い");
						$items = $this->inventory->getContents();
						$inv = $player->getInventory();
						foreach($items as $slot => $item){
							$inv->addItem($item);
						}
						$this->inventory->clearAll();
						$this->itemArray = [];
						$this->soldout = true;
						$message = "商品を購入しました";
					}
				break;				
			}
			$player->sendMessage(Chat::SystemToPlayer($message));
		}
		return false;
	}

	/**
	*	ブロック長押しされた時　キャンセルは不可
	*	@param Item そのブロックをタップしたアイテム
	*	@return bool
	*/
	public function StartBreak(Player $player){

	}

	/**
	*	ブロック長押しされ続け、壊された時
	*	trueが帰ると、DestroyBlockEventがキャンセルされる
	*	@param $x, $y, $z | 座標
	*	@param Item そのブロックをタップしたアイテム
	*	@return bool
	*/
	public function Break(Player $player){
		$name = $player->getName();
		if($name === $this->ownerName){
			$this->itemArray = $this->inventory->getItemArray();
			if(isset($this->itemArray[0])){
				$player->sendMessage(Chat::SystemToPlayer("§c商品が入っているため破壊できません"));
				return true;
			}
		}else{
			$player->sendMessage(Chat::SystemToPlayer("§c他人のショップは破壊できません"));
			return true;
		}
	}

	/**
	*	破壊された後の最終処理
	*	@return void
	*/
	public function Delete(){

	}

	public function getData(){
		$this->itemArray = $this->inventory->getItemArray();
		$data = [
			$this->ownerName,
			$this->itemArray,
			$this->shopType,
			$this->soldout,
		];
		return $data;
	}
	public function setData($data){
		$this->ownerName = $data[0];
		$this->itemArray = $data[1];
		$this->shopType = $data[2];
		$this->soldout = $data[3];
		return true;
	}

	public function getObjIndexNo(){
		return $this->indexNo;
	}

	public function getPrice(){
		if(isset(self::$price_index[$this->shopType])){
			return self::$price_index[$this->shopType];
		}
		return 0;
	}

	public function getShopName(){
		$price = $this->getPrice();
		$name = $this->ownerName."の".$price."μショップ";
		return $name;
	}

	/**
	*	商品一覧の名前を返す
	*	trueが帰ると、DestroyBlockEventがキャンセルされる
	*	@param String $separator 区切り文字
	*	@return String | bool
	*/
	public function getItemNameList($separator = "、"){
		$list = $this->inventory->getItemArray();
		if(!isset($list[0])){
			return false;//なんもはいってない
		}
		$str = "";
		$first = true;
		foreach($list as $item){
			$id = $item[0];
			$meta = $item[1];
			$count = $item[2];
			$name = ItemName::getNameOf($id, $meta);
			if(!$first){
				$str .= $separator;
			}else{
				$first = false;
			}
			$str .= $name."×".$count;
		}
		return $str;
	}

	/**
	*	購入のチェック処理
	*	@param Player $player
	*	@return -2 => 売り切れ, -1 => 所持金不足, 0 => 購入確認, 1 => 購入可
	*/
	public function buyCheck($player){
		$name = $player->getName();
		$now = microtime(true);
		$price = $this->getPrice();
		if($this->soldout){
			return -2;
		}
		if(!isset(self::$buyCheck[$this->indexNo][$name])){
			self::$buyCheck[$this->indexNo][$name] = $now;
			return 0;
		}
		if(self::$buyCheck[$this->indexNo][$name]+4 < $now){//前回のタップから4秒以上経過
			self::$buyCheck[$this->indexNo][$name] = $now;
			return 0;
		}elseif(Account::get($player)->getMeu()->getAmount() < $price){//所持金不足のチェック
			self::$buyCheck[$this->indexNo][$name] = 0;
			return -1;
		}else{
			self::$buyCheck[$this->indexNo][$name] = 0;
			return 1;
		}
	}

	public function getMeu($Ac){
    	$meu = $Ac->getMeu();
    	return $meu;//所持μ表示
	}

	public function getAccount($name){
		$Ac = Account::getByName($name, true);//AccountID取得
		$result=$Ac->loadData($name, true);
		if($result === false){
			return null;
		}
		return $Ac;
	}
}