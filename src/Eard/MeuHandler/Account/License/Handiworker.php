<?php
namespace Eard\MeuHandler\Account\License;


class Handiworker extends Costable {

	// @License
	public function getLicenseNo(){
		return self::HANDIWORKER;
	}

	// @License
	public function canUpgrade(){
		if($this->rank == 1 or $this->rank == 2) return true;
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
		if($this->rank == 2 or $this->rank == 3) return true;
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
		2 => "3",
	];
	protected static $rankcost = [
	    1 => 1,
        2 => 3,
        3 => 5
    ];
	protected static $name = "服飾";

}