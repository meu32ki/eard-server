<?php
namespace Eard\Form;


# basic
use pocketmine\Server;

# Eard
use Eard\DBCommunication\Earmazon;
use Eard\Utils\ItemName;


class EarmazonForm extends FormBase {

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
				$cache[] = 101;

				if($this->id != 0){
					$buttons[] = ['text' => "買取アイテム追加\nプレイヤーがμを得る"];
					$cache[] = 2;

					$buttons[] = ['text' => "販売アイテムを出す\nプレイヤーがアイテムを得る"];
					$cache[] = 5;

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
				$cache = [3];
			break;
			case 3:
				$title = "Earmazon管理 > 買取アイテム追加";
				$data = $this->lastData;
				$amount = $data[1] ?? 0;
				$price = $data[2] ?? 0;
				if(!$price || !$amount){
					$this->sendErrorModal($title, "数量と価格を入力してください。", 2);
					return false;
				}

				$this->amount = $amount;
				$this->price = $price;

				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$content = "ID:{$this->id} Damage:{$this->meta} 「{$itemName}」 を 1個 {$price}μ で {$amount}個 買取発注をかけます。よろしいですか？";
				$this->sendModal($title, $content, "よろしい",4, "よろしくない",2);
			break;
			case 4:
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
			case 5:
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
				$cache = [6];
			break;
			case 6:
				$title = "Earmazon管理 > 販売アイテム追加";
				$data = $this->lastData;
				$amount = $data[1] ?? 0;
				$price = $data[2] ?? 0;
				if(!$price || !$amount){
					$this->sendErrorModal($title, "数量と価格を入力してください。", 5);
					return false;
				}

				$this->amount = $amount;
				$this->price = $price;
				$itemName = ItemName::getNameOf($this->id, $this->meta);
				$content = "ID:{$this->id} Damage:{$this->meta} 「{$itemName}」 を 1個 {$price}μ で {$amount}個 販売します。よろしいですか？";
				$this->sendModal($title, $content, "よろしい",7, "よろしくない",5);
			break;
			case 7:
				$title = "Earmazon管理 > 販売アイテム追加";
				$result = Earmazon::addBuyUnit($this->id, $this->meta, $this->amount, $this->price, false);
				$content = $result ? "追加した" : "追加できなかった";
				$this->amount = 0;
				$this->meta = 0;
				$this->sendModal($title, $content, "OK",1);
			break;
/*
	アイテム検索
*/
			case 101:
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
				$cache = [102];
			break;
			case 102:
				// 探したアイテム判定
				$title = "Earmazon管理 > アイテム選択";
				$data = $this->lastData;
				$id = $data[1] ?? 0;
				$damage = $data[2] ?? 0;
				if(!$id){
					$this->sendErrorModal($title, "IDを入力してください", 101);
				}else{
					$itemName = ItemName::getNameOf($id,$damage);
					$content = "§fアイテム「{$itemName}」を選択しました。";

					$this->id = $id;
					$this->meta = $damage;
					$this->sendModal($title, $content, "OK、わかった",1, "いや、指定しなおそう",101);
				}
			break;
			case 103:
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