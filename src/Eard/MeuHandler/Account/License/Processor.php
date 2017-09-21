<?php
namespace Eard\MeuHandler\Account\License;


class Processor extends Costable {

	// @License
	public function getLicenseNo(){
		return self::PROCESSOR;
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

	public $ranktxt = [
		1 => "1",
		2 => "2",
		3 => "3",
	];
	public $rankcost = [
	    1 => 1,
        2 => 3,
        3 => 5
    ];
	public $name = "加工";

}