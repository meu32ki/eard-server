<?php
namespace Eard\Form;

# basic
use pocketmine\Server;
use pocketmine\scheduler\Task;
use pocketmine\math\Vector3;

# Eard
use Eard\DBCommunication\Connection;
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Government;
use Eard\MeuHandler\Account\License\License;
use Eard\MeuHandler\Account\License\Costable;
use Eard\Event\AreaProtector;
use Eard\Enemys\AI;
use Eard\Utils\Chat;
use Eard\Utils\ChestIO;
use Eard\Utils\ItemName;

/*
 * DB操作とかは Eard/DBCommunication/Freemarket へ
 */
class FreemarketForm extends FormBase {

	const NEXT = 8; //大きく分けたときのフォームの種類数
	const TOP = 1;
	const SEARCH = 2;
	const REQUEST = 3;
	const SEARCH_BUY = 4;
	const SEARCH_SELL = 5;
	const REQUEST_BUY = 6;
	const REQUEST_SELL = 7;

	public $onlinelist = []; // 一時的な保存用
	public $name = [];
	public $data = null;
	public $chest = null;
	public $price = null;


	public function send(int $id){
		$playerData = $this->playerData;
		$cache = [];
		switch($id){
			//GPS => 目的地設定
			case self::TOP:
				$this->data = null;
				$title = "フリマ";
				$buttons[] = ['text' => "出品物一覧を見る\n即時取引できます"];
				$cache[] = self::SEARCH;
				$buttons[] = ['text' => "出品する\n取引に時間がかかります"];
				$cache[] = self::REQUEST;

				$data = [
					'type'    => "form",
					'title'   => $title,
					'content' => "お探しのものを選んでください。",
					'buttons' => $buttons
				];
			break;

/**
 * 他人の出品か依頼を探す
 */
			case self::SEARCH:
				$this->sendErrorModal("フリマ > 出品物一覧", "まだ書いてない", self::TOP);
			break;
/**
 * 自分で出品するか依頼を出す
 */
			case self::REQUEST:
				$title = "フリマ > 出品する";
				$buttons[] = ['text' => "お金\nお金を出し、アイテムを得る"];
				$cache[] = self::REQUEST_BUY;
				$buttons[] = ['text' => "アイテム\nアイテムを出し、お金を得る"];
				$cache[] = self::REQUEST_SELL;

				$data = [
					'type'    => "form",
					'title'   => $title,
					'content' => "出品するものを選んでください。",
					'buttons' => $buttons
				];
			break;

			case self::REQUEST_BUY:
				$title = "フリマ > 出品する > お金";
				$this->sendErrorModal($title, "まだ書いてない", self::TOP);
			break;

			case self::REQUEST_SELL:
				$this->chest = null;
				$title = "フリマ > 出品する > アイテム";
				$buttons[] = ['text' => "アイテムを選択する"];
				$cache[] = self::REQUEST_SELL+self::NEXT*1;
				$buttons[] = ['text' => "戻る"];
				$cache[] = self::TOP;

				$data = [
					'type'    => "form",
					'title'   => $title,
					'content' => "持ち物から出品するアイテムを選んでください。",
					'buttons' => $buttons
				];
			break;
			case self::REQUEST_SELL+self::NEXT*1:
				$title = "フリマ > 出品する > アイテム > アイテム選択";
				$buttons[] = ['text' => "アイテムを選択し直す"];
				$cache[] = self::REQUEST_SELL+self::NEXT*1;
				$buttons[] = ['text' => "次へ"];
				$cache[] = self::REQUEST_SELL+self::NEXT*2;
				$buttons[] = ['text' => "戻る (アイテムを選択している場合は必ず取り出してください) "];
				$cache[] = self::REQUEST_SELL;
				$data = [
					'type'    => "form",
					'title'   => $title,
					'content' => "アイテムを選択したら[次へ]を選択してください。",
					'buttons' => $buttons
				];
				$this->lastSendData = $data;
				$this->cache = $cache;
				$this->show($id, $data);
				$player = $playerData->getPlayer();
				$this->chest = $this->chest ?? new ChestIO($player);
				$this->chest->setName("出品するアイテムを中に入れて閉じてください");
				$player->addWindow($this->chest);
				return true;
			break;
			case self::REQUEST_SELL+self::NEXT*2:
				$title = "フリマ > 出品する > アイテム > 値段設定";
				if(empty($this->chest->getContents())){
					$this->sendErrorModal($title, "アイテムが選択されていません。", self::REQUEST_SELL+self::NEXT*1);
					return false;
				}
				$item = $this->chest->getItem(0);//あとでぶん回す
				$itemname = ItemName::getNameOf($item->getId(), $item->getDamage());
				$this->item = $item;
				$title = "フリマ > 出品する > アイテム > アイテム選択";
				$data = [
					'type'    => "custom_form",
					'title'   => $title,
					'content' => [
						[
							'type' => "label",
							'text' => "選択中のアイテム : {$itemname} ×{$item->getCount()}"
						],
						[
							'type' => "input",
							'text' => "値段を入力",
							'placeholder' => "半角数字で入力",
							'default' => $this->price != 0 ? (string) $this->price : ""
						],
						[
							'type' => "toggle",
							'text' => "アイテムを選択し直す",
							'default' => false
						]
					]
				];
				$cache[] = self::REQUEST_SELL+self::NEXT*3;
			break;
			case self::REQUEST_SELL+self::NEXT*3:
				//todo 選択し直すの確認
				$this->price = $this->lastData[1] ?? 1;
				$itemname = ItemName::getNameOf($this->item->getId(), $this->item->getDamage());
				$title = "フリマ > 出品する > アイテム >　確認";
				$buttons[] = ['text' => "OK"];
				$cache[] = self::REQUEST_SELL+self::NEXT*4;
				$buttons[] = ['text' => "戻る"];
				$cache[] = self::REQUEST_SELL+self::NEXT*2;
				$data = [
					'type'    => "form",
					'title'   => $title,
					'content' => "{$itemname} ×{$this->item->getCount()}個を {$this->price}μで出品します。\nよろしいですか？",
					'buttons' => $buttons
				];
			break;
			case self::REQUEST_SELL+self::NEXT*4:
				$title = "フリマ > 出品する > アイテム > 確認";
				$this->sendModal($title, "出品が完了しました。", "OK", 1);
				return false;
			break;
		}
		
		// みせる
		if($cache){
			// sendErrorMoralのときとかは動かないように
			$this->lastSendData = $data;
			$this->cache = $cache;
			$this->show($id, $data);
		}
	}
}