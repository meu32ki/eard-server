<?php
namespace Eard\Quests;

class Quest{
	public static $allQuests = [];

	public static function getAllQuests(){
		return self::$allQuests;
	}

	public static function get(int $id){
		return isset(self::$allQuests[$id])? self::$allQuests[$id] : null;
	}
}