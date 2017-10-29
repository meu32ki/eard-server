<?php
namespace Eard\Form;


# basic
use pocketmine\Server;
use pocketmine\item\Item;

# Eard
use Eard\DBCommunication\Earmazon;
use Eard\Utils\Chat;
use Eard\Utils\ItemName;
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\License\License;
use Eard\Form\EarmazonAdminForm;


class EarmazonForm extends FormBase {

	public function send(int $id){
		$playerData = $this->playerData;
		$name = $playerData->getName();
		$cache = [];

		if(isset($this->input[$name])){
			$view = $this->input[$name];
		}else{
			$view = new ShopView();
			$this->input[$name] = $view;
		}

		switch($id){
			case 1:

        $buttons = [
          ['text' => "アイテムを買う"],
          ['text' => "アイテムを売る"],
        ];
        $cache = [2, 37];

  			if($playerData->hasValidLicense(License::GOVERNMENT_WORKER, License::RANK_GENERAL)){
  				$buttons[] = ['text' => "管理画面へ"];
          $cache[] = 100;
  			}

        $data = [
					'type'    => "form",
					'title'   => "Earmazon",
					'content' => "",
					'buttons' => $buttons
				];

			break;
      case 2:

        $view->setMode(1);
        $view->setCategory(0);
        $view->setId(0, 0);
        $buttons = [
          ['text' => "IDとダメージ値から検索"],
          ['text' => "カテゴリーから検索"],
          ['text' => "全アイテムを検索"],
          ['text' => "戻る"]
        ];
        $cache = [3, 4, 27, 1];

        $data = [
          'type'    => "form",
          'title'   => "Earmazon 購入>トップ",
          'content' => "",
          'buttons' => $buttons
        ];

      break;
      case 3:
        // 購入 検索 ID
        // $buttons = [
        //   ['text' => "戻る"]
        // ]; // 16へ
        // $cache = [1];

        $content =
            "\n159:9 や 21 のように\n".
            "数字と、メタ値がある場合は、「:」を使い入力してください。\n";

				$data = [
						'type'    => "custom_form",
						'title'   => "Earmazon 購入>検索",
						'content' => [
							[
								'type' => "input",
								'text' => $content,
								'placeholder' => "ID : Meta"
							]
						]
				];
				$cache = [16];

      break;
      case 4:
        $buttons = [
          ['text' => "一般ブロック"],
          ['text' => "装飾用ブロック"],
          ['text' => "鉱石系"],
          ['text' => "設置ブロック"],
          ['text' => "草花"],
          ['text' => "RS系統"],
          ['text' => "素材"],
          ['text' => "ツール"],
          ['text' => "食べ物"],
          ['text' => "戻る"],
      ];
      $cache = [5, 6, 7, 8, 9, 10, 11, 12, 13, 2];

      $data = [
        'type'    => "form",
        'title'   => "Earmazon 購入>検索",
        'content' => "",
        'buttons' => $buttons
      ];

      break;
      case 5:	case 6: case 7: case 8: case 9: case 10: case 11: case 12: case 13:
        $category = $id - 4;

        $view->setId(0, 0);
        $view->setCategory($category);
        $array = $this->makeList($playerData->getPlayer());

				$data = [
	        'type'    => "form",
	        'title'   => $array[0],
	        'content' => $array[1],
	        'buttons' => $array[2]
	      ];
				$cache = $array[3];

      break;
			case 16:
				// 購入 検索 ID
				$txt = $this->lastData[0];
				$ar = explode(":", $txt);
				$cnt = count($ar);
				if($cnt === 2){
					$id = $ar[0];
					$meta = $ar[1];
					if(! ((int) $id)){
						$this->sendErrorModal(
							"Earmazon",
							"入力は数字でおねがいします", 1
						);
						break;
					}
				}elseif($cnt === 1){
					$id = $ar[0];
					$meta = 0;
					if(! ((int) $id)){
						$this->sendErrorModal(
							"Earmazon",
							"入力は数字でおねがいします", 1
						);
						break;
					}
				}else{
					$this->sendErrorModal(
						"Earmazon",
						"入力は数字でおねがいします", 1
					);
					break;
				}

				$view->setCategory(0);
				$view->setId($id, $meta);
				$array = $this->makeList($playerData->getPlayer());
				$data = [
	        'type'    => "form",
	        'title'   => $array[0],
	        'content' => $array[1],
	        'buttons' => $array[2]
	      ];
				$cache = $array[3];

			break;
			case 20: case 21: case 22: case 23: case 24: // 1ページに5つ、アイテムがのってるから
				// 購入 商品
				$key = $id - 20;
				$unitno = $view->getKeys()[$key];
				if($unitno){
					$unit = Earmazon::getBuyUnit($unitno);
					if($unit){
						$id_ = $unit[0]; $meta = $unit[1]; $leftamount = $unit[2]; $unitprice = $unit[3];

						// 最大でいくつ買うことができる状態にあるかを出す
						$meuAmount = $playerData->getMeu()->getAmount();
						$canbuy = floor($meuAmount / $unitprice); // 今持ってる金で最大幾つかえるか
						$buyableamount = min(64, $leftamount, $canbuy); // 買える最大の数(ユニットの残数、プレイや0のお金でいくつかえるか、など)

						$buttons = [];
						$cache = [];
						$ar = [];
						// そもそも、買える？
						if($buyableamount){
							$itemname = ItemName::getNameOf($id_, $meta);

							$title = "Earmazon 購入>確認";
							$content = "\n{$itemname} をいくつ買いますか？\n";
							//$buttons[] = ['text' => "戻る"];
							//$cache[] = 27;

							// 最大購入可能数に応じて
							$pricear = [1,2,3,4,8,16,32,48,64];
							$cnt = 1;
							foreach($pricear as $key => $am){
								if($am <= $buyableamount){ // 買える個数より少なければリストに追加(まとめ買いは最大64個)
									$ar[] = "{$am}個";
									++$cnt;
								}
							}
							$cache[] = 28;
						}else{
							$title = "Earmazon 購入>エラー";
							$content = "お金が足りないようです。(所持金: {$meuAmount}μ)";
							$buttons[] = ['text' => "戻る"];
							$cache[] = 27;
						}

						$view->setUnitNo($unitno);
					}else{
						$title = "Earmazon 購入>検索";
						$content = "何かのエラー。";
						$buttons[] = ['text' => "戻る"];
						$cache[] = 27;
					}
					$data = [
						'type'    => "custom_form",
						'title'   => $title,
						'content' => [
							[
								'type' => "dropdown",
								'text' => $content,
									'placeholder' => "個数",
									'options' => $ar
							]
						]
					];
				}else{
					// 起こりえない
				}
			break;
			case 25: case 26: case 27:

				// 購入 次ページへボタン
				if($id === 25) $view->setPage($view->page + 1); // 購入 次ページへボタン
				if($id === 26) $view->setPage($view->page - 1); // 購入 前ページへボタン
				// 27の場合はなにもしない
				$array = $this->makeList($playerData->getPlayer());
				$data = [
	        'type'    => "form",
	        'title'   => $array[0],
	        'content' => $array[1],
	        'buttons' => $array[2]
	      ];
				$cache = $array[3];

			break;
			case 28: case 29: case 30: case 31:
			case 32: case 33: case 34: case 35: case 36:

				$pricear = [1,2,3,4,8,16,32,48,64];
				$amount = $pricear[$this->lastData[0]];

				// 購入処理
				$result = Earmazon::playerBuy($view->no, $amount, $playerData);
				if($result){
					$title = "Earmazon 購入>完了";
					$content =
						"お買い上げありがとうございます。\n".
						"携帯から「アイテムボックス」をご確認ください。\n".
						"またのご利用をお待ちしております。\n";
					$buttons = [
						['text' => "購入トップへ戻る"]
					];
					$cache = [2];
					unset($this->input[$name]);
				}else{
					$title = "Earmazon 購入";
					$content = "何かのエラー";
					$buttons = [
						['text' => "戻る"]
					];
					$cache = [27];
				}

				$data = [
	        'type'    => "form",
	        'title'   => $title,
	        'content' => $content,
	        'buttons' => $buttons
	      ];

			break;


			case 37:

				$view->setData([]); // まとめ売りのリセット
				$view->setMode(2);
				$view->setCategory(0);
				$view->setId(0, 0);

				$buttons = [
					['text' => "IDとダメージ値から検索"],
					['text' => "カテゴリーから検索"],
					['text' => "全アイテムを検索"],
					['text' => "手持ちの売れるものを全部売る"],
					['text' => "戻る"]
				];
				$cache = [38, 39, 57, 80, 1];

				$data = [
					'type'    => "form",
					'title'   => "Earmazon 売却>トップ",
					'content' => "",
					'buttons' => $buttons
				];

			break;
			case 38:

				// 売却 検索 ID
				$playerData->setChatObject($this);
				$content =
            "\n159:9 や 21 のように\n".
            "数字と、メタ値がある場合は、「:」を使い入力してください。\n";
				$data = [
						'type'    => "custom_form",
						'title'   => "Earmazon 売却>検索",
						'content' => [
							[
								'type' => "input",
								'text' => $content,
								'placeholder' => "ID : Meta"
							]
						]
				];
				$cache = [16];

			break;
			case 39:

				// 売却 検索 カテゴリ
				$buttons = [
					['text' => "一般ブロック"],
					['text' => "装飾用ブロック"],
					['text' => "鉱石系"],
					['text' => "設置ブロック"],
					['text' => "草花"],
					['text' => "RS系統"],
					['text' => "素材"],
					['text' => "ツール"],
					['text' => "食べ物"],
					['text' => "戻る"],
				];
				$cache = [40, 41, 42, 43, 44, 45, 46, 47, 48, 37];

				$data = [
					'type'    => "form",
					'title'   => "Earmazon 売却>検索",
					'content' => "",
					'buttons' => $buttons
				];

			break;
			case 40: case 41: case 42: case 43: case 44: case 45: case 46: case 47: case 48:
				// 売却 検索 カテゴリ
				$category = $id - 39;

				$view->setId(0, 0);
				$view->setCategory($category);
				$array = $this->makeList($playerData->getPlayer());
				$data = [
	        'type'    => "form",
	        'title'   => $array[0],
	        'content' => $array[1],
	        'buttons' => $array[2]
	      ];

				$cache = $array[3];

			break;
			case 50: case 51: case 52: case 53: case 54:
				// 売却 商品
				$key = $id - 50;
				$unitno = $view->getKeys()[$key];
				if($unitno){
					$unit = Earmazon::getSellUnit($unitno);
					if($unit){
						$id = $unit[0]; $meta = $unit[1]; $leftamount = $unit[2]; $unitprice = $unit[3];
						$itemname = ItemName::getNameOf($id, $meta);
						$title = "Earmazon 売却>確認";
						$content = "\n{$itemname} をいくつ売りますか？\n";


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
						$items = $playerData->getPlayer()->getInventory()->getContents();
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
								$ar[] = "{$am}個";
								++$cnt;
							}
						}

						$view->setUnitNo($unitno);
					}else{
						$title = "Earmazon 売却>検索";
						$content = "何かのエラー";
						$buttons = [
							['text' => "戻る"]
						];
						$cache = [57];
					}
					$data = [
						'type'    => "custom_form",
						'title'   => $title,
						'content' => [
							[
								'type' => "dropdown",
								'text' => $content,
									'placeholder' => "個数",
									'options' => $ar
							]
						]
					];
					$cache = [58];
				}else{
					// 起こりえない
				}
				break;
				case 55: case 56: case 57:

					// 売却 次ページへボタン
					if($id === 55) $view->setPage($view->page + 1); // 売却 次ページへボタン
					if($id === 56) $view->setPage($view->page - 1); // 売却 前ページへボタン
					// 57の場合はなにもしない
					$array = $this->makeList($playerData->getPlayer());
					$data = [
		        'type'    => "form",
		        'title'   => $array[0],
		        'content' => $array[1],
		        'buttons' => $array[2]
		      ];
					$cache = $array[3];

				break;
				case 58: case 59: case 60: case 61:
				case 62: case 63: case 64: case 65: case 66:
					$pricear = [1,2,3,4,8,16,32,48,64];
					$amount = $pricear[$this->lastData[0]];

					// 売却処理
					$result = Earmazon::playerSell($view->no, $amount, $playerData);
					if($result){
						$title = "Earmazon 売却>完了";
						$content =
							"査定は終了です。\n".
							"またのご利用をお待ちしております。\n";
						$buttons = [
							['text' => "戻る"]
						];
						$cache = [37];
						unset($this->input[$name]);
					}else{
						$title = "Earmazon 売却>エラー";
						$content = "何かのエラー";
						$buttons = [
							['text' => "戻る"]
						];
						$cache = [57];
					}

					$data = [
		        'type'    => "form",
		        'title'   => $title,
		        'content' => $content,
		        'buttons' => $buttons
		      ];

				break;
				case 80:
				/*
					$sellItems = [
						$invのindexNo => [$id, $meta, $amount, $price]
					];
				*/
					$inv = $playerData->getPlayer()->getInventory();
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

					if(!$itemtxts){
						$this->sendErrorModal(
							"Earmazon まとめて売却",
							"売れるアイテムはないようです。", 37
						);
					}else{
						$itemtxt = "";
						$cnt = 1;
						foreach($itemtxts as $t){
							$selector = $cnt % 2 == 0 ? "\n" : " ";
							$itemtxt .= "{$t}{$selector}";
							++$cnt;
						}

						$data = [
							'type'    => "modal",
							'title'   => "Earmazon まとめて売却 > 確認",
							'content' => "{$itemtxt}を売って{$pay}μを得る予定です。\n売りに出しますか？",
								'button1' => "いいえ",
								'button2' => "はい",
						];
						$cache = [37, 81];
						$view->setData($sellItems);
					}
			break;
			case 81:

				// Earmazonに専用めそっどを追加しろ
				$sellItems = $view->getData();
				foreach($sellItems as $invIndex => $unit){
					$amount = $playerData->getPlayer()->getInventory()->getItem($invIndex)->getCount();
					$unitno = $unit[4];
					Earmazon::playerSell($unitno, $amount, $playerData);
				}
				$data = [
		       'type'    => "form",
		       'title'   => "Earmazon まとめて売却>完了",
		       'content' =>	"\n査定は終了です。\n".
												"またのご利用をお待ちしております。\n",
		       'buttons' => [
						['text' => "売却へ戻る"]
					]
		     ];
				$cache = [37];

			break;

			case 100: // 管理画面
				new EarmazonAdminForm($playerData);
			break;
			default:
				$data = [
					'type'    => "form",
					'title'   => "Earmazon",
					'content' => "ページがありません",
					'buttons' => [
						['text' => '戻る']
					]
				];
				$cache = [1];
			break;

		}

		// みせる
		if($cache){
			// sendErrorMoralのときとかは動かないように
			$this->lastSendData = $data;
			$this->cache = $cache;
			$this->show($id, $data);
		}else{
			// echo "formIdが1000と表示されていれば送信済みでもそれいがいならcacheが設定されていないので送られてない\n";
		}
	}

  /**
	*	カテゴリから/検索idから つくる
	*	@param Player $player
	*	@return Array $pageAr
	*/
	public function makeList($player){
		$view = $this->input[$player->getName()];

		// 検索方法を選択
		$buttons = [];
    $cache = [];
		$title = "";
		$content = "";

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
			$title = "Earmazon {$modetxt}>検索";
			// はいってたら
			$cnt = $mode === 1 ? 20 : 50; // modeによって、メニューgetPageAr()の番号が違うから
			// まず、複数ページに分ける必要があるか？
			if(5 < count($unitar)){
				// あるわ = 次へ進むページとか作らねばならない
				$page = $view->page;
				$nowfirst = 5 * ($page - 1);
				if(isset( $unitar[$nowfirst + 5] )){
					$buttons[] = ['text' => "次ページへ"];
          $cache[] = 25;
				}
				if(isset( $unitar[$nowfirst - 5] )){
					$buttons[] = ['text' => "前ページへ"];
          $cache[] = 26;
				}
				$unitar = array_slice($unitar, ($page - 1) * 5, 5);
			}

			// リストの中身
			$keys = [];
			foreach($unitar as $data){
				$itemName = ItemName::getNameOf($data[0], $data[1]);
        $buttons[] = ['text' => "§0{$itemName} §8{$data[3]}μ§0/個 残り§8{$data[2]}§0個"];
        $cache[] = $cnt;
				$keys[] = $data[4]; // buylist / selllist の no;
				$cnt ++;
			}

			if($view->category){
        $buttons[] = ['text' => "カテゴリ検索に戻る"];
        $cache[] = 4;
			}elseif($view->id){
        $buttons[] = ['text' => "ID検索に戻る"];
        $cache[] = 3;
			}else{
        $buttons[] = ['text' => "戻る"];
        $cache[] = 2;
			}

			$view->setKeys($keys);
		}else{
			// からだったら
			$title = "Earmazon {$modetxt}>検索";
			$content = "何もありませんでした";

			if($view->category){
        $buttons[] = ['text' => "カテゴリ検索に戻る"];
        $cache[] = 4;
			}elseif($view->id){
        $buttons[] = ['text' => "ID検索に戻る"];
        $cache[] = 3;
			}else{
        $buttons[] = ['text' => "戻る"];
        $cache[] = 2;
			}
		}

		$array = [$title, $content, $buttons, $cache];
		return $array;
	}

	public $id, $meta = 0;
	public $amount, $price = 0;

	public $input;

	private $categoryNo = 0;
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



	public function setData($data){
		$this->data = $data;
	}
	public function getData(){
		return $this->data;
	}

	public $page = 1;
	public $id = 0;
	public $meta = 0;
	public $category = 0;
	public $keys = [];
	public $mode = 0;
	public $no;
	private $data = [];
}