<?php
namespace Eard\MeuHandler\Account\License;


class Builder extends License {

	public function getLicenseNo(){
		return self::BUILDER;
	}

	public $ranktxt = [
		1 => "初級",
		2 => "三級",
		3 => "二級",
		4 => "一級",
		5 => "マスター",
	];
	public $name = "建築士";

}