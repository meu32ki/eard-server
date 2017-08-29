<?php
namespace Eard\Event;

class Tips {

	protected static $tips = [
		1 => "ウィットには気を付けよう！",
		2 => "資源のほうが強いウィットが出るらしい…",
		3 => "政府公認Earmazonがアイテムの販売買取を実施中！",
		4 => "携帯をなくさないように！",
		5 => "携帯で生活区域と資源区域を行き来できるようになっているぞ",
	];

	public static function getTips($no){
		if(!isset(self::$tips[$no])){
			return "§4存在しないtips";
		}
		return self::$tips[$no];
	}

	public static function getRandomTips(){
		$tips = self::$tips;
		shuffle($tips);
		return "§e".$tips[0];
	}
}