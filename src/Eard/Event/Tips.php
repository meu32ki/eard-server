<?php
namespace Eard\Event;


class Tips {

	protected static $tips = [
		1 => "「ライセンス」の有効期限切れには注意しよう！",
		2 => "ウィットには気を付けよう！資源のほうが強いウィットが出るらしい…",
		3 => "スニーク状態で、素手でどこかを長押しすると「ヘルプ」が出るぞ！",
		4 => "操作がわからなくなったら「ヘルプ」を使うかwebを見よう",
		5 => "銀行から借りたμを使って、よりいい道具を使ってみないか？",
		6 => "お金を返せる自信があるなら銀行からお金を借りてみよう！",
		7 => "お金を使うだけではなく稼がないと、eardでは生きていけないぞ。",
		8 => "PVPをしたいなら、マイクラの「設定」からPVPをONにすれば、できるぞ！",
		9 => "皆で協力してこの惑星で生き抜こう！",
		10=> "政府は、雇用創出、インフラ開発、国民の皆さんのための開発をしています。",
		11=> "生活区域と資源区域は真反対の位置にあるので、昼夜が反転しているぞ！",
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