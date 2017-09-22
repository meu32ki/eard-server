<?php
namespace Eard\MeuHandler\Account\License;


class Refiner extends Costable {

	// @License
	public function getLicenseNo(){
		return self::REFINER;
	}

	public function getFullName(){
		return $this->getName();
	}

	public $rankcost = [
	    1 => 3,
    ];
    public $ranktxt = [
    	1 => "",
    ];
	public $name = "精錬";

}