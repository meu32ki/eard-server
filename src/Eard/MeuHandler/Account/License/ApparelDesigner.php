<?php
namespace Eard\MeuHandler\Account\License;


class ApparelDesigner extends Costable {

	// @License
	public function getLicenseNo(){
		return self::APPAREL_DESIGNER;
	}

	// @License
	public function canUpgrade(){
		if($this->rank == 1) return true;
		return false;
	}

	// @License
	public function upgrade(){
		if($this->canUpgrade()){
			$this->rank += 1;
		}
	}

	// @License
	public function canDowngrade(){
		if($this->rank == 2) return true;
		return false;
	}

	// @License
	public function downgrade(){
		if($this->canDowngrade()){
			$this->rank -= 1;
		}
	}

	protected static $ranktxt = [
		1 => "1",
		2 => "2",
	];
	protected static $rankcost = [
	    1 => 2,
        2 => 3,
    ];
	protected static $name = "服飾";

}