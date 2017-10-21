<?php
namespace Eard\Quests\Level4;

use Eard\Quests\Quest;
use Eard\MeuHandler\Account;

use Eard\Quests\Level3\mission12;
use Eard\Quests\Level3\mission8;
use Eard\Quests\Level3\mission9;

class Level4{
	public static $quests = [];
	public static $index = [];

	public static function registerQuests(){
		self::register(mission14::QUESTID, mission14::class);
		self::register(mission15::QUESTID, mission15::class);
		self::register(mission16::QUESTID, mission16::class);
		self::register(mission17::QUESTID, mission17::class);
		self::register(mission18::QUESTID, mission18::class);
		self::register(mission19::QUESTID, mission19::class);
		self::register(mission20::QUESTID, mission20::class);
		self::register(mission21::QUESTID, mission21::class);
		return true;
	}

	public static function canSend($player){
		$playerData = Account::get($player);
		return (
			$playerData->isClearedQuest(mission12::QUESTID) &&
			$playerData->isClearedQuest(mission8::QUESTID) &&
			$playerData->isClearedQuest(mission9::QUESTID)
		);
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