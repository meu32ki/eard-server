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

	public $rankcost = [
	    1 => 3,
    ];
	public $name = "農家";

}