<?php
namespace Eard\MeuHandler\Account\License;


use Eard\Utils\Time;


/***
*
*	ライセンスについて
*/
class License {

	//20170925 すげえいろいろ書き足したなあ
	//20170709 仕様が決まったのでゴリゴリ書いていこう
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
	public static function get($licenseNo, $time = null, $rank = 1){
		$license = isset(self::$list[$licenseNo]) ? clone self::$list[$licenseNo] : null;
		if($license && $license->isRankExist($rank)){
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
	オブジェクトの値関係
*/

	/**
	*	@return Int 	各ライセンスに割り当てられた番号
	*/
	public function getLicenseNo(){
		return $this->no;
	}

	/**
	*	ライセンスのアップグレード時/購入時かかるお金を返す
	*	@return Int
	*/
	public function getPrice(){
		return 1000;
	}

	/**
	*	そのライセンスに、次のランクがあるのであれば、ランクを1段階上げる
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

	/**
	*	そのライセンスの有効期間を一日延ばす場合にかかるお金を返す
	*	@return Int
	*/
	public function getUpdatePrice(){
		return 100;
	}

	/**
	*	強制的にそのライセンスを無効にする 実際に無効化されるのは2時間後
	*/
	public function expire(){
		if(!$this->isExpireing()){
			$this->time = time() + 60 * 60 * 2;
			return true;
		}else{
			// すでに無効化段階に入っている場合は何もしない。期限が伸びることになるので。
			return false;
		}
	}

	/**
	*	現在無効化段階に入っているか(残り二時間を切っているか) 切っていればtrue
	*	無期限の場合はfalseを返す
	*	@return bool
	*/
	public function isExpireing(){
		return $this->time === -1 ? false : $this->time - time() < 7200;
	}

	/**
	*	そのライセンスの有効期限を伸ばす。値がなければ一週間。
	*	@param int 増やす時間(秒)
	*/
	public function update($timeAmount){
		// まだ有効期限内
		if(time() < $this->time){
			$this->time = $this->time + $timeAmount;
		// もう有効期限切れてる
		}else{
			$this->time = time() + $timeAmount;
		}
		return true;
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
	*	そのライセンスの有効期限はいつまでか
	*	@return String
	*/
	public function getValidTimeText(){
		switch($this->time){
			case -1: $out = "無期限"; break;
			case 0: $out = "未使用"; break;
			default: $out = time() < $this->time ? date("n月j日G時i分", $this->time)."まで有効" : date("n月j日G時i分", $this->time)."に無効化済"; break;
		}
		return $out;
	}

	/**
	*	そのライセンスの有効期限まで何日何分何秒か
	*	@return String
	*/
	public function getLeftTimeText(){
		$sec = $this->time - time();
		if(0 < $sec){
			$out = Time::calculateTime($sec);
		}
		return $this->time === -1 ? "" : (0 < $sec ? "残り {$out}" : "");
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
	*	そのランクが存在しているかどうか (giveの時の確認、レベルアップ確認に使う)
	*	@param Int (Rank) 入れた場合には、そのランクが存在するかを返し、入れなかった場合には、現在のこのライセンスのランクが存在するかを返す
	*	@return bool
	*/
	public function isRankExist($rank = false){
		$rank = $rank ? $rank : $this->rank;
		return isset($this->ranktxt[$rank]);
	}

	/**
	*	そのランクの名前を返す
	*	@return String
	*/
	public function getRankText(){
		$rank = $this->rank;
		if(!$rank) return "";
		return isset($this->ranktxt[$rank]) ? $this->ranktxt[$rank] : "[UNDEFINED]";
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
		return $this->rank ? $this->getName()."(".$this->getRankText().")" : $this->getName();
	}

	/**
	*	ライセンスの画像の置き場所のurlを返す
	*	@return String URL
	*/
	public function getImgPath(){
		$classar = explode("\\", get_class($this));
		$classname = $classar[count($classar) - 1];
		$rank = $this->getRank();
		$status = $this->isValidTime() ? "activated" : "normal";
		return "http://eard.space/images/license/{$status}/{$classname}_{$rank}.png";
	}


	private static $list = [];
	private $no = 0;
	private $time = 0;
	public $rank = 0;
	public $name = "";
	public $ranktxt = [];

}