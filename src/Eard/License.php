<?php
namespace Eard;

/***
*
*	ライセンスについて
*/
class License {

	//20170521 仕様が決まっていないためとりあえず放置

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
		self::$list[self::BUILDER] = new Builder;
		self::$list[self::MINER] = new Miner;
		self::$list[self::TRADER] = new Trader;
		self::$list[self::SERVICER] = new Servicer;
		self::$list[self::ENTREPRENEUR] = new Entrepreneur;
		self::$list[self::FARAMER] = new Farmer;
		self::$list[self::DANGEROUS_ITEM_HANDLER] = new DangerousItemHandler;
		self::$list[self::GOVERNMENT_WORKER] = new GovernmentWorker;
	}

	public function getLicenseNo(){
		return $this->no;
	}
	public function delayTime(){
		return $this->delay;
	}

	private static $list = [];
	private $no, $delay;

}


class Builder extends License {



}