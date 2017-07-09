<?php
namespace Eard\BlockObject;


# Basic
use pocketmine\Player;
use pocketmine\item\Item;

# Eard
use Eard\Account;
use Eard\Chat;
use Eard\Utils\ItemName;


class ItemExchanger implements BlockObject, ChatInput {


/********************
	BlockObject
********************/

	public $x, $y, $z;
	public $objNo;
	public static $kind = 1;

	public function Place(Player $player){
		$player->sendMessage(Chat::Format("アイテム交換", "タップして起動"));
		return false;
	}

	public function Tap(Player $player){
		$this->MenuTap($player);
		return true; //キャンセルしないと、手持ちがブロックだった時に置いてしまう
	}

	public function StartBreak(Player $player){
		$this->MenuLongTap($player);
		return false;
	}

	public function Break(Player $player){
		return false;
	}

	public function Delete(){
		$this->backItemAll();
		$this->removeTextParticleAll();
	}

	public function getData(){
		return [];
	}
	public function setData($data){
		return true;
	}

	public function getObjNo(){
		return $this->objNo;
	}


	public function Chat(Player $player, String $txt){

	}

/********************
	BlockMenu
********************/

	use BlockMenu;

	public function getPageAr($no, $player){
		switch($no){
		case 1:
			$ar = [
				["アイテム交換", false],
				["タップして アイテム/μ をセット", false],
				["長押しで次へ", 2],
			];
		break;
		case 2: // あいてむorみゅー せんたく
			$ar = [
				["アイテム交換", false],
				["ミューをセット", 3],
				["アイテムをセット", 15],
				["戻る", 1]
			];
		break;
		case 3: //みゅーとっぷ
			$playerData = Account::get($player);
			$meu = $playerData->getMeu();
			$meuAmount = $meu->getAmount();
			$itemText = $this->getAsText($player);
			$ar = [
				["アイテム交換", false],
				["所持μ : {$meuAmount}μ", false],
				["セットしたアイテム :\n{$itemText}", false],
			];
			if($meuAmount){
				$ar[] = ["数値を打ち込む", false];
				$ar[] = ["有り金全部", 5];
				if(10000 < $meuAmount) $ar[] = ["10000μ", 6];
				if(5000 < $meuAmount) $ar[] = ["5000μ", 7];
				if(1000 < $meuAmount) $ar[] = ["1000μ", 8];
				if(500 < $meuAmount) $ar[] = ["500μ", 9];
				if(100 < $meuAmount) $ar[] = ["100μ", 10];
				if(100 < $meuAmount) $ar[] = ["50μ", 11];
				if(100 < $meuAmount) $ar[] = ["10μ", 12];
			}else{
				$ar[] = ["お金がありません", false];
			}
			$ar[] = ["戻る", 2];
		break;
		case 5: $meu = $playerData->getMeu(); break;
		case 6: $meu = $playerData->getMeu()->spilit(10000); break;
		case 7: $meu = $playerData->getMeu()->spilit(5000); break;
		case 8: $meu = $playerData->getMeu()->spilit(1000); break;
		case 9: $meu = $playerData->getMeu()->spilit(500); break;
		case 10: $meu = $playerData->getMeu()->spilit(100); break;
		case 11: $meu = $playerData->getMeu()->spilit(50); break;
		case 12: $meu = $playerData->getMeu()->spilit(10); break;
		case 5: case 6: case 7: case 8: case 9: case 10: case 11: case 12:
			$playerData = Account::get($player);
			$playerMeuAmount = $playerData->getMeu()->getAmount();
			$result = $this->set($playerData, $meu) ? "セットしました" : "なんかエラー";
			$itemText = $this->getAsText($player);
			$ar = [
				["アイテム交換", false],
				//[$result, false],
				["所持μ : {$playerMeuAmount}μ", false],
				["セットしたアイテム :\n{$itemText}", false],
				["μを追加で入れる", 3],
				["セット完了する", 17]
			];
		break;
		case 15: // あいてむとっぷ
			$ar = [
				["アイテム交換", false],
				["セットしたいアイテムを手に",false],
				["持って、「セット」を押してネ",false],
				["セット",16],
				["戻る", 2]
			];
		break;
		case 16: //手元のアイテムを入れる、いれおわり
			$playerData = Account::get($player);
			$result = $this->set($playerData, $player->getInventory()->getItemInHand()) ? "セットしました" : "なんかエラー";
			$player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 1));
			$itemText = $this->getAsText($player);
			$ar = [
				["アイテム交換", false],
				["セットしたアイテム :\n{$itemText}", false],
				["アイテムを追加で入れる", 15],
				["セット完了する", 17]
			];
		break;
		case 17: // アイテム入れ終わった待機画面
			$name = $player->getName();
			$this->readyplayers[] = $name;
			/*
			$this->readyplayers = [
				"meu32ki" => 0,
				"famima65536" => 1
			];
			*/
			//2人も待機状態になったらスタート
			if(count($this->readyplayers) == 2){
				$name1 = $this->readyplayers[0];
				$name2 = $this->readyplayers[1];
				$player1 = $this->transaction[$name1][0]->getPlayer();
				$player2 = $this->transaction[$name2][0]->getPlayer();
				$this->sendPageData(18, $player1);
				$this->sendPageData(18, $player2);

				$item1 = $this->getAsText($player1, "");
				$item2 = $this->getAsText($player2, "");
				$player1->sendMessage(Chat::Format("システム", $player2->getName()."さんの[".$item2."]と、あなたの[".$item1."]を交換しますか？"));
				$player2->sendMessage(Chat::Format("システム", $player1->getName()."さんの[".$item1."]と、あなたの[".$item2."]を交換しますか？"));
				return [];//間違ったものが送られないように。sendPageDataしたほうのデータが反映されるように。。。
			}
			$itemText = $this->getAsText($player);
			$ar = [
				["アイテム交換", false],
				["交換相手を待機中です……", false],
				["セットしたアイテム : \n{$itemText}", false],
				["取引をやめる", 18]
			];
		break;
		case 18: //取引確認画面
			$ar = [
				["アイテム交換", false],
				["交換しますか？", false],
				["いいえ", 20],
				["はい", 19]
			];
		break;
		case 19: //取引実行
			$name = $player->getName();
			$this->transaction[$name][2] = true;

			$name1 = $this->readyplayers[0];
			$name2 = $this->readyplayers[1];
			if( $this->transaction[$name1][2] && $this->transaction[$name2][2] ){
				//実行
				$player1 = $this->transaction[$name1][0]->getPlayer();
				$player2 = $this->transaction[$name2][0]->getPlayer();

				$playerData2 = Account::get($player2);
				foreach($this->transaction[$name1][1] as $item){
					if($item instanceof Item){
						$playerData2->getPlayer()->getInventory()->addItem($item);
					}elseif($item instanceof Meu){
						$playerData2->getMeu()->merge($item);
					}
				}

				$playerData1 = Account::get($player1);
				foreach($this->transaction[$name2][1] as $item){
					if($item instanceof Item){
						$playerData2->getPlayer()->getInventory()->addItem($item);
					}elseif($item instanceof Meu){
						$playerData2->getMeu()->merge($item);
					}
				}

				$player1->sendMessage(Chat::Format("システム", "交換しました"));
				$player2->sendMessage(Chat::Format("システム", "交換しました"));

				//さいごのでーた
				$this->sendPageData(1, $player1);
				$this->sendPageData(1, $player2);

				//終了
				$this->readyplayers = [];
				unset($this->transaction[$name1]);
				unset($this->transaction[$name2]);
			}
			$ar = [
				["アイテム交換", false],
				["交換相手を待機中です……", false],
			];
		break;
		case 20: //　あいてむもどす とりひき きゃんせる
			$itemText = $this->getAsText($player);
			$this->backItem(Account::get($player));
			$ar = [
				["アイテム交換", false],
				["中止しました。{$itemText}を", false],
				["あなたの手元に戻しました", false],
				["トップへ戻る", 1]
			];
		break;

/*		case 21:　//交換後のページ
			$ar = [
				["アイテム交換", false],
				["完了しました", false],
				["トップへ戻る", 1]
			];
		break;
*/
		default:
			$ar = [
				["アイテム交換", false],
				["ページがありません",1],
			];
		break;
		}
		return $ar;
	}


/*************************
	ItemExchanger
*************************/

	private $readyplayers = [];
	private $transaction = [];

	/**
	*	playerが持っているitem,Meuを、こちら側のコンテナにセットする
	*	@param $item = Item, Meu
	*	@return bool
	*/
	public function set($playerData, $item){
		$name = $playerData->getPlayer()->getName();
		if(!isset($this->transaction[$name])){
			$this->transaction[$name][0] = $playerData;
			$this->transaction[$name][1][] = $item;
			$this->transaction[$name][2] = false; //アイテム交換していいかの最終確認用
		}else{
			$key = count( $this->transaction[$name][1] ) - 1;
			if($this->transaction[$name][1][$key] instanceof Meu && $item instanceof Meu){
				$this->transaction[$name][1][$key]->merge($item);
			}else{
				if($item instanceof Item && $item->getId() != 0){//airがはいるかもしれない
					$this->transaction[$name][1][] = $item;
				}
			}
		}
		return true;
	}

	public function getAsText($player, $separator = "\n"){
		$name = $player->getName();
		$out = "";
		if(isset($this->transaction[$name][1])){
			foreach($this->transaction[$name][1] as $i){
				$itemName = ItemName::getNameOf($i);
				$out .= " {$itemName} x {$i->getCount()}{$separator}";
			}
			$out = substr($out, 0, -1);
			return $out;
		}else{
			return "  なし";
		}
	}

	public function backItemAll(){
		foreach($this->transaction as $name => $d){
			$playerData = $d[0];
			foreach($d[1] as $i){
				if($d[1] instanceof Item){
					$playerData->getPlayer()->getInventory()->addItem($i);
				}elseif($d[1] instanceof Meu){
					$playerData->getMeu()->merge($i);
				}
			}
		}
		$this->transaction = [];
	}

	public function backItem($playerData){
		if(isset($this->transaction[$name])){
			$name = $playerData->getPlayer()->getName();
			foreach($this->transaction[$name][1] as $item){
				if($item instanceof Item){
					$playerData->getPlayer()->getInventory()->addItem($item);
				}elseif($item instanceof Meu){
					$playerData->getMeu()->merge($item);
				}
			}
			unset($this->transaction[$name]);
		}
	}

}