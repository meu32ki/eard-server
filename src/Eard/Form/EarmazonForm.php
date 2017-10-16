<?php
namespace Eard\Form;


# basic
use pocketmine\Server;

# Eard
use Eard\DBCommunication\Earmazon;
use Eard\Utils\ItemName;
use Eard\MeuHandler\Account\License\License;


class EarmazonForm extends FormBase {

	const NEXT = 5;
	const SEARCH = 101;

	/*
		アイテムを選択して、そのアイテムをどうするかを決めてって感じ。
	*/

	public function send(int $id){
		$playerData = $this->playerData;
		$cache = [];
		switch($id){
			case 1:
				// メニュー
				$buttons = [];

				$buttons[] = ['text' => "アイテム選択"];
				$cache[] = self::SEARCH;
				$gwrank = $playerData->getLicense(License::GOVERNMENT_WORKER)->getRank();
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
					$content = "§7ID:§f{$this->id} §7Damage:§f{$this->meta} §e「{$itemName}」";
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
				$content = "§7ID:§f{$this->id} §7Damage:§f{$this->meta} §e「{$itemName}」";
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
							'default' => (string) $this->amount != 0 ? $this->amount : ""
						],
						[
							'type' => "input",
							'text' => "価格 (1つあたりこの値段で買取します)",
							'placeholder' => "半角数字で入力",
							'default' => (string) $this->price != 0 ? $this->price : ""
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
					$this->sendErrorModal($title, "数量と価格を入力してください。", 2);
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
				$result = Earmazon::addSellUnit($this->id, $this->meta, $this->amount, $this->price, false);
				$content = $result ? "追加した" : "追加できなかった";
				$this->amount = 0;
				$this->meta = 0;
				$this->sendModal($title, $content, "OK",1);
			break;
/*
	販売アイテム
*/
			case 3:
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$content = "§7ID:§f{$this->id} §7Damage:§f{$this->meta} §e「{$itemName}」";
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
							'default' => (string) $this->amount != 0 ? $this->amount : ""
						],
						[
							'type' => "input",
							'text' => "価格 (1つあたりこの値段で販売します)",
							'placeholder' => "半角数字で入力",
							'default' => (string) $this->price != 0 ? $this->price : ""
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
					$this->sendErrorModal($title, "数量と価格を入力してください。", 3);
					return false;
				}

				$this->amount = (int) $amount;
				$this->price = (int) $price;
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$content = "ID:{$this->id} Damage:{$this->meta} 「{$itemName}」 を 1個 {$price}μ で {$amount}個 販売します。よろしいですか？";
				$this->sendModal($title, $content, "よろしい",3+self::NEXT, "よろしくない",3);
			break;
			case 3+self::NEXT*2:
				$title = "Earmazon管理 > 販売アイテム追加";
				$result = Earmazon::addBuyUnit($this->id, $this->meta, $this->amount, $this->price, false);
				$content = $result ? "追加した" : "追加できなかった";
				$this->amount = 0;
				$this->meta = 0;
				$this->sendModal($title, $content, "OK",1);
			break;
/*
	在庫を追加
*/
			case 4:
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$content = "§7ID:§f{$this->id} §7Damage:§f{$this->meta} §e「{$itemName}」";
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
							'default' => (string) $this->amount != 0 ? $this->amount : ""
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
					$this->sendErrorModal($title, "数量を入力してください。", 4);
					return false;
				}

				$this->amount = (int) $amount;
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$content = "ID:{$this->id} Damage:{$this->meta} 「{$itemName}」 を {$amount}個 追加します。よろしいですか？";
				$this->sendModal($title, $content, "よろしい",4+self::NEXT*2, "よろしくない", 4);
			break;
			case 4+self::NEXT*2:
				$title = "Earmazon管理 > 在庫を追加";
				$result = Earmazon::addIntoStorage($this->id, $this->meta, $this->amount);
				$content = $result ? "追加した" : "追加できなかった";
				$this->amount = 0;
				$this->meta = 0;
				$this->sendModal($title, $content, "OK",1);
			break;
/*
	在庫から引き出す
*/
			case 5:
				$title = "Earmazon管理 > 在庫から引き出す";
				$this->sendErrorModal($title, "内容がないよう。", 1);
			break;
			case 5+self::NEXT*1:
				$title = "Earmazon管理 > 在庫から引き出す";
				$this->sendErrorModal($title, "内容がないよう。", 1);
			break;
			case 5+self::NEXT*2:
				$title = "Earmazon管理 > 在庫から引き出す";
				$this->sendErrorModal($title, "内容がないよう。", 1);
			break;
/*
	アイテム検索
*/
			case self::SEARCH:
				// さがすやつ
				$data = [
					'type'    => "custom_form",
					'title'   => "Earmazon管理 > アイテム選択",
					'content' => [
						[
							'type' => "label",
							'text' => "idを打ち込んでください。突貫工事なのでアイテムidからしか選択できません。idは頑張って調べて！"
						],
						[
							'type' => "input",
							'text' => "ID",
							'placeholder' => "半角数字で入力",
							'default' => (string) $this->id != 0 ? $this->id : ""
						],
						[
							'type' => "input",
							'text' => "DAMAGE",
							'placeholder' => "半角数字で入力",
							'default' => (string) $this->meta != 0 ? $this->meta : ""
						],
						[
							'type' => "label",
							'text' => ""
						],
					]
				];
				$cache = [self::SEARCH+self::NEXT];
			break;
			case self::SEARCH+self::NEXT:
				// 探したアイテム判定
				$title = "Earmazon管理 > アイテム選択";
				$data = $this->lastData;
				$id = $data[1] ?? 0;
				$damage = $data[2] ?? 0;
				if(!$id){
					$this->sendErrorModal($title, "IDを入力してください", self::SEARCH);
				}else{
					$itemName = ItemName::getNameOf((int) $id,(int) $damage);
					$content = "§fアイテム「{$itemName}」を選択しました。";

					$this->id = (int) $id;
					$this->meta = (int) $damage;
					$this->sendModal($title, $content, "OK、わかった",1, "いや、指定しなおそう",self::SEARCH);
				}
			break;
			case self::SEARCH+self::NEXT*2:
				// 決定
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
}