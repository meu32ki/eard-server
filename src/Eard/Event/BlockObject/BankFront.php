<?php
namespace Eard\Event\BlockObject;


# Basic
use pocketmine\Player;
use pocketmine\item\Item;

# Eard
use Eard\MeuHandler\Account;
use Eard\Utils\ItemName;
use Eard\Form\BankForm;
use Eard\Utils\Chat;

class BankFront implements BlockObject {


/********************
	BlockObject
********************/

	public $x, $y, $z;
	public $indexNo;
	public static $objNo = 1;

	public function Place(Player $player){
		$player->sendMessage(Chat::Format("銀行", "タップして起動"));
		return false;
	}

	public function Tap(Player $player){
    new BankForm(Account::get($player));
		return true; //キャンセルしないと、手持ちがブロックだった時に置いてしまう
	}

	public function StartBreak(Player $player){
		return false;
	}

	public function Break(Player $player){
		return false;
	}

	public function Delete(){
		//$this->backItemAll(); ItemExchangerではないため不要 by moyasan
		//$this->removeTextParticleAll(); TextParticleではなくForm形式のため不要 by moyasan
	}

	public function getData(){
		return [];
	}
	public function setData($data){
		return true;
	}

	public function getObjIndexNo(){
		return $this->indexNo;
	}
}
