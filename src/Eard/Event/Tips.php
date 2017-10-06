<?php
namespace Eard\Event;


class Tips {

	protected static $tips = [
		1 => "「ライセンス」の有効期限切れには注意しよう！",
		2 => "ウィットには気を付けよう！資源のほうが強いウィットが出るらしい…",
		3 => "スニーク状態で、素手でどこかを長押しすると「ヘルプ」が出るぞ",
		4 => "操作がわからなくなったら「ヘルプ」を使うかwebを見よう",
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