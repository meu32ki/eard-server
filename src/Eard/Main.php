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
use Eard\Enemys\EnemyRegister;
use Eard\Utils\ItemName;
use Eard\Enemys\Spawn;


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

		# DB系
		DataIO::init(); //たぶん一番最初に持ってくるべき
		$connected = DB::mysqlConnect(true);
		if($connected){
			//正常にmysqlにつなげた場合のみ。
			$this->reconnect();
		}
		Spawn::init(Connection::getPlace()->isLivingArea());//生活区域ならtrue、資源区域ならfalse
	}

	public function reconnect(){
		Connection::load();
		// Connection::setup();

		# Muni関連
		AreaProtector::load();
		Account::load();
		BlockObjectManager::load();
		Government::load();
		new EnemyRegister();
		ItemName::init();
		// Earmazon::setup();
		self::$instance = $this;
	}

	public function onDisable(){

		#Muni関連
		Government::save();
		BlockObjectManager::saveAllObjects();
		BlockObjectManager::save();	
		Account::save();
		AreaProtector::save();

		# DB系
		Connection::getPlace()->makeOffline();
	}

	public function onCommand(CommandSender $s, Command $cmd, $label, array $a){
		$user = $s->getName();
		switch($cmd->getName()){
			case "test": // テスト用に変更とかして使う
				Earmazon::check();
				return true;
			break;
			case "ap": // Area Protector 土地関連
				if(isset($a[0])){
					switch($a[0]){
						case "asec": // 販売セクションの発行数設定
							if(isset($a[1]) && 0 < (int) $a[1]){
								$result　= AreaProtector::setAffordableSection($a[1]);
								$out = $result ? Chat::SystemToPlayer("販売セクション数を{$a[1]}に設定しました") : Chat::SystemToPlayer("設定できませんでした");
								$s->sendMessage($out);
								return true;
							}else{
								return false;
							}
						    break;
                        case "vsec": // 販売されてるセクションの発行数確認
                            $leftSection = AreaProtector::$leftSection;
                            $affordableSection = AreaProtector::$affordableSection;
                            $out = Chat::SystemToPlayer("販売可能土地数: {$leftSection} / {$affordableSection}");
                            $s->sendMessage($out);
                            return true;
                            break;
                        case "give": // セクションをタダで渡す
                        	if(isset($a[1])){
                        		$name = (string) $a[1];
                        		if( ( $player = Server::getInstance()->getPlayer($name) ) instanceof Player){
                        			$playerData = Account::get($player);
                        			$sectionNoX = AreaProtector::calculateSectionNo($player->getX());
                        			$sectionNoZ = AreaProtector::calculateSectionNo($player->getZ());
                        			$result = AreaProtector::giveSection($playerData, $sectionNoX, $sectionNoZ);
                        			if($result){
										$sectionCode = AreaProtector::getSectionCode($sectionNoX, $sectionNoZ);
										$s->sendMessage(Chat::SystemToPlayer("{$sectionCode}を{$player->getName()}さんにあげました"));
										$player->sendMessage(Chat::SystemToPlayer("政府から{$sectionCode}をもらいました"));
                        			}else{
										$s->sendMessage(Chat::SystemToPlayer("あげれへんかったで"));
                        			}
                        		}else{
									$s->sendMessage(Chat::SystemToPlayer("プレイヤーおらへんで"));
                        		}
                        	}else{
                        		$s->sendMessage(Chat::SystemToPlayer("パラメータ不足"));
                        	}
                        	return true;
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
						case "ameu": // 発行量設定
							if(isset($a[1]) && 0 < (int) $a[1]){
								$result = Government::setCentralBankFirst($a[1]);
								$out = $result ? Chat::SystemToPlayer("政府の通貨発行量を{$a[1]}に設定しました。") : Chat::SystemToPlayer("設定できませんでした");
								$s->sendMessage($out);
								return true;
							}else{
								return false;
							}
						break;
						case "vmeu": // 発行数の確認
							$out = Government::confirmBalance();
							$s->sendMessage($out);
							return true;
						break;
						case "give": // ただでお金が欲しい！
							if(isset($a[1]) && isset($a[2])){
								$name = $a[1];
								$amount = $a[2];
								$player = Server::getInstance()->getPlayer($name);
								if($player instanceof Player){
									$playerData = Account::get($player);
									$result = Government::giveMeu($playerData, $amount);
									$out = $result ? "{$amount}μあげた" : "あげられなかった 政府の予算が足りない";
									$s->sendMessage(Chat::SystemToPlayer($out));
								}else{
									$s->sendMessage(Chat::SystemToPlayer("プレイヤーおらへんで"));
								}
							}else{
								$s->sendMessage(Chat::SystemToPlayer("パラメータ不足"));
							}
							return true;
						break;
					}
				}
				return false;				
			break;
			case "co": // Connection
				if(isset($a[0])){
					$p = strtolower($a[0]);
					if($p === "place"){
						if(isset($a[1])){
							$place = $a[1];
							Connection::writePlace($place);
							return true;
						}else{
							$s->sendMessage(Chat::SystemToPlayer("/co place <数字> で入力してくれ"));						
						}
					}else if($p === "addr"){
						if(isset($a[1]) && isset($a[2])){
							$place = (int) $a[1];
							$addrtxt = $a[2];
							$result = Connection::writeAddr($place, $addrtxt);
							$out = $result ? Chat::SystemToPlayer("セーブ完了") : Chat::SystemToPlayer("なんかセーブできなかったらしい");
							$s->sendMessage($out);
						}else{
							$s->sendMessage(Chat::SystemToPlayer("/co addr address:port のように入力"));				
						}
					}else{
						$s->sendMessage(Chat::SystemToPlayer("パラメータおかしいで、なおして /co [place|addr]"));
					}
				}else{
					$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /co [place|addr]"));
				}
				return true;
			break;
			case "db": // Data base connect info
				if(isset($a[0])){
					$p = strtolower($a[0]);
					$value = isset($a[1]) ? $a[1] : "";
					if(!$value){
						$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /co {$p} <{$p}> のように入力"));
						return true;
					}
					switch($p){
						case "addr":
							DB::writeAddr($value);
						break;
						case "user":
							DB::writeUser($value);
						break;
						case "pass":
							DB::writePass($value);
						break;
						case "name":
							DB::writename($value);
						break;
						default:
							$s->sendMessage(Chat::SystemToPlayer("パラメータ異常 /co [addr|user|pass|name] で入力"));
						break;
					}
				}else{
					$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /co [addr|user|pass|name]"));
				}
				return true;
			break;
			case "enemy":
				if($s instanceof Player && count($a) >= 1){
					$s->sendMessage(Chat::SystemToPlayer("召喚したで"));
					EnemyRegister::summon($s->getLevel(), $a[0], mt_rand(-10, 10) + $s->x, $s->y, mt_rand(-10, 10) + $s->z);
					return true;
				}else{
					$s->sendMessage(Chat::SystemToPlayer("コンソールじゃ無理"));
					return false;
				}
			break;
			case "saveskin":
				if(count($a) == 1){
					$skinName = $a[0];
					$savePlayer = Server::getInstance()->getPlayer($skinName);
					$result = self::saveSkinData($savePlayer);
					if($result === false){
						Command::broadcastCommandMessage($s, Chat::SystemToPlayer("そのプレイヤーは存在しません"));
					}else{
						Command::broadcastCommandMessage($s, Chat::SystemToPlayer("スキンをセーブしました"));
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
			$fullPath = $dir.'/Enemys/skins/'.$name.'.txt';
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

