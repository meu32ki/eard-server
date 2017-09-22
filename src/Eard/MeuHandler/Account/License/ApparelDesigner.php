<?php
namespace Eard\MeuHandler\Account\License;


class ApparelDesigner extends CostableUpgradeable {

	// @License
	public function getLicenseNo(){
		return self::APPAREL_DESIGNER;
	}

	public $ranktxt = [
		1 => "1",
		2 => "2",
	];
	public $rankcost = [
	    1 => 2,
        2 => 3,
    ];
	public $name = "服飾";

}