<?php
namespace Eard\MeuHandler\Account\License;


class Hunter extends CostableUpgradeable {

	// @License
	public function getLicenseNo(){
		return self::HUNTER;
	}

	public $ranktxt = [
		1 => "1",
		2 => "2",
		3 => "3",
	];
	public $rankcost = [
	    1 => 2,
        2 => 4,
        3 => 6
    ];
	public $name = "ハンター";

}