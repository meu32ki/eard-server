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
	const RANK_MASTER = 4;


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
	*	@param Timestamp | -1	そのライセンスの有効期限 -1であれば無期限
	*/
	public function setValidTime($time){
		$this->time = $time;
		return true;
	}

	/**
	*	@return Timestamp | -1	そのライセンスの有効期限 -1であれば無期限
	*/
	public function getValidTime(){
		return $this->time;
	}

	/**
	*	ライセンスの有効であるかどうか じかんだけしらべる
	*	@return bool
	*/
	public function isValidTime(){
		return $this->time === -1 ? true : time() < $this->time;
	}

	/**
	*	@return String
	*/
	public function getValidTimeText(){
		return $this->time === -1 ? "無期限" : date("n月j日 G時i分")."まで";
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
	*	そのランクの名前を返す
	*	@return String
	*/
	public function getRankText(){
		$rank = $this->rank;
		return isset($this->ranktxt[$rank]) ? $this->ranktxt[$rank] : "[UNDEFINED]";
	}


	/**
	*	ライセンスが有効であるか
	*	@param Int self::RANKからはじまる値
	*	@return bool
	*/
	public function isValid($rank){
		$isTimeValid = $this->isValidTime();
		$isRankEnough = $this->isRankEnough($rank);
		return $isTimeValid && $isRankEnough;
	}

	/**
	*	ライセンスの名前を返す
	*	@return String
	*/
	public function getName(){
		return $this->name;
	}

	/**
	*	ライセンスの名前をランク入りで返す
	*	@return String
	*/
	public function getFullName(){
		return $this->getName()."(".$this->getRankText().")";
	}


	private static $list = [];
	private $no = 0;
	private $time = 0;
	private $rank = self::RANK_BEGINNER;
	private $name = "";
	private $ranktxt = [
		1 => "初心者",
		2 => "中級者",
		3 => "上級者",
		4 => "プロ",
		5 => "マスター",
	];

}