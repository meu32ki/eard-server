<?php
namespace Eard\BlockObject;


# Basic
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\math\Vector3;

use pocketmine\level\particle\FloatingTextParticle;

# Eard
use Eard\BlockObject\ItemName;
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
				$ar = explode(":", $string);
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
				$this->input[$player->getName()][1] = [$id,$meta];
				// がんばれー
				$playerData->setChatMode(Chat::CHATMODE_PLAYER);
				$this->sendPageData(16, $player);
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
				["アイテムを売る", 5],
			];
			$name = strtolower($player->getName());
			if($name === "meu32ki" && $name === "32ki"){
				$ar[] = ["管理画面へ", 50];
			}
			break;
		case 2:
			$view->setMode(1);
			$ar = [
				["{$thisname} 購入", false],
				["IDとダメージ値から検索",3],
				["カテゴリーから検索",4],
				["全アイテムを検索",27],
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
			]; // Chat経由で16へ
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
				["戻る",2],
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
			$data = $this->input[$player->getName()][1];
			$id = $data[0];
			$meta = $data[1];

			$view->setCategory(0);
			$view->setId($id, $meta);
			$ar = $this->makeList($player);
			break;
		case 20: case 21: case 22: case 23: case 24:
			// 購入 商品
			$key = $no - 20;
			$unitno = $this->getKeys()[$key];
			if($unitno){
				$unit = Earmazon::getBuyUnit($unitno);
				$ar = [
					["{$thisname} 購入>決定", false],
					["1つ 購入", 28],
					["1つ 購入", 28],
					["戻る", 27]
				];
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



			
		case 50:
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
	*	つくる
	*	@param Player $player
	*	@return Array $pageAr
	*/
	public function makeList($player){
		$view = $this->input[$player->getName()];
		$thisname = $this->name;

		// 検索方法を選択
		if($view->category){
			$unitar = Earmazon::searchBuyUnitByCategory($view->category);
		}elseif($view->id){
			$unitar = Earmazon::searchBuyUnitById($view->id, $view->meta);
		}else{
			$unitar = Earmazon::searchBuyUnit();
		}

		// リストにアイテムがあったか？
		if($unitar){
			// はいってたら
			$ar = [];
			$cnt = $flag === 1 ? 20 : 0;
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
			}

			$keys = [];
			foreach($unitar as $data){
				$itemName = ItemName::getNameOf($data[0], $data[1]);
				$ar[] = ["{$itemName} {$data[3]}μ/個 残り{$data[2]}個", $cnt];
				$keys[] = $data[4];
				$cnt ++;
			}

			if($view->category){
				$ar[] = ["カテゴリ検索に戻る",4];
			}elseif($view->id){
				$ar[] = ["ID検索に戻る", 3];
			}

			$view->setKey($keys);
		}else{
			// からだったら
			$ar = [
				["{$thisname} 購入>検索", false],
				["何もありませんでした",false],
				["戻る", 4],
			];
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
	public function getKeys($keys){
		return $this->keys;
	}


	public $page = 1;
	public $id = 0;
	public $meta = 0;
	public $category = 0;
	public $keys = [];
	public $mode = 0;
}