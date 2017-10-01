<?php
namespace Eard\Quests\Level2;

use Eard\Quests\Quest;
use Eard\MeuHandler\Account;

use Eard\Quests\Level1\mission1;
use Eard\Quests\Level1\mission2;

class Level2{
	public static $quests = [];
	public static $index = [];

	public static function registerQuests(){
		self::register(mission4::QUESTID, mission4::class);
		self::register(mission5::QUESTID, mission5::class);
		self::register(mission6::QUESTID, mission6::class);
		self::register(mission7::QUESTID, mission7::class);
		return true;
	}

	public static function canSend($player){
		$playerData = Account::get($player);
		return (
			$playerData->isClearedQuest(mission1::QUESTID) &&
			$playerData->isClearedQuest(mission2::QUESTID)
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