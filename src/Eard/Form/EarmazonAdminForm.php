<?php
namespace Eard\Form;


# basic
use pocketmine\Server;
use pocketmine\item\Item;

# Eard
use Eard\DBCommunication\Earmazon;
use Eard\Utils\Chat;
use Eard\Utils\ItemName;
use Eard\MeuHandler\Account\License\License;


class EarmazonAdminForm extends FormBase {

	const NEXT = 5;
	const SEARCH = 100;

	/*
		アイテムを選択して、そのアイテムをどうするかを決めてって感じ。
	*/

	public function send(int $id){
		$playerData = $this->playerData;
		$cache = [];
		switch($id){
			case 1:
				// メニュー
				if($this->id == 0){
					$this->send(self::SEARCH);
					return false;
				}

				$buttons = [];

				$buttons[] = ['text' => "アイテム選択"];
				$cache[] = self::SEARCH;

				$license = $playerData->getLicense(license::GOVERNMENT_WORKER);
				$gwrank = $license instanceof License ? $license->getRank() : 0;
				if($this->id != 0){
					switch($gwrank){
						case 6: //長官
						case 5: //高官
						case 4: //次官
							$buttons[] = ['text' => "無から在庫を生成する程度の能力"];
							$cache[] = 4;
						case 3: //係員
							$buttons[] = ['text' => "買取アイテム追加\nプレイヤーがμを得る"];
							$cache[] = 2;

							$buttons[] = ['text' => "販売アイテムを出す\nプレイヤーがアイテムを得る"];	
							$cache[] = 3;
						case 2: //研修者
							$buttons[] = ['text' => "在庫を引き出す奴"]; //todo
							$cache[] = 5;
						case 1: //土木技術者
							;
						break;
					}
					$itemName = ItemName::getNameOf($this->id, $this->meta);
					$quantity = Earmazon::getStorageAmount($this->id, $this->meta);
					$content = "§e「{$itemName}」 §7ID:§f{$this->id} §7Damage:§f{$this->meta} §7Qty:§f{$quantity}";
				}else{
					$content = "";
				}

				$data = [
					'type'    => "form",
					'title'   => "Earmazon管理",
					'content' => $content,
					'buttons' => $buttons,
				];
			break;
/*
	買取アイテム
*/
			case 2:
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$quantity = Earmazon::getStorageAmount($this->id, $this->meta);
				$content = "§e「{$itemName}」 §7ID:§f{$this->id} §7Damage:§f{$this->meta} §7Qty:§f{$quantity}";
				$data = [
					'type'    => "custom_form",
					'title'   => "Earmazon管理 > 買取アイテム追加",
					'content' => [
						[
							'type' => "label",
							'text' => $content
						],
						[
							'type' => "input",
							'text' => "数量 (この個数分だけ買取します)",
							'placeholder' => "半角数字で入力",
							'default' => $this->amount != 0 ? (string) $this->amount : ""
						],
						[
							'type' => "input",
							'text' => "価格 (1つあたりこの値段で買取します)",
							'placeholder' => "半角数字で入力",
							'default' => $this->price != 0 ? (string) $this->price : ""
						],
						[
							'type' => "label",
							'text' => ""
						],
					]
				];
				$cache = [2+self::NEXT];
			break;
			case 2+self::NEXT:
				$title = "Earmazon管理 > 買取アイテム追加";
				$data = $this->lastData;
				$amount = $data[1] ?? 0;
				$price = $data[2] ?? 0;
				if(!$price || !$amount){
					$this->sendErrorModal($title, "数量と価格を入力してください。", 1);
					return false;
				}

				$this->amount = (int) $amount;
				$this->price = (int) $price;
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$content = "ID:{$this->id} Damage:{$this->meta} 「{$itemName}」 を 1個 {$price}μ で {$amount}個 買取発注をかけます。よろしいですか？";
				$this->sendModal($title, $content, "よろしい",2+self::NEXT*2, "よろしくない",2);
			break;
			case 2+self::NEXT*2:
				$title = "Earmazon管理 > 買取アイテム追加";
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$result = Earmazon::addSellUnit($this->id, $this->meta, $this->amount, $this->price, false);
				$content = $result ? "追加した" : "追加できなかった";
				$this->sendModal($title, $content, "OK",1);

				$msg = Chat::Format("§8Earmazon", "§bお知らせ", "§eEarmazonで§a「{$itemName}」§eの買取が§a{$this->price}μ§eで始まりました！");
				Server::getInstance()->broadcastMessage($msg);

				$this->amount = 0;
				$this->price = 0;
			break;
/*
	販売アイテム
*/
			case 3:
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$quantity = Earmazon::getStorageAmount($this->id, $this->meta);
				if($quantity <= 0){
					$this->sendErrorModal("Earmazon管理 > 販売アイテム追加", "{$itemName}の在庫がありません。\n次官以上の権限者が在庫を追加するか、買取アイテムを追加して集めてください。", 1);
					return false;
				}
				$content = "§e「{$itemName}」 §7ID:§f{$this->id} §7Damage:§f{$this->meta} §7Qty:§f{$quantity}";
				$data = [
					'type'    => "custom_form",
					'title'   => "Earmazon管理 > 販売アイテム追加",
					'content' => [
						[
							'type' => "label",
							'text' => $content
						],
						[
							'type' => "input",
							'text' => "数量 (この個数分だけ販売します)",
							'placeholder' => "半角数字で入力",
							'default' => $this->amount != 0 ? (string) $this->amount : ""
						],
						[
							'type' => "input",
							'text' => "価格 (1つあたりこの値段で販売します)",
							'placeholder' => "半角数字で入力",
							'default' => $this->price != 0 ? (string) $this->price : ""
						],
						[
							'type' => "label",
							'text' => ""
						],
					]
				];
				$cache = [3+self::NEXT];
			break;
			case 3+self::NEXT:
				$title = "Earmazon管理 > 販売アイテム追加";
				$data = $this->lastData;
				$amount = $data[1] ?? 0;
				$price = $data[2] ?? 0;
				if(!$price || !$amount){
					$this->sendErrorModal($title, "数量と価格を入力してください。", 1);
					return false;
				}

				$this->amount = (int) $amount;
				$this->price = (int) $price;
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$content = "ID:{$this->id} Damage:{$this->meta} 「{$itemName}」 を 1個 {$price}μ で {$amount}個 販売します。よろしいですか？";
				$this->sendModal($title, $content, "よろしい",3+self::NEXT*2, "よろしくない",3);
			break;
			case 3+self::NEXT*2:
				$title = "Earmazon管理 > 販売アイテム追加";
				$result = Earmazon::addBuyUnit($this->id, $this->meta, $this->amount, $this->price, false);
				$content = $result ? "追加した" : "追加できなかった";
				$this->sendModal($title, $content, "OK",1);

				$msg = Chat::Format("§8Earmazon", "§bお知らせ", "§eEarmazonで§a「{$itemName}」§eの販売が§a{$this->price}μ§eで始まりました！");
				Server::getInstance()->broadcastMessage($msg);

				$this->amount = 0;
				$this->price = 0;
			break;
/*
	在庫を追加
*/
			case 4:
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$quantity = Earmazon::getStorageAmount($this->id, $this->meta);
				$content = "§e「{$itemName}」 §7ID:§f{$this->id} §7Damage:§f{$this->meta} §7Qty:§f{$quantity}";
				$data = [
					'type'    => "custom_form",
					'title'   => "Earmazon管理 > 在庫を追加",
					'content' => [
						[
							'type' => "label",
							'text' => $content
						],
						[
							'type' => "input",
							'text' => "数量 (この個数分だけ販売します)",
							'placeholder' => "半角数字で入力",
							'default' => $this->amount != 0 ? (string) $this->amount : ""
						],
						[
							'type' => "label",
							'text' => ""
						],
					]
				];
				$cache = [4+self::NEXT];
			break;
			case 4+self::NEXT:
				$title = "Earmazon管理 > 在庫を追加";
				$data = $this->lastData;
				$amount = $data[1] ?? 0;
				if(!$amount){
					$this->sendErrorModal($title, "数量を入力してください。", 1);
					return false;
				}

				$this->amount = (int) $amount;
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$content = "ID:{$this->id} Damage:{$this->meta} 「{$itemName}」 を {$amount}個 追加します。よろしいですか？";
				$this->sendModal($title, $content, "よろしい",4+self::NEXT*2, "よろしくない", 4);
			break;
			case 4+self::NEXT*2:
				$title = "Earmazon管理 > 在庫を追加";
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$result = Earmazon::addIntoStorage($this->id, $this->meta, $this->amount);
				$content = $result ? "追加した" : "追加できなかった";
				$this->amount = 0;
				$this->price = 0;
				$this->sendModal($title, $content, "OK",1);
			break;
/*
	在庫から引き出す
*/
			case 5:
				$title = "Earmazon管理 > 在庫から引き出す";
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$quantity = Earmazon::getStorageAmount($this->id, $this->meta);
				$content = "§e「{$itemName}」 §7ID:§f{$this->id} §7Damage:§f{$this->meta} §7Qty:§f{$quantity}";
				$data = [
					'type'    => "custom_form",
					'title'   => $title,
					'content' => [
						[
							'type' => "label",
							'text' => $content
						],
						[
							'type' => "input",
							'text' => "数量 (この個数分だけ引き出します)",
							'placeholder' => "半角数字で入力",
							'default' => $this->amount != 0 ? (string) $this->amount : ""
						],
						[
							'type' => "label",
							'text' => ""
						],
					]
				];
				$cache = [5+self::NEXT];
			break;
			case 5+self::NEXT*1:
				$title = "Earmazon管理 > 在庫から引き出す";
				$data = $this->lastData;
				$amount = (int) $data[1] ?? 0;
				if(!$amount){
					$this->sendErrorModal($title, "数量を入力してください。", 1);
					return false;
				}
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$e_amount = Earmazon::getStorageAmount($this->id, $this->meta);
				if(Earmazon::getStorageAmount($this->id, $this->meta) < $amount){
					$this->sendErrorModal($title, "在庫不足。\n「{$itemName}」の在庫は{$e_amount}個しかありません。", 5);
					return false;
				}
				$this->amount = (int) $amount;
				$content = "ID:{$this->id} Damage:{$this->meta} 「{$itemName}」 を {$amount}個 引き出します。よろしいですか？";
				$this->sendModal($title, $content, "よろしい",5+self::NEXT*2, "よろしくない", 5);
			break;
			case 5+self::NEXT*2:
				$title = "Earmazon管理 > 在庫から引き出す";
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$e_amount = Earmazon::getStorageAmount($this->id, $this->meta);
				if(Earmazon::getStorageAmount($this->id, $this->meta) < $this->amount){ //途中で誰かが引き出す可能性があるため
					$this->sendErrorModal($title, "在庫不足。\n「{$itemName}」の在庫は{$e_amount}しかありません。\n途中で誰かが引き出した、または購入したようです。", 5);
					return false;
				}
				$inv = $playerData->getItemBox();
				$item = Item::get($this->id, $this->meta, $this->amount);
				if(!$inv->canAddItem($item)){
					$this->sendErrorModal($title, "アイテムボックスがいっぱいなようです。", 5);
					return false;
				}
				// ストレージから減らす
				if(!Earmazon::removeFromStorage($this->id, $this->meta, $this->amount)){
					$this->sendErrorModal($title, "§c出るべきでないエラー(報告してください)。§7ストレージの在庫を減らす処理に失敗しました。", 5);
					Earmazon::addIntoBuyUnit($unitno, $amount); // 販売リストの点数戻す
					return false;
				}
				$inv->addItem($item);
				$this->sendModal($title, "アイテムの引き出しが完了しました。\n引き続き惑星Eardの開拓にご協力よろしくお願いします。", "わかりました", 1);
			break;
/*
	アイテム検索
*/
			case self::SEARCH:
				$data = [
					'type'    => "form",
					'title'   => "Earmazon管理 > アイテム選択",
					'content' => "",
					'buttons' => [
						['text' => "IDから探す"],
						['text' => "カテゴリから探す"],
					]
				];
				$cache = [self::SEARCH+1, self::SEARCH+2];
			break;
			case self::SEARCH+1:
				// さがすやつ
				$data = [
					'type'    => "custom_form",
					'title'   => "Earmazon管理 > アイテム選択 > IDから探す",
					'content' => [
						[
							'type' => "label",
							'text' => "idを打ち込んでください。突貫工事なのでアイテムidからしか選択できません。idは頑張って調べて！"
						],
						[
							'type' => "input",
							'text' => "ID",
							'placeholder' => "半角数字で入力",
							'default' => $this->id != 0 ? (string) $this->id : ""
						],
						[
							'type' => "input",
							'text' => "Damage",
							'placeholder' => "半角数字で入力",
							'default' => $this->meta != 0 ? (string) $this->meta : ""
						],
						[
							'type' => "label",
							'text' => ""
						],
					]
				];
				$cache = [self::SEARCH+1+self::NEXT];
			break;
			case self::SEARCH+1+self::NEXT:
				// 探したアイテム判定
				$title = "Earmazon管理 > アイテム選択 > ID指定";
				$data = $this->lastData;
				$id = $data[1] ?? 0;
				$damage = $data[2] ?? 0;
				if(!$id){
					$this->sendErrorModal($title, "IDを入力してください", 1);
				}else{
					$itemName = ItemName::getNameOf((int) $id,(int) $damage);
					$content = "§fアイテム「{$itemName}」を選択しました。";

					$this->id = (int) $id;
					$this->meta = (int) $damage;
					$this->sendModal($title, $content, "§bOK、わかった",1, "§cいや、指定しなおそう",self::SEARCH);
				}
			break;

			case self::SEARCH+2:
				// かてごりからさがすやつ
				$data = [
					'type'    => "form",
					'title'   => "Earmazon管理 > アイテム選択 > カテゴリから探す",
					'content' => "",
					'buttons' => [
						['text' => "一般ブロック"],
						['text' => "装飾用ブロック"],
						['text' => "鉱石系"],
						['text' => "設置ブロック"],
						['text' => "草花"],
						['text' => "RS系統"],
						['text' => "素材"],
						['text' => "ツール"],
						['text' => "食べ物"],
						['text' => "戻る"]
					]
				];
				$cache = [self::SEARCH+2+self::NEXT*2];
			break;
			case self::SEARCH+2+self::NEXT*2:
				if($this->lastData === 9){ // 「もどる」
					$this->send(self::SEARCH);
					return false;
				}

				$categoryNo = $this->lastData + 1;
				$this->categoryNo = $categoryNo;

				$listofitem = ItemName::getListByCategory($categoryNo);
				$ar = ["一般ブロック","装飾用ブロック","鉱石系","設置ブロック","草花","RS系統","素材","ツール","食べ物"];
				$categoryText = $ar[$this->lastData];

				// ぼたんつくる
				$buttons = [];
				$page = self::SEARCH+2+self::NEXT*3;
				foreach($listofitem as $d){
					$name = (isset($d[0]) && isset($d[1])) ? "§l".ItemName::getNameOf($d[0],$d[1])."§r ( {$d[0]}:{$d[1]} )" : "";
					$buttons[] = ['text' => $name];
					$cache[] = $page;
				}
				$buttons[] = ['text' => "戻る"];
				$cache[] = self::SEARCH+2;

				$data = [
					'type'    => "form",
					'title'   => "アイテム選択 > カテゴリから探す > {$categoryText}",
					'content' => "",
					'buttons' => $buttons
				];
			break;
			case self::SEARCH+2+self::NEXT*3:
				$listofitem = ItemName::getListByCategory($this->categoryNo);
				$this->id = $listofitem[$this->lastData][0];
				$this->meta = $listofitem[$this->lastData][1];

				$title = "アイテム選択 > カテゴリから探す";
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$content = "§fアイテム「{$itemName}」を選択しました。";
				$this->sendModal($title, $content, "§bOK、わかった",1, "§cいや、指定しなおそう",self::SEARCH);

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

	public $id, $meta = 0;
	public $amount, $price = 0;

	private $categoryNo = 0;
}