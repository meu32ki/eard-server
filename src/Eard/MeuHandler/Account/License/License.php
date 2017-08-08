<?php
namespace Eard\MeuHandler\Account\License;

/***
*
*	ライセンスについて
*/
class License {

	//20170521 仕様が決まっていないためとりあえず放置

	const NEET = 0; const NOJOBS = 0;
	const GOVERNMENT_WORKER = 1;
	const BUILDER = 2;
	const MINER = 3;
	const TRADER = 4;
	const SERVICER = 5;
	const ENTREPRENEUR = 6;
	const FARAMER = 8;
	const DANGEROUS_ITEM_HANDLER = 8;

	const RANK_BEGINNER = 1;
	const RANK_GENERAL = 2;
	const RANK_SKILLED = 3;
	const RANK_PROFESSIONAL = 4;
	
	public static function init(){
		self::$list[self::GOVERNMENT_WORKER] = new GovernmentWorker;
		self::$list[self::BUILDER] = new Builder;
		self::$list[self::MINER] = new Miner;
		self::$list[self::TRADER] = new Trader;
		self::$list[self::SERVICER] = new Servicer;
		self::$list[self::ENTREPRENEUR] = new Entrepreneur;
		self::$list[self::FARAMER] = new Farmer;
		self::$list[self::DANGEROUS_ITEM_HANDLER] = new DangerousItemHandler;
	}

	public static function get($licenseNo, $time = null, $rank = null){
		$license = isset(self::$list[$licenseNo]) ? self::$list[$licenseNo] : null;
		if($license){
			if($time){
				$license->setValidTime($time);
			}
			if($rank){
				$license->setRank($rank);
			}
		}
		return $license;
	}

	/**
	*	@return Int 	各ライセンスに割り当てられた番号
	*/
	public function getLicenseNo(){
		return $this->no;
	}


	/**
	*	@param Timestamp 	そのライセンスの有効期限
	*/
	public function setValidTime($time){
		$this->time = $time;
		return true;
	}

	/**
	*	@return timestamp 	そのライセンスの有効期限
	*/
	public function getValidTime(){
		return $this->time;
	}

	/**
	*	ライセンスの有効時間であるかどうか じかんだけしらべる
	*	@return bool
	*/
	public function isValidTime(){
		return $this->time < time();
	}


	/**
	*	@param Int 	$rank
	*/
	public function setRank($rank){
		$this->rank = $rank;
		return true;
	}

	/**
	*	@return Int
	*/
	public function getRank(){
		return $this->rank;
	}

	/**
	*	@param int 	$rank
	*	@return bool
	*/
	public function isRankEnough($rank){
		return $this->rank <= $rank;
	}


	/**
	*	すべてが有効であるか
	*	@param Int self::RANKからはじまる値
	*/
	public function isValid($rank){
		$isTimeValid = $this->isValidTime();
		$isRankEnough = $this->isRankEnough($rank);
		return $isTimeValid && $isRankEnough;
	}


	private static $list = [];
	private $no = 0;
	private $time = 0;
	private $rank = self::RANK_BEGINNER;

}