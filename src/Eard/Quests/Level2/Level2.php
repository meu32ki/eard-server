<?php
namespace Eard\Quests\Level2;

use Eard\Quests\Quest;

class Level2{
	public static $quests = [];
	public static $index = [];

	public static function registerQuests(){
		self::register(mission4::QUESTID, mission4::class);
		self::register(mission5::QUESTID, mission5::class);
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