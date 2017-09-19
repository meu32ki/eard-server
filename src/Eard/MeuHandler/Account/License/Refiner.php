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

	protected static $rankcost = [
	    1 => 3,
    ];
	protected static $name = "精錬";

}