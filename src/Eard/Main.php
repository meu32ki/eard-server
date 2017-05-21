<?php
namespace Eard;

# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

use pocketmine\permission\Permission;

# Command
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

# Muni
use Eard\DB;
use Eard\Event;
use Eard\AreaProtector;

/***
*
*	コマンド関係のみ
*/
class Main extends PluginBase implements Listener, CommandExecutor{


	public function onLoad(){
	}

	public function onEnable(){
		date_default_timezone_set('asia/tokyo');
		$this->getServer()->getPluginManager()->registerEvents(new Event(), $this);

		#Muni関連
		DB::mysqlConnect(true);
		AreaProtector::init();
		self::$instance = $this;
	}

	public function onCommand(CommandSender $s, Command $cmd, $label, array $a){
		$user = $s->getName();
		switch($cmd->getName()){
			case "test":
				//$no = isset($a[0]) ? $a[0] : 0;
				$playerData = Account::getByName('meu32ki');
				$player = $playerData->getPlayer();
				AreaProtector::cal($player);
				return true;
			break;
			default:
				return true;
			break;
		}
	}

	public static function getInstance(){
		return self::$instance;
	}
	public static $instance = null;
}
