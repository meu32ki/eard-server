<?php
namespace Eard\MeuHandler\Account\License;


/***
*
*	ライセンスについて
*/
class License {


	//20180709 仕様が決まったのでゴリゴリ書いていこう
	//20170521 仕様が決まっていないためとりあえず放置

	// const NEET = 0; const NOJOBS = 0;
	const RESIDENCE = 1;
	const GOVERNMENT_WORKER = 2;

	const REFINER = 3;
	const FARMER = 4;
	const DANGEROUS_ITEM_HANDLER = 5;
	const MINER = 6;
	const APPAREL_DESIGNER = 7;
	const PROCESSOR = 7;
	const HUNTER = 9;
	const HANDIWORKER = 10;


	const RANK_BEGINNER = 1;
	const RANK_GENERAL = 2;
	const RANK_SKILLED = 3;
	const RANK_PROFESSIONAL = 4;
	const RANK_MASTER = 4;


	public static function init(){
		self::$list[self::RESIDENCE] = new Residence;
		self::$list[self::GOVERNMENT_WORKER] = new GovernmentWorker;

		self::$list[self::REFINER] = new Refiner;
		self::$list[self::FARMER] = new Farmer;
		self::$list[self::DANGEROUS_ITEM_HANDLER] = new DangerousItemHandler;
		self::$list[self::MINER] = new Miner;
		self::$list[self::APPAREL_DESIGNER] = new ApparelDesigner;
		self::$list[self::PROCESSOR] = new Processor;
		self::$list[self::HUNTER] = new Hunter;
		self::$list[self::HANDIWORKER] = new Handiworker;
	}


	/**
	*	@param int 				各ライセンスに割り当てられた番号
	*	@param Timestamp | -1 	有効期限を示すTimestamp。未来。
	*	@param Int 				self::RANK から始まる値
	*	@return License | null
	*/
	public static function get($licenseNo, $time = null, $rank = null){
		$license = isset(self::$list[$licenseNo]) ? clone self::$list[$licenseNo] : null;
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
	*	存在するすべてのライセンスを返す
	*/
	public static function getAll(){
		return self::$list;
	}

/*
	プレイヤーの更新関係
*/

	/**
	*	ライセンスのアップグレード時/購入時かかるお金を返す
	*	@return Int
	*/
	public function getPrice(){
		return 1000;
	}

	/**
	*	そのライセンスの有効期間を一週間延ばす場合にかかるお金を返す
	*	@return Int
	*/
	public function getUpdatePrice(){
		return 1000;
	}

	/**
	*	強制的にそのライセンスを無効にする
	*/
	public function expire(){
		$this->time = time();
		return true;
	}

	/**
	*	そのライセンスの有効期限を伸ばす。値がなければ一週間。
	*	@param int 増やす時間(秒)
	*/
	public function update($timeAmount = 0){
		if($timeAmount === 0){
			$timeAmount = 604800; // 一週間
		}
		// まだ有効期限内
		if(time() < $this->time){
			$this->time = $this->time + $timeAmount;
		// もう有効期限切れてる
		}else{
			$this->time = time() + $timeAmount;
		}
	}

	/**
	*	そのライセンスに、次のランクがあるのであれば、ライセンスの有効期間を一週間短くすることでランクを1段階上げる
	*	有効期限は伸びない。
	*/
	public function upgrade(){
		return false;
	}

	/**
	*	ライセンスのランクがあげられる場合にはtrueを返す
	*/
	public function canUpgrade(){
		return false;
	}

	/**
	*	ランクが上がった状態であるならば、ランクを一段階下げる
	*	有効期限は伸びない。
	*/
	public function downgrade(){
		return false;
	}

	/**
	*	ライセンスのランクがさげられる場合にはtrueを返す
	*/
	public function canDowngrade(){
		return false;
	}
/*
	オブジェクトの値関係
*/

	/**
	*	@return Int 	各ライセンスに割り当てられた番号
	*/
	public function getLicenseNo(){
		return $this->no;
	}

    /**
     *	ライセンスがそのランクを満たしており、かつ有効であるか
     *	@param Int self::RANKからはじまる値
     *	@return bool
     */
    public function isValid($rank){
        $isTimeValid = $this->isValidTime();
        $isRankEnough = $this->isRankEnough($rank);
        return $isTimeValid && $isRankEnough;
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
		return $this->time === -1 ? "無期限" : ( time() < $this->time ? date("n月j日G時i分")."まで有効" : date("n月j日G時i分")."に無効化済" );
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
	*	@param int 	$rank この値よりも大きいかどうか
	*	@return bool
	*/
	public function isRankEnough($rank){
		return $rank <= $this->rank;
	}

	/**
	*	そのランクの名前を返す
	*	@return String
	*/
	public function getRankText(){
		$rank = $this->rank;
		return isset(self::$ranktxt[$rank]) ? self::$ranktxt[$rank] : "[UNDEFINED]";
	}


	/**
	*	ライセンスの名前を返す
	*	@return String
	*/
	public function getName(){
		return self::$name;
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
	protected static $name = "";
	protected static $ranktxt = [];

}