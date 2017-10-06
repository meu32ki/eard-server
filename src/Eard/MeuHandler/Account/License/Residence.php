<?php
namespace Eard\MeuHandler\Account\License;


class Residence extends License {

	public function getLicenseNo(){
		return self::RESIDENCE;
	}

	// @License
	public function canUpgrade(){
		return $this->isRankExist($this->rank + 1);
	}

	// @License
	public function upgrade(){
		if($this->canUpgrade()){
			$this->rank += 1;
			return true;
		}
		return false;
	}

	// @License
	public function canDowngrade(){
		return $this->isRankExist($this->rank - 1);
	}

	// @License
	public function downgrade(){
		if($this->canDowngrade()){
			$this->rank -= 1;
			return true;
		}
		return false;
	}

	public function getPrice(){
		switch($this->getRank()){
			case 1: $price = 1; break;
			case 2: $price = 1000; break;
			case 3: $price = 4000; break;
			case 4: $price = 9000; break;
			case 5: $price = 16000; break;
		}
		return isset($price) ? $price : 0;
	}

	public function getUpdatePrice(){
		switch($this->getRank()){
			case 1: $price = 1; break;
			case 2: $price = 50; break;
			case 3: $price = 200; break;
			case 4: $price = 400; break;
			case 5: $price = 800; break;
		}
		return isset($price) ? $price : 0;
	}

    public function getFullName(){
        return $this->getName()." ".$this->getRankText();
    }

	public function getImgPath(){
		$classar = explode("\\", get_class($this));
		$classname = $classar[count($classar) - 1];
		$rank = $this->isValidTime() ? $this->getRank() : 0;
		return "http://eard.space/images/license/normal/{$classname}_{$rank}.png";
	}

	public $ranktxt = [
		1 => "浮浪者",
		2 => "一般",
		3 => "中流",
		4 => "上流",
		5 => "富裕"
	];
	public $name = "生活";

}