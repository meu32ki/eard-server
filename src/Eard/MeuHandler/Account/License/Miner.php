<?php
namespace Eard\MeuHandler\Account\License;


class Miner extends Costable {

	public function getLicenseNo(){
		return self::MINER;
	}

	protected static $ranktxt = [
		1 => "1",
		2 => "2",
	];
	protected static $rankcost = [
	    1 => 2,
        2 => 3
    ];
	protected static $name = "採掘士";

}