<?php
namespace Eard\Quests\Level3;

use Eard\Quests\Quest;
use Eard\MeuHandler\Account;

use Eard\Quests\Level2\mission4;
use Eard\Quests\Level2\mission5;
use Eard\Quests\Level2\mission7;

class Level3{
	public static $quests = [];
	public static $index = [];

	public static function registerQuests(){
		self::register(mission8::QUESTID, mission8::class);
		self::register(mission9::QUESTID, mission9::class);
		self::register(mission10::QUESTID, mission10::class);
		self::register(mission11::QUESTID, mission11::class);
		self::register(mission12::QUESTID, mission12::class);
		self::register(mission13::QUESTID, mission13::class);
		return true;
	}

	public static function canSend($player){
		$playerData = Account::get($player);
		return (
			$playerData->isClearedQuest(mission4::QUESTID) &&
			$playerData->isClearedQuest(mission5::QUESTID) &&
			$playerData->isClearedQuest(mission7::QUESTID)
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