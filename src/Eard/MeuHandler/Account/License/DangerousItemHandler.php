<?php
namespace Eard\MeuHandler\Account\License;


class DangerousItemHandler extends Costable {

	// @License
	public function getLicenseNo(){
		return self::DANGEROUS_ITEM_HANDLER;
	}

	public function getFullName(){
		return $this->getName();
	}

	public $rankcost = [
	    1 => 1,
    ];
	public $name = "危険物取扱";

}