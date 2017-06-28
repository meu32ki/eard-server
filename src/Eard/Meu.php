<?php
namespace Eard;


/****
*
*	通貨に関する記述
*/
class Meu {

	//いまんとこplayerdataを入れておくメリットをあまり感じない 20170616

	/**
	*	@param Int $amount
	*	@param Account $playerData
	*	@return class Meu
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

	/*
	*	amount以上あるか
	*/
	public function sufficient($amount){
		return $amount <= $this->amount;
	}

	private $amount, $playerData;

}