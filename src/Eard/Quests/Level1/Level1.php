<?php
namespace Eard\Quests\Level1;

use Eard\Quests\Quest;

class Level1{
	public static $quests = [];

	public static function registerQuests(){
		self::register(mission1::QUESTID, mission1::class);
		return true;
	}

	public static function register($id, $class){
		self::$quests[$id] = $class;
		Quest::$allQuests[$id] = $class;
	}

	public static function getQuests(){
		return self::$quests;
	}
}