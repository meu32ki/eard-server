<?php
namespace Eard\Event;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;

# Eard
use Eard\DBCommunication\Connection;
use Eard\MeuHandler\Account;
use Eard\Utils\Chat;


class Tips {

	protected static $tips = [
		1 => "ウィットには気を付けよう！",
		2 => "夜間の方が強力なウィットが出るよ！",
		3 => "Earmazonでアイテムの売買できるよ！",
		4 => "スマホをなくさないようにね",
		5 => "スマホで生活区域と資源区域を行き来できるよ",
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
		return $tips[0];
	}
}