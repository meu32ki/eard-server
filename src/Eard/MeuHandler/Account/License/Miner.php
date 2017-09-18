<?php
namespace Eard\MeuHandler\Account\License;


class Miner extends Costable {

	// @License
	public function getLicenseNo(){
		return self::MINER;
	}

	// @License
	public function canUpgrade(){
		if(1 < $this->rank && $this->rank <= 2) return true;
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
		if($this->rank != 1 && 2 <= $this->rank) return true;
		return false;
	}

	// @License
	public function downgrade(){
		if($this->canDowngrade()){
			$this->rank -= 1;
		}
	}

	protected static $ranktxt = [
		1 => "1",
		2 => "2",
	];
	protected static $rankcost = [
	    1 => 2,
        2 => 3
    ];
	protected static $name = "採掘士";

}