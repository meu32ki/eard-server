<?php
namespace Eard\BlockObject;


# Basic
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\math\Vector3;

use pocketmine\level\particle\FloatingTextParticle;

# Eard
use Eard\Utils\ItemName;
use Eard\Chat;
use Eard\Earmazon;
use Eard\Account;


/****
*
*	ショップ
*/
class EarmazonShop implements BlockObject, ChatInput {

/********************
	BlockObject
********************/

	public $x, $y, $z;
	public $indexNo;
	public static $objNo = 3;

	public $name = "Earmazon";

	public function Place(Player $player){
		$name = $player->getName();

		$particle = new FloatingTextParticle(new Vector3($this->x + 0.5, $this->y + 2, $this->z + 0.5), "", $this->name);
		$level = $player->getLevel()->addParticle($particle);

		$this->particle = $particle;
	}

	public function Tap(Player $player){
		$this->MenuTap($player);
		return true; //キャンセルしないと、手持ちがブロックだった時に置いてしまう
	}

	public function StartBreak(Player $player){
		$this->MenuLongTap($player);
		$name = strtolower($player->getName());
		if($name === "meu32ki"){
			return false; //ｊこわせる
		}
		return true;
	}

	public function Break(Player $player){
		$name = strtolower($player->getName());
		if($name === "meu32ki"){
			return false; //ｊこわせる
		}
		return true;
	}

	public function Delete(){
		// floatingtextparcicle
	}

	public function getData(){
		$data = [];
		return $data;
	}
	public function setData($data){
		return true;
	}

	public function getObjIndexNo(){
		return $this->indexNo;
	}

	public function Chat(Player $player, String $txt){
		$no = $this->menu[$player->getName()][0];
		switch($no){
			case 3:
			case 33:
				$ar = explode(":", $txt);
				$cnt = count($ar);
				if($cnt === 2){
					$id = $ar[0];
					$meta = $ar[1];
					if(! ((int) $id)){
						$player->sendMessage( Chat::Format("Earmazon", "§6個人", "入力は数字でおねがいします") );
					}
				}elseif($cnt === 1){
					$id = $ar[0];
					$meta = 0;
					if(! ((int) $id)){
						$player->sendMessage( Chat::Format("Earmazon", "§6個人", "入力は数字でおねがいします") );
					}
				}else{
					$player->sendMessage( Chat::Format("Earmazon", "§6個人", "入力は数字でおねがいします") );
				}
				// getPageArのために
				$view = $this->input[$player->getName()];
				$view->setId($id, $meta);
				// がんばれー

				$playerData = Account::get($player);	
				$playerData->setChatMode(Chat::CHATMODE_VOICE);
				if($no == 3){
					$this->sendPageData(16, $player);
				}elseif($no == 33){
					$this->sendPageData(46, $player);
				}
			break;
		}
	}


/********************
	BlockMenu
********************/

	use BlockMenu;

	public function getPageAr($no, $player){
		// 個人の、ボタン配置などを記録
		if(isset($this->input[$player->getName()])){
			$view = $this->input[$player->getName()];
		}else{
			//オブジェクト作る
			$view = new ShopView();
			$this->input[$player->getName()] = $view;
		}


		$thisname = $this->name;
		switch($no){
		case 1:
			$ar = [
				["{$thisname}", false],
				["アイテムを買う", 2],
				["アイテムを売る", 32],
			];
			$name = strtolower($player->getName());
			if($name === "meu32ki" || $name === "32ki"){
				$ar[] = ["管理画面へ", 100];
			}
			break;
		case 2:
			$view->setMode(1);
			$view->setCategory(0);
			$view->setId(0, 0);
			$ar = [
				["{$thisname} 購入", false],
				["IDとダメージ値から検索",3],
				["カテゴリーから検索",4],
				["全アイテムを検索",27],
				["戻る", 1]
			];
			break;
		case 3:
			// 購入 検索 ID
			$playerData = Account::get($player);
			$playerData->setChatMode(Chat::CHATMODE_ENTER);
			$playerData->setChatObject($this);
			$ar = [
				["{$thisname} 購入>検索", false],
				["159:9 や 21 のように",false],
				["数字と、メタ値がある場合は",false],
				[":を使い入力してください。",false],
				["戻る",2]
			]; // Chat経由で16へ
			break;
		case 4:
			// 購入 検索 カテゴリ
			$ar = [
				["{$thisname} 購入>検索", false],
				["一般ブロック",5, " "],
				["装飾用ブロック",6, " "],
				["鉱石系",7],
				["設置ブロック", 8, " "],
				["草花",9, " "],
				["RS系統",10],
				["素材", 11, " "],
				["ツール", 12, " "],
				["食べ物", 13, " "],
				["戻る",2]
			];
			break;
		case 5:	case 6: case 7: case 8: case 9: case 10: case 11: case 12: case 13:
			// 購入 検索 カテゴリ
			$category = $no - 4;

			$view->setId(0, 0);
			$view->setCategory($category);
			$ar = $this->makeList($player);
			break;
		case 16:
			// 購入 検索 ID
			$id = $view->id;
			$meta = $view->meta;

			$view->setCategory(0);
			$view->setId($id, $meta);
			$ar = $this->makeList($player);
			break;
		case 20: case 21: case 22: case 23: case 24:
			// 購入 商品
			$key = $no - 20;
			$unitno = $view->getKeys()[$key];
			if($unitno){
				$unit = Earmazon::getBuyUnit($unitno);
				if($unit){
					$id = $unit[0]; $meta = $unit[1];
					$itemname = ItemName::getNameOf($id, $meta);
					$ar = [
						["{$thisname} 購入>決定", false],
						["{$itemname}", false],
						["64つ 購入", 30],
						["10つ 購入", 29],
						["1つ 購入", 28],
						["戻る", 27]
					];
					$view->setUnitNo($unitno);
				}else{
					$ar = [
						["{$thisname} 購入>検索", false],
						["何かのエラー。", false],
						["戻る", 27]
					];					
				}
			}else{
				// 起こりえない
			}
			break;
		case 25: case 26: case 27:
			// 購入 次ページへボタン
			if($no === 25) $view->setPage($view->page + 1); // 購入 次ページへボタン
			if($no === 26) $view->setPage($view->page - 1); // 購入 前ページへボタン
			// 27の場合はなにもしない
			$ar = $this->makeList($player);
			break;
		case 28: case 29: case 30:
			$pricear = [1,10,64];
			$amount = $pricear[$no - 28];
			$playerData = Account::get($player);

			// 購入処理
			$result = Earmazon::playerBuy($view->no, $amount, $playerData);
			if($result){
				$ar = [
					["{$thisname} 購入", false],
					["購入完了", false],
					["戻る", 1]
				];	
				unset($this->input[$player->getName()]);
			}else{
				$ar = [
					["{$thisname} 購入", false],
					["何かのエラー", false],
					["戻る", 27]
				];
			}
			break;


		case 32:
			$view->setMode(2);
			$view->setCategory(0);
			$view->setId(0, 0);
			$ar = [
				["{$thisname} 売却", false],
				["IDとダメージ値から検索",33],
				["カテゴリーから検索",34],
				["全アイテムを検索",27],
				// ["手持ちから売れるものを検索", ]
				["戻る", 1]
			];
			break;
		case 33:
			// 売却 検索 ID
			$playerData = Account::get($player);
			$playerData->setChatMode(Chat::CHATMODE_ENTER);
			$playerData->setChatObject($this);
			$ar = [
				["{$thisname} 売却>検索", false],
				["159:9 や 21 のように",false],
				["数字と、メタ値がある場合は",false],
				[":を使い入力してください。",false],
				["戻る",32]
			]; // Chat経由で16へ
			break;
		case 34:
			// 売却 検索 カテゴリ
			$ar = [
				["{$thisname} 売却>検索", false],
				["一般ブロック",35, " "],
				["装飾用ブロック",36, " "],
				["鉱石系",37],
				["設置ブロック", 38, " "],
				["草花",39, " "],
				["RS系統",40],
				["素材", 41, " "],
				["ツール", 42, " "],
				["食べ物", 43, " "],
				["戻る",32]
			];
			break;
		case 35: case 36: case 37: case 38: case 39: case 40: case 41: case 42: case 43:
			// 売却 検索 カテゴリ
			$category = $no - 34;

			$view->setId(0, 0);
			$view->setCategory($category);
			$ar = $this->makeList($player);
			break;
		case 46:
			// 売却 検索 ID
			$id = $view->id;
			$meta = $view->meta;

			$view->setCategory(0);
			$view->setId($id, $meta);
			$ar = $this->makeList($player);
			break;
		case 50: case 51: case 52: case 53: case 54:
			// 売却 商品
			$key = $no - 50;
			$unitno = $view->getKeys()[$key];
			if($unitno){
				$unit = Earmazon::getSellUnit($unitno);
				if($unit){
					$id = $unit[0]; $meta = $unit[1];
					$itemname = ItemName::getNameOf($id, $meta);
					$ar = [
						["{$thisname} 売却>決定", false],
						["{$itemname}", false],
						["64つ 売却", 60],
						["10つ 売却", 59],
						["1つ 売却", 58],
						["戻る", 57]
					];
					$view->setUnitNo($unitno);
				}else{
					$ar = [
						["{$thisname} 売却>検索", false],
						["何かのエラー。", false],
						["戻る", 57]
					];					
				}
			}else{
				// 起こりえない
			}
			break;
		case 55: case 56: case 57:
			// 売却 次ページへボタン
			if($no === 55) $view->setPage($view->page + 1); // 売却 次ページへボタン
			if($no === 56) $view->setPage($view->page - 1); // 売却 前ページへボタン
			// 27の場合はなにもしない
			$ar = $this->makeList($player);
			break;
		case 58: case 59: case 60:
			$pricear = [1,10,64];
			$amount = $pricear[$no - 58];
			$playerData = Account::get($player);

			// 売却処理
			$result = Earmazon::playerSell($view->no, $amount, $playerData);
			if($result){
				$ar = [
					["{$thisname} 売却", false],
					["売却完了", false],
					["戻る", 1]
				];	
				unset($this->input[$player->getName()]);
			}else{
				$ar = [
					["{$thisname} 売却", false],
					["何かのエラー", false],
					["戻る", 57]
				];
			}
			break;




		case 100: // 管理画面
			$ar = [

			];
		default: 
			$ar = [
				["{$thisname}", false],
				["ページがありません",1]
			];
		break;
		}
		return $ar;
	}


	/**
	*	カテゴリから/検索idから つくる
	*	@param Player $player
	*	@return Array $pageAr
	*/
	public function makeList($player){
		$view = $this->input[$player->getName()];
		$thisname = $this->name;

		// 検索方法を選択
		$ar = [];
		$mode = $view->getMode();
		if( $mode === 1){
			$modetxt = "購入";
			if($view->category){
				$unitar = Earmazon::searchBuyUnitByCategory($view->category);
			}elseif($view->id){
				$unitar = Earmazon::searchBuyUnitById($view->id, $view->meta);
			}else{
				$unitar = Earmazon::searchBuyUnit();
			}
		}elseif($mode === 2){
			$modetxt = "売却";
			if($view->category){
				$unitar = Earmazon::searchSellUnitByCategory($view->category);
			}elseif($view->id){
				$unitar = Earmazon::searchSellUnitById($view->id, $view->meta);
			}else{
				$unitar = Earmazon::searchSellUnit();
			}
		}else{
			echo "ここに来るべきではない\n";
			return false;
		}

		// リストにアイテムがあったか？
		if($unitar){
			$ar[] = ["{$thisname} {$modetxt}>検索", false];
			// はいってたら
			$cnt = $mode === 1 ? 20 : 50; // modeによって、メニューgetPageAr()の番号が違うから
			// まず、複数ページに分ける必要があるか？
			if(5 < count($unitar)){
				// あるわ = 次へ進むページとか作らねばならない
				$page = $view->page;
				$nowfirst = 5 * ($page - 1);
				if(isset( $unitar[$nowfirst + 5] )){
					$ar[] = ["次ページへ", 25];
				}
				if(isset( $unitar[$nowfirst - 5] )){
					$ar[] = ["前ページへ", 26];
				}
				$unitar = array_slice($unitar, ($page - 1) * 5, 5);
			}

			// リストの中身
			$keys = [];
			foreach($unitar as $data){
				$itemName = ItemName::getNameOf($data[0], $data[1]);
				$ar[] = ["{$itemName} §f{$data[3]}μ§7/個 残り{$data[2]}個", $cnt];
				$keys[] = $data[4]; // buylist / selllist の no;
				$cnt ++;
			}

			if($view->category){
				$ar[] = ["カテゴリ検索に戻る",4];
			}elseif($view->id){
				$ar[] = ["ID検索に戻る", 3];
			}else{
				$ar[] = ["戻る", 2];
			}

			$view->setKeys($keys);
		}else{
			// からだったら
			$ar = [
				["{$thisname} {$modetxt}>検索", false],
				["何もありませんでした",false],
			];

			if($view->category){
				$ar[] = ["カテゴリ検索に戻る",4];
			}elseif($view->id){
				$ar[] = ["ID検索に戻る", 3];
			}else{
				$ar[] = ["戻る", 2];
			}
		}
		return $ar;
	}

}


class ShopView{


	public function __construct(){

	}

	/**
	*	@param int 1 = buy 2 = sell
	*/
	public function setMode($mode){
		$this->mode = $mode;
	}
	public function getMode(){
		return $this->mode;
	}

	/**
	*	@param int
	*/
	public function setCategory($category){
		$this->category = $category;
	}

	/**
	*	@param int
	*/
	public function setId($id, $meta){
		$this->id = $id;
		$this->id = $meta;
	}

	/**
	*	@param int
	*/
	public function setPage($page){
		$this->page = $page;
	}

	/**
	*	@param Array
	*/
	public function setKeys($keys){
		$this->keys = $keys;
	}
	public function getKeys(){
		return $this->keys;
	}


	public function setUnitNo($unitno){
		$this->no = $unitno;
	}

	public $page = 1;
	public $id = 0;
	public $meta = 0;
	public $category = 0;
	public $keys = [];
	public $mode = 0;
	public $no;
}