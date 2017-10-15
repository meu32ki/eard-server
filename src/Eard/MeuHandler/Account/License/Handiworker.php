<?php
namespace Eard\MeuHandler\Account\License;


class Handiworker extends CostableUpgradeable {

	// @License
	public function getLicenseNo(){
		return self::HANDIWORKER;
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
	public $name = "細工師";

}