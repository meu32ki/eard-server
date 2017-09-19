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

	protected static $rankcost = [
	    1 => 1,
    ];
	protected static $name = "危険物取扱";

}