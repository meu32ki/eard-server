<?php
namespace Eard;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\item\Item;
use pocketmine\permission\Permission;

# Command
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

# Muni
use Eard\DBCommunication\DB;
use Eard\DBCommunication\Connection;
use Eard\MeuHandler\Government;
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\License\License;
use Eard\Event\Event;
use Eard\Event\AreaProtector;
use Eard\Event\BlockObject\BlockObjectManager;
use Eard\Utils\ItemName;
use Eard\Utils\DataIO;
use Eard\Utils\Chat;
use Eard\DBCommunication\Earmazon;

# Enemys
use Eard\Enemys\EnemyRegister;
use Eard\Enemys\Spawn;
use Eard\Enemys\NPC;

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
	}

	public function reconnect(){
		Connection::load();

		/*
		Earmazon::setup();
		AreaProtector::setup();
		Connection::setup();
		*/

		# Eard関連
		AreaProtector::load();
		Account::load();
		BlockObjectManager::load();
		Government::load();
		new EnemyRegister();
		ItemName::init();
		License::init();
		Spawn::init(Connection::getPlace()->isLivingArea());//生活区域ならtrue、資源区域ならfalse

		self::$instance = $this;
	}

	public function onDisable(){

		# Eard関連
		Government::save();
		BlockObjectManager::saveAllObjects();
		BlockObjectManager::save();	
		Account::save();
		AreaProtector::save();

		# DB系
		Connection::close();
	}

	public function onCommand(CommandSender $s, Command $cmd, $label, array $a){
		$user = $s->getName();
		switch($cmd->getName()){
			case "test": // テスト用に変更とかして使う
				Earmazon::check();
				return true;
			break;
			case "li": // らいせんすかんけい
				if(isset($a[0])){
					switch($a[0]){
						case "give":
							if(isset($a[1]) && isset($a[2])){
								$name = $a[1];
								$no = $a[2];
								if(isset($a[3])){
									$time = $a[3] == -1 ? -1 : time() + (int) $a[3]; // 無期限でなければ、それを秒数だと認識し
								}else{
									$time = time() + 600; // デフォルトでは10分
								}
								$rank = isset($a[4]) ? (int) $a[4] : 1;
								$player = Server::getInstance()->getPlayer($name);
								if($player instanceof Player){
									$playerData = Account::get($player);
									$license = license::get($no, $time, $rank);
									if(!$license){
										$s->sendMessage(Chat::SystemToPlayer("そんなライセンスあらへんで"));
										return true;
									}

									$result = $playerData->addLicense($license);
									$out = 0 < $result ? "あげました" : "あげれませんでした";
									$s->sendMessage(Chat::SystemToPlayer($out));

									if($result){
										$player->sendMessage(Chat::SystemToPlayer("あなたは §f{$license->getFullName()}§7 のライセンスを手に入れました！ 有効期限：{$license->getValidTimeText()}"));
									}
								}else{
									$s->sendMessage(Chat::SystemToPlayer("プレイヤーおらへんで"));
								}
							}else{
								$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /li give <player> <licenceNo> [time] [rank]"));
							}							
						break;
						case "confirm":
							$playerData = Account::get($s);
							$licenses = $playerData->getAllLicenses();
							$out = "";
							foreach($licenses as $license){
								$out .= "{$license->getFullName()} {$license->getValidTimeText()}\n";
							}
							$out = !$out ? "あなたは何もライセンスを持っていません" : "\n{$out}";
							$s->sendMessage(Chat::SystemToPlayer($out));
						break;
						default:
							$s->sendMessage(Chat::SystemToPlayer("パラメータがおかしい /li [give|confirm]"));
						break;
					}
				}else{
					$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /li [give|confirm]"));
				}
				return true;
			break;
			case "ea":
				if(isset($a[0])){
					switch($a[0]){
						case "sell": // プレイヤーがこの値段で売れる
							$cnt = count($a);
							if(4 <= $cnt){
								$nometa = $cnt == 4 ? true : false;// パラメータが4つしかないときにはメタ値を省略
								$id = $a[1];
								$meta = !$nometa ? $a[2] : 0;
								$amount = !$nometa ? $a[3] : $a[2];
								$price = !$nometa ? $a[4] : $a[3];
								$result = Earmazon::addSellUnit($id, $meta, $amount, $price, false);
								$msg = $result ? "追加した" : "追加できなかった";
								$s->sendMessage(Chat::SystemToPlayer($msg));
							}else{
								$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /ea sell <id> <meta> <amount> <price>"));
							}
						break;
						case "buy": // プレイヤーがこの値段で買える
							$cnt = count($a);
							if(4 <= $cnt){
								$nometa = $cnt == 4 ? true : false;// パラメータが4つしかないときにはメタ値を省略
								$id = $a[1];
								$meta = !$nometa ? $a[2] : 0;
								$amount = !$nometa ? $a[3] : $a[2];
								$price = !$nometa ? $a[4] : $a[3];
								$result = Earmazon::addSellUnit($id, $meta, $amount, $price, false);
								$msg = $result ? "追加した" : "追加できなかった";
								$s->sendMessage(Chat::SystemToPlayer($msg));
							}else{
								$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /ea buy <id> <meta> <amount> <price>"));
							}
						break;
						case "item":

						break;
						case "give":
							$playerData = Account::get($s);
							if( $playerData->hasValidLicense(License::GOVERNMENT_WORKER, License::RANK_GENERAL) ){
								$cnt = count($a);
								if(3 <= $cnt){
									$nometa = $cnt == 3 ? true : false;// パラメータが3つしかないときにはメタ値を省略
									$id = $a[1];
									$meta = !$nometa ? $a[2] : 0;
									$amount = !$nometa ? $a[3] : $a[2];
									if( Earmazon::removeFromStorage($id, $meta, $amount) ){
										$item = Item::get($id, $meta, $amount);
										$s->getInventory()->addItem($item);
									}else{
										$storageamount = Earmazon::getStorageAmount($id, $meta);
										$s->sendMessage(Chat::SystemToPlayer("Earmazonのストレージにそんなに入ってない 入ってるのは {$storageamount}個"));
									}
								}else{
									$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /ea give <id> <meta> <amount>"));
								}
							}else{
								$s->sendMessage(Chat::SystemToPlayer("政府関係者でないので使えません"));
							}
						break;
					}
					return true;
				}
			break;
			case "ap": // Area Protector 土地関連
				if(isset($a[0])){
					switch($a[0]){
						case "a": // 販売セクションの発行数設定
							if(isset($a[1]) && 0 < (int) $a[1]){
								$result = AreaProtector::setAffordableSection($a[1]);
								$out = $result ? Chat::SystemToPlayer("販売セクション数を{$a[1]}に設定しました") : Chat::SystemToPlayer("設定できませんでした");
								$s->sendMessage($out);
								return true;
							}else{
								return false;
							}
						    break;
                        case "v": // 販売されてるセクションの発行数確認
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
                        		$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /ap give <player>"));
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
						case "a": // 発行量設定
							if(isset($a[1]) && 0 < (int) $a[1]){
								$result = Government::setCentralBankFirst($a[1]);
								$out = $result ? Chat::SystemToPlayer("政府の通貨発行量を{$a[1]}に設定しました。") : Chat::SystemToPlayer("設定できませんでした");
								$s->sendMessage($out);
								return true;
							}else{
								return false;
							}
						break;
						case "v": // 発行数の確認
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
								$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /gv give <player> <amount>"));
							}
							return true;
						break;
					}
				}
				return false;				
			break;
			case "co": // Connection
				if(isset($a[0])){
					$place = (int) $a[0];
					Connection::writePlace($place);
				}else{
					$s->sendMessage(Chat::SystemToPlayer("パラメータ不足 /co <place>"));
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
			case "npc":
				if($s instanceof Player && count($a) >= 1){
					$s->sendMessage(Chat::SystemToPlayer("召喚したで"));
					NPC::summon($s->getLevel(), $s->x, $s->y, $s->z, EnemyRegister::loadSkinData('Buriki'), 'Standard_Custom', $a[0]);
					return true;
				}else{
					$s->sendMessage(Chat::SystemToPlayer("コンソールじゃ無理"));
					return false;
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

