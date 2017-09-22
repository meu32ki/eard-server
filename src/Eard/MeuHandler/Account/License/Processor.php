<?php
namespace Eard\MeuHandler\Account\License;


class Processor extends CostableUpgradeable {

	// @License
	public function getLicenseNo(){
		return self::PROCESSOR;
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