<?php
namespace Eard\Event\BlockObject;


# Basic
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\math\Vector3;

use pocketmine\level\particle\FloatingTextParticle;

# Eard
use Eard\DBCommunication\Earmazon;
use Eard\Event\ChatManager;
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\License\License;
use Eard\Utils\Chat;
use Eard\Utils\ItemName;


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
		return true;
	}

	public function Break(Player $player){
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
			case 38:
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
				$playerData->setChatMode(ChatManager::CHATMODE_VOICE);
				if($no == 3){
					$this->sendPageData(16, $player);
				}elseif($no == 33){
					$this->sendPageData(49, $player);
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
				["アイテムを売る", 37],
			];
			$name = strtolower($player->getName());
			$playerData = Account::get($player);
			if( $playerData->hasValidLicense(License::GOVERNMENT_WORKER, License::RANK_GENERAL) ){
				$ar[] = ["管理画面へ", 100];
			}
			break;
		case 2:
			$playerData = Account::get($player);
			$playerData->setChatMode(ChatManager::CHATMODE_VOICE);
			$view->setMode(1);
			$view->setCategory(0);
			$view->setId(0, 0);
			$ar = [
				["{$thisname} 購入>トップ", false],
				["IDとダメージ値から検索",3],
				["カテゴリーから検索",4],
				["全アイテムを検索",27],
				["戻る", 1]
			];
			break;
		case 3:
			// 購入 検索 ID
			$playerData = Account::get($player);
			$playerData->setChatMode(ChatManager::CHATMODE_ENTER);
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
		case 20: case 21: case 22: case 23: case 24: // 1ページに5つ、アイテムがのってるから
			// 購入 商品
			$key = $no - 20;
			$unitno = $view->getKeys()[$key];
			if($unitno){
				$unit = Earmazon::getBuyUnit($unitno);
				if($unit){
					$id = $unit[0]; $meta = $unit[1]; $leftamount = $unit[2]; $unitprice = $unit[3];

					// 最大でいくつ買うことができる状態にあるかを出す
					$playerData = Account::get($player);
					$meuAmount = $playerData->getMeu()->getAmount();
					$canbuy = floor($meuAmount / $unitprice); // 今持ってる金で最大幾つかえるか
					$buyableamount = min(64, $leftamount, $canbuy); // 買える最大の数(ユニットの残数、プレイや0のお金でいくつかえるか、など)

					// そもそも、買える？
					if($buyableamount){
						$itemname = ItemName::getNameOf($id, $meta);

						// リスト作る
						$ar = [
							["{$thisname} 購入>確認", false],
							["{$itemname} をいくつ買いますか？", false],
						];

						// 最大購入可能数に応じて
						$pricear = [1,2,3,4,8,16,32,48,64];
						$cnt = 1;
						foreach($pricear as $key => $am){
							if($am <= $buyableamount){ // 買える個数より少なければリストに追加(まとめ買いは最大64個)
								$ar[] = ["{$am}個", 28 + $key, $cnt % 3 === 0 ? "\n" : " "];
								++$cnt;
							}
						}
					}else{
						$ar = [
							["{$thisname} 購入>エラー", false],
							["お金が足りないようです。", false],
							["(所持金: {$meuAmount}μ)", false],
							["戻る",27]
						];
					}

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
		case 28: case 29: case 30: case 31:
		case 32: case 33: case 34: case 35: case 36:
			$pricear = [1,2,3,4,8,16,32,48,64];
			$amount = $pricear[$no - 28];
			$playerData = Account::get($player);

			// 購入処理
			$result = Earmazon::playerBuy($view->no, $amount, $playerData);
			if($result){
				$ar = [
					["{$thisname} 購入>完了", false],
					["お買い上げありがとうございます。", false],
					["またのご利用をお待ちしております。", false],
					["購入トップへ戻る", 2]
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


		case 37:
			$view->setData([]); // まとめ売りのリセット
			$view->setMode(2);
			$view->setCategory(0);
			$view->setId(0, 0);
			$ar = [
				["{$thisname} 売却>トップ", false],
				["IDとダメージ値から検索",38],
				["カテゴリーから検索",39],
				["全アイテムを検索",57],
				["手持ちの売れるものを全部売る", 80],
				["戻る", 1]
			];
			break;
		case 38:
			// 売却 検索 ID
			$playerData = Account::get($player);
			$playerData->setChatMode(ChatManager::CHATMODE_ENTER);
			$playerData->setChatObject($this);
			$ar = [
				["{$thisname} 売却>検索", false],
				["159:9 や 21 のように",false],
				["数字と、メタ値がある場合は",false],
				[":を使い入力してください。",false],
				["戻る",37]
			]; // Chat経由で16へ
			break;
		case 39:
			// 売却 検索 カテゴリ
			$ar = [
				["{$thisname} 売却>検索", false],
				["一般ブロック", 40, " "],
				["装飾用ブロック", 41, " "],
				["鉱石系", 42],
				["設置ブロック", 43, " "],
				["草花",44, " "],
				["RS系統",45],
				["素材", 46, " "],
				["ツール", 47, " "],
				["食べ物", 48, " "],
				["戻る",37]
			];
			break;
		case 40: case 41: case 42: case 43: case 44: case 45: case 46: case 47: case 48:
			// 売却 検索 カテゴリ
			$category = $no - 39;

			$view->setId(0, 0);
			$view->setCategory($category);
			$ar = $this->makeList($player);
			break;
		case 49:
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
					$id = $unit[0]; $meta = $unit[1]; $leftamount = $unit[2]; $unitprice = $unit[3];
					$itemname = ItemName::getNameOf($id, $meta);
					$ar = [
						["{$thisname} 売却>確認", false],
						["{$itemname} をいくつ売りますか？", false],
						["戻る", 27, " "]
					];

					// 最大でいくつ売ることができる状態にあるか
					/*
					// todo: インベントリからあいてむこすうしゅとくしてぶんまわしするやつ
					$playerData = Account::get($player);
					$meuAmount = $playerData->getMeu()->getAmount();
					$canbuy = floor($meuAmount / $unitprice); // 今持ってる金で最大幾つかえるか
					if(64 <= $canbuy && 64 <= $leftamount){
						$buyableamount = 64;
					}else{
						$buyableamount = ($leftamount < $canbuy) ? $leftamount : $canbuy;
					}
					*/
					// スタックされている中で一番多いindexをさがし
					$items = $player->getInventory()->getContents();
					$sellableamount = 0;
					foreach($items as $index => $item){
						if($item->getId() == $id && $item->getDamage() == $meta){
							$sellableamount = $sellableamount < $item->getCount() ? $item->getCount() : $sellableamount;
						}
					}

					// リスト作る
					$pricear = [1,2,3,4,8,16,32,48,64];
					$cnt = 2;
					foreach($pricear as $key => $am){
						if($am <= $sellableamount){ // 買える個数より少なければリストに追加(まとめ買いは最大64個)
							$ar[] = ["{$am}個", 58 + $key, $cnt % 5 === 0 ? "\n" : " "];
							++$cnt;
						}
					}

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
		case 58: case 59: case 60: case 61: 
		case 62: case 63: case 64: case 65: case 66:
			$pricear = [1,2,3,4,8,16,32,48,64];
			$amount = $pricear[$no - 58];
			$playerData = Account::get($player);

			// 売却処理
			$result = Earmazon::playerSell($view->no, $amount, $playerData);
			if($result){
				$ar = [
					["{$thisname} 売却>完了", false],
					["査定は終了です。", false],
					["またのご利用をお待ちしております。", false],
					["戻る", 37]
				];	
				unset($this->input[$player->getName()]);
			}else{
				$ar = [
					["{$thisname} 売却>エラー", false],
					["何かのエラー", false],
					["戻る", 57]
				];
			}
			break;

		case 80:
		/*
			$sellItems = [
				$invのindexNo => [$id, $meta, $amount, $price]
			];
		*/
			$inv = $player->getInventory();
			$sellItems = []; // 売るアイテム
			$itemtxts = []; // アイテム確認用
			$pay = 0; // 価格確認用
			foreach($inv->getContents() as $slotIndex => $item){ // インベントリ中をぶん回し
				$itemid = $item->getId(); $itemmeta = $item->getDamage();
				$unitData = Earmazon::searchSellUnitById($itemid, $itemmeta); // そのアイテムが、売れるか
				if($unitData){ // 売れるようであれば
					$highest = 0; // 
					$unit = [];
					// uNitDataは複数あるのでそれをチェック 一番高い値段で売ろうと試みる
					foreach($unitData as $u){ // leftamountのチェックはしてない
						$unitprice = $u[3];
						if($highest < $unitprice){
							$highest = $unitprice;
							$unit = $u;
						}
					}
					if($unit){
						$itemname = ItemName::getNameOf($itemid, $itemmeta);
						$itemamount = $item->getCount();
						$sellItems[$slotIndex] = $unit;
						$pay = $pay + $highest * $itemamount;
						$itemtxts[] = "{$itemname}x{$itemamount}";
					}
				}
			}

			$itemtxt = "";
			$cnt = 1;
			foreach($itemtxts as $t){
				$selector = $cnt % 2 == 0 ? "\n" : " "; 
				$itemtxt .= "{$t}{$selector}";
				++$cnt;
			}
			$ar = [
				["{$thisname} まとめて売却>確認", false],
				["{$itemtxt}を売って{$pay}μを得る予定です。\n売りに出しますか？", false],
				["いいえ", 37, " "],
				["はい", 81]
			];
			$view->setData($sellItems);
		break;
		case 81:
			// Earmazonに専用めそっどを追加しろ
			$playerData = Account::get($player);
			$sellItems = $view->getData();
			foreach($sellItems as $invIndex => $unit){
				$amount = $player->getInventory()->getItem($invIndex)->getCount();
				$unitno = $unit[4];
				Earmazon::playerSell($unitno, $amount, $playerData);
			}
			$ar = [
				["{$thisname} まとめて売却>完了", false],
				["査定は終了です。", false],
				["またのご利用をお待ちしております。", false],
				["売却へ戻る", 37]
			];
		break;




		case 100: // 管理画面
			$ar = [
				["管理画面", false],
				["販売アイテムを追加", 101]
			];
			break;
		case 101:
			$data = $view->getData();
			$itemid = isset($data[0]) ? $data[0] : 0;
			$itemmeta = isset($data[1]) ? $data[1] : 0;
			$itemamount = isset($data[2]) ? $data[2] : 1;
			$itemprice = isset($data[3]) ? $data[3] : 100;
			$itemname = ItemName::getNameOf($itemid, $itemmeta);
			$ar = [
				["管理画面 販売アイテム追加", false],
				["追加アイテム: {$itemname}\n{$itemid}:{$itemmeta}", 102],
				["追加数量: {$itemamount}", 105],
				["販売価格: {$itemprice}", 106],
			];
			break;
		case 102:
			$ar = [
				["管理画面 販売アイテム追加>アイテム検索", false],
				[],
			];
			break;
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