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
use Eard\BlockObject\BlockObjectManager;


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
		AreaProtector::load();
		Account::load();
		BlockObjectManager::load();
		self::$instance = $this;
	}

	public function onDisable(){

		#Muni関連
		BlockObjectManager::save();	
		Account::save();
		AreaProtector::saveAllObjects();
		AreaProtector::save();
	}

	public function onCommand(CommandSender $s, Command $cmd, $label, array $a){
		$user = $s->getName();
		switch($cmd->getName()){
			case "test": // テスト用に変更とかして使う
				//$no = isset($a[0]) ? $a[0] : 0;
				$playerData = Account::getByName('meu32ki');
				$player = $playerData->getPlayer();
				AreaProtector::cal($player);
				return true;
			break;
			case "ap": // Area Protector 土地関連
				if(isset($a[0])){
					switch($a[0]){
						case "afs": // 販売セクションの設定
							if(0 < (int) $a[1]){
								AreaProtector::setAffordableSection($a[1]);
								$out = Chat::System("販売セクション数を{$a[1]}に設定しました。");
								$s->sendMessage($out);
								return true;
							}else{
								return false;
							}
						    break;
                        case "lfs": // 販売されてるセクションの確認
                            $leftSection = AreaProtector::$leftSection;
                            $affordableSection = AreaProtector::$affordableSection;
                            $out = Chat::System("販売可能土地数: {$leftSection} / {$affordableSection}");
                            $s->sendMessage($out);
                            return true;
                            break;
                        case "give": // セクションをタダで渡す
                        	if(isset($a[1])){
                        		$name = (string) $a[1];
                        		if( ($player = Server::getInstance()->getPlayer($name) ) instanceof Player){
                        			$playerData = Account::get($player);
                        			$sectionNoX = AreaProtector::calculateSectionNo($player->getX());
                        			$sectionNoZ = AreaProtector::calculateSectionNo($player->getZ());
                        			$result = AreaProtector::giveSection($playerData, $sectionNoX, $sectionNoZ);
                        			if($result){
										$sectionCode = AreaProtector::getSectionCode($sectionNoX, $sectionNoZ);
										$s->sendMessage(Chat::System("{$sectionCode}を{$player->getName()}さんにあげました。"));
										$player->sendMessage(Chat::System("政府から{$sectionCode}をもらいました。"));
                        			}else{
										$s->sendMessage(Chat::System("あげれへんかったで。"));
                        			}
                        		}else{
                        			$s->sendMessage(Chat::System("その名前のプレイヤーおんらいんちゃうで。"));
                        		}
                        	}else{
                        		$s->sendMessage(Chat::System("パラメータ不足"));
                        	}
                        break;
						default: break;
					}
				}else{
					$out = "/ap afs <int> : 販売するセクションをせってい\n/ap lfs : 売られているセクションの個数";
                    $s->sendMessage($out);
					return false;
				}
			case "gv": // Government 政府のお金関連
				if(isset($a[0])){
					switch($a[0]){
						case "give":
							if(isset($a[1]) && isset($a[2])){
								$name = $a[1];
								$amount = $a[2];
								$player = Server::getInstance()->getPlayer($name);
								if($player instanceof Player){
									$playerData = Account::get($player);
									$result = Government::giveMeu($playerData, $amount);
									$out = $result ? "あげた" : "あげてない";
									$s->sendMessage($out);
								}
							}else{

							}
						break;
					}
				}else{

				}				
			break;
			case "enemy":
				if($s instanceof Player && count($a) >= 1){
					EnemyRegister::summon($s->getLevel(), $a[0], $s->x, $s->y, $s->z);
					return true;
				}else{
					return false;
				}
			break;
			case "saveskin":
				if(count($a) == 1){
					$skinName = $a[0];
					$savePlayer = Server::getInstance()->getPlayer($skinName);
					$result = self::saveSkinData($savePlayer);
					if($result === false){
						Command::broadcastCommandMessage($s, "そのプレイヤーは存在しません");
					}else{
						Command::broadcastCommandMessage($s, "スキンをセーブしました");
						return true;
					}
				}
			break;
			default:
				return true;
			break;
		}
	}

	/*
	 * スキンデータをセーブ
	 */
	public static function saveSkinData(Player $player){
		if($player instanceof Player){
			$path = __FILE__ ;
			$dir = dirname($path);
			$name = $player->getName();
			$fullPath = $dir.'/data/skins/'.$name.'.txt';
			$skinData = $player->getSkinData();
			$encode_skin = urlencode($skinData);
			file_put_contents($fullPath, $encode_skin);
			Command::broadcastCommandMessage($player, "Skin ID:".$player->getSkinId());
			return true;
		}
		return false;
	}

	public static function getInstance(){
		return self::$instance;
	}
	public static $instance = null;
}

