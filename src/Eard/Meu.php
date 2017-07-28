<?php
namespace Eard;


/****
*
*	通貨に関する記述
*/
class Meu {

	//いまんとこPlayerDataを入れておくメリットをあまり感じない 20170616
	//PlayerDataをUniqueNoにした。会社からの送金があるかもしれない。20170702

	/**
	*	@param int $amount | そのmeuの量
	*	@param int $uniqueNo | そのmeuを持つ持ち主のUniqueNo
	*	@return class Meu
	*/
	public static function get($amount, $uniqueNo){
		$meu = new Meu();
		$meu->amount = $amount;
		$meu->uniqueNo = $uniqueNo;
		return $meu;
	}

	//getにしておく必要性もあまり感じないが
	public function getUniqueNo(){
		return $this->uniqueNo;
	}

	public function getName(){
		return "{$this->amount}μ";
	}


	/**
	*	!注意! governmentから以外では使うな。
	*	お金量をコントロールできるのは政府だけだから。
	*	基本的には、meuの計算合算は外部でおこなうべき(?) 20170701
	*	@param int $amount
	*	@return bool
	*/
	/* duplicated
	public function setAmount($amount){
		$this->amount = $amount;
		return true;
	}*/

	/**
	*	@return int
	*/
	public function getAmount(){
		return $this->amount;
	}


	/**
	*	amount以上あるか確認する
	*	@param int $amount
	*	@return bool
	*/
	public function sufficient($amount){
		return $amount <= $this->amount;
	}

	/**
	*	分割する。playerの全額面のなかから、一部だけを切り取りたい時に。
	*	@param int | 取り出したいmeu
	*	@return Meu　or null
	*/
	public function spilit($spilitAmount){
		if($spilitAmount <= $this->amount){
			//残りが0以下にならないように
			$this->amount = $this->amount - $spilitAmount;
			return self::get($spilitAmount, $this->uniqueNo);
		}else{
			//残りが0以下になっちゃう
			return null;
		}
	}

	/**
	*	合算する。
	*	@param Meu | 吸収するMeu
	*	@return bool
	*/
	public function merge(Meu $meu){
		$this->amount = $this->amount + $meu->getAmount();
		return true;
	}

	private $amount, $uniqueNo;

}