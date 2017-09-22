<?php
namespace Eard\MeuHandler\Account\License;


class Miner extends CostableUpgradeable {

	// @License
	public function getLicenseNo(){
		return self::MINER;
	}

	public $ranktxt = [
		1 => "1",
		2 => "2",
	];
	public $rankcost = [
	    1 => 2,
        2 => 3
    ];
	public $name = "採掘士";

}