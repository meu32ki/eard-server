<?php
namespace Eard;


/****
*
*	通貨に関する記述
*/
class Meu {

	/**
	*	ブロックが置かれた時
	*	@param Int $amount
	*	@param Account $playerData
	*	@return void
	*/
	public static function get($amount, $playerData){
		$meu = new Meu();
		$meu->amount = $amount;
		$meu->playerData = $playerData;
		return $meu;
	}

	public function getName(){
		return "{$this->amount}μ";
	}

	public function getAmount(){
		return $this->amount;
	}

	/**
	*	分割する。playerの全額面のなかから、一部だけを切り取りたい時に。
	*	@param Int
	*	@return Meu
	*/
	public function spilit($spilitAmount){
		$this->amount = $this->amount - $spilitAmount;
		return new Meu($spilitAmount, $this->playerData);
	}

	public function marge($meu){
		$this->amount = $this->amount + $meu->getAmount();
		return true;
	}

	private $amount, $playerData;

}