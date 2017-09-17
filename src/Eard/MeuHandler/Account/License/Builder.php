<?php
namespace Eard\MeuHandler\Account\License;


class Builder extends License {

	public function getLicenseNo(){
		return self::BUILDER;
	}

	protected static $ranktxt = [
		1 => "初級",
		2 => "三級",
		3 => "二級",
		4 => "一級",
		5 => "マスター",
	];
	protected static $name = "建築士";

}