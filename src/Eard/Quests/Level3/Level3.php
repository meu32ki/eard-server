<?php
namespace Eard\Quests\Level3;

use Eard\Quests\Quest;

class Level3{
	public static $quests = [];
	public static $index = [];

	public static function registerQuests(){
		self::register(mission10::QUESTID, mission10::class);

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