<?php
namespace Eard\MeuHandler\Account\License;


class Residence extends License {

	/*
		1 => 低レベル
		2 => ふつう。
		3 => 家があって稼ぎもそこそこ？
	*/

	public function getLicenseNo(){
		return self::RESIDENCE;
	}

	// @License
	public function canUpgrade(){
		if(1 <= $this->rank and $this->rank < 5) return true;
		return false;
	}

	// @License
	public function upgrade(){
		if($this->canUpgrade()){
			$this->rank += 1;
		}
	}

	// @License
	public function canDowngrade(){
		if(1 < $this->rank and $this->rank <= 5) return true;
		return false;
	}

	// @License
	public function downgrade(){
		if($this->canDowngrade()){
			$this->rank -= 1;
		}
	}

	public function getPrice(){
		switch($this->getCost()){
			case 1: $price = 0; break;
			case 2: $price = 1000; break;
			case 3: $price = 4000; break;
			case 4: $price = 9000; break;
			case 5: $price = 16000; break;
		}
	}

	public function getUpdatePrice(){
		switch($this->getCost()){
			case 1: $price = 0; break;
			case 2: $price = 500; break;
			case 3: $price = 2000; break;
			case 4: $price = 4500; break;
			case 5: $price = 8000; break;
		}
	}


	public $ranktxt = [
		1 => "浮浪者",
		2 => "一般",
		3 => "中流",
		4 => "上流",
		5 => "富裕"
	];
	public $name = "生活レベル";

}