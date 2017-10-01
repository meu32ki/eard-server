<?php
namespace Eard\Quests\Level1;

use Eard\Quests\Quest;
use Eard\MeuHandler\Account;

class Level1{
	public static $quests = [];
	public static $index = [];

	public static function registerQuests(){
		self::register(mission1::QUESTID, mission1::class);
		self::register(mission2::QUESTID, mission2::class);
		self::register(mission3::QUESTID, mission3::class);
		return true;
	}

	public static function canSend($player){
		return true;
	}

	public static function register($id, $class){
		self::$quests[$id] = $class;
		self::$index[] = $class;
		Quest::$allQuests[$id] = $class;
	}

	public static function getQuests(){
		return self::$quests;
	}

	public static function getIndex(){
		return self::$index;
	}
}