<?php
namespace Eard\MeuHandler\Account\License;


class Farmer extends Costable {

	// @License
	public function getLicenseNo(){
		return self::FARMER;
	}

	public function getFullName(){
		return $this->getName();
	}

	protected static $rankcost = [
	    1 => 3,
    ];
	protected static $name = "農家";

}