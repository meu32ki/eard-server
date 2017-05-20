<?php

namespace Eard;


class License {


	const NEET = 0; const NOJOBS = 0;
	const BUILDER = 1;
	const MINER = 2;
	const TRADER = 3;
	const SERVICER = 4;
	const ENTREPRENEUR = 5;
	const FARAMER = 6;
	const DANGEROUS_ITEM_HANDLER = 7;
	const GOVERNMENT_WORKER = 8;
	
	public static function init(){
		//self::$list[self::BUILDER] = new Builder;
	}

	public function getLicenseNo(){
		return $this->no;
	}
	public function delayTime(){
		return $this->delay;
	}

	public static $list = [];
	public $no, $delay;


}