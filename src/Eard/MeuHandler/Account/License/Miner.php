<?php
namespace Eard\MeuHandler\Account\License;


class Miner extends License {

	/*
		1 => さいくつそくとあっぷ(1)
		2 => さいくつそくどあっぷ(1) きん/だいやがほれる
		3 => さいくつそくどあっぷ(2) きん/だいやがほれる RS/らぴすがほれる
		４　=> さいくつそくどあっぷ(3) きん/だいやがほれる RS/らぴすがほれる えーてるでなんかすごいやつ(雑)
		5 => さいくつそくどあっぷ(4) きん/だいやがほれる RS/らぴすがほれる えーてるでなんかすごいやつ(雑) すごいやつだいにだんがつかえる
	*/

	public function getLicenseNo(){
		return self::MINER;
	}

	protected $ranktxt = [
		1 => "初級",
		2 => "三級",
		3 => "二級",
		4 => "一級",
		5 => "マスター",
	];
	protected $name = "採掘士";

}