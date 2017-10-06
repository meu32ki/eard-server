<?php
namespace Eard\Event;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\item\Item;
use pocketmine\block\Block;

# Event
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerFishEvent;
use pocketmine\event\player\PlayerRespawnEvent;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\scheduler\Task;

# NetWork
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\ServerSettingsRequestPacket;

# Eard
use Eard\Main;
use Eard\DBCommunication\Connection;
use Eard\Event\AreaProtector;
use Eard\Event\ChatManager;
use Eard\Event\BlockObject\BlockObjectManager;
use Eard\Form\MenuForm;
use Eard\Form\HelpForm;
use Eard\Form\SettingsForm;
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\Menu;
use Eard\MeuHandler\Account\License\License;
use Eard\MeuHandler\Account\License\Recipe;
use Eard\Utils\Chat;
use Eard\Utils\ItemName;

# Enemy
use Eard\Enemys\EnemyRegister;
use Eard\Enemys\Humanoid;
use Eard\Enemys\NPC;
use Eard\Enemys\Unagi;
use Eard\Enemys\Umimedama;
use Eard\Enemys\AI;

# Quest
use Eard\Quests\QuestManager;
use Eard\Quests\Quest;

/***
*
*	言わずもがな。
*/
class Event implements Listener{

	public function L(PlayerLoginEvent $e){
		$player = $e->getPlayer();
		$playerData = Account::get($player);

		// dev
		$dev = false; // devモードの時はtrue
		$name = strtolower($player->getName());
		if($dev && $playerData->hasValidLicense(License::GOVERNMENT_WORKER, License::RANK_GENERAL) ){
			$e->setCancelled(true);
			return true;
		}

		//playerData関連の準備
		$playerData->setPlayer($player);//	touitusuruna
		$playerData->loadData();
		$playerData->initItemBox();
		$playerData->onLoadTime();

		#権限関係
		$main = Main::getInstance();
		$perms = [
			"pocketmine.command.gamemode" => false,
			"pocketmine.command.version" => false,
			"pocketmine.command.me" => false,
			"pocketmine.command.tell" => false,
			"pocketmine.command.kill" => false,
		];
		foreach($perms as $name => $value){
			$player->addAttachment($main, $name, $value);
		}
	}



	public function J(PlayerJoinEvent $e){
		$player = $e->getPlayer();
		$e->setJoinMessage(Chat::getJoinMessage($player->getDisplayName()));
		Connection::getPlace()->recordLogin($player->getName()); //　オンラインテーブルに記録
		Account::get($player)->applyEffect();
	}



	public function R(PlayerRespawnEvent $e){
		$player = $e->getPlayer();
		$task = new Delay($player, function ($player){
			Account::get($player)->applyEffect();
			$inv = $player->getInventory();
			$inv->addItem(Item::get(416));
		});
		Server::getInstance()->getScheduler()->scheduleDelayedTask($task, 5);
	}


	public function Q(PlayerQuitEvent $e){
		$player = $e->getPlayer();
		if($player->spawned){ //whitelistでひっかかっていなかったら
			$playerData = Account::get($player);

			// 必ずしも閉じる必要あるかな？
			/*
			if($playerData->getFormObject() instanceof Form){
				$playerData->getFormObject()->close();
			}
			*/

			//　オンラインテーブルから記録消す
			Connection::getPlace()->recordLogout($player->getName());

			// 退出時メッセージ
			$msg = $playerData->isNowTransfering() ? Chat::getTransferMessage($player->getDisplayName()) : Chat::getQuitMessage($player->getDisplayName());
			$e->setQuitMessage($msg);

			$playerData->onUpdateTime();
			$playerData->updateData(true);
			//quitの最後に持ってくること。他の処理をこの後に入れない。セーブされないデータが出てきてしまうかもしれないから。
		}
	}


	// 20170928 1.2対応のためいったん無効 20171003 対応させたので復活
	public function F(PlayerFishEvent $e){
		$item = $e->getItem();
		$hook = $e->getHook();
		switch($item->getId()){
			case 280:
				if(Connection::getPlace()->isLivingArea()){
					$wiht = Umimedama::summon($e->getPlayer()->getLevel(), $hook->x, $hook->y, $hook->z);
				}else{
					$wiht = Unagi::summon($e->getPlayer()->getLevel(), $hook->x, $hook->y, $hook->z);					
				}
				AI::jump($wiht);
				$e->setCancelled(true);
			break;
			case 281:
				$wiht = Umimedama::summon($e->getPlayer()->getLevel(), $hook->x, $hook->y, $hook->z);
				AI::jump($wiht);
				$e->setCancelled(true);
			break;
		}
	}

	public function PacketReceive(DataPacketReceiveEvent $e){
		$packet = $e->getPacket();
		$player = $e->getPlayer();
		$name = $player->getName();
		switch($packet::NETWORK_ID){
			case ProtocolInfo::PLAYER_ACTION_PACKET:
				//壊し始めたとき
				if($packet->action === PlayerActionPacket::ACTION_START_BREAK){
					$x = $packet->x; $y = $packet->y; $z = $packet->z;
					if(!AreaProtector::$allowBreakAnywhere){
						AreaProtector::Edit($player, $x, $y, $z);
						//キャンセルとかはさせられないので、表示を出すだけ。
					}else{
						$e->setCancelled( BlockObjectManager::startBreak($x, $y, $z, $player) );
					}
				}
				$x = $packet->x; $y = $packet->y; $z = $packet->z;
			break;
			case ProtocolInfo::MODAL_FORM_RESPONSE_PACKET:

				$id = $packet->formId;
				$data = $packet->formData;

				// オブジェクトがあればそっち優先
				$playerData = Account::get($player);
				if($obj = $playerData->getFormObject()){
					$obj->receive($id, $data);
				}

				// なければクエストのほうにパケット分岐
				if($data === "null\n"){
					;
				}else{
					$data = (int) $data;
					if($id === 1000){
						QuestManager::addQuestsForm($player, $data+1);
					}else if($id > 1000 && $id < 1500){
						$qid = $id - 1000;
						$class = "Eard\Quests\Level$qid\Level$qid";
						$q = $class::getIndex()[$data];
						QuestManager::sendQuest($player, $q::QUESTID);
					}else if($id > 1500 && $id < 2000){
						if($packet->formData === "true\n"){
							$player->sendMessage(Chat::SystemToPlayer("クエストを開始しました"));
							$playerData->setNowQuest(Quest::get($id - 1500));
						}else{
							QuestManager::addQuestsForm($player, 0);
						}
					}else if($id === 2000 && $packet->formData === "true\n"){
						$playerData->resetQuest();
						$player->sendMessage(Chat::SystemToPlayer("クエストを取り消しました"));	
					}
				}
			break;
			case ProtocolInfo::SERVER_SETTINGS_REQUEST_PACKET:
				// echo "ghuee";
				new SettingsForm(Account::get($player));
			break;
		}
	}


	public function PacketSend(DataPacketSendEvent $e){
		$pk = $e->getPacket();
		$player = $e->getPlayer();
		switch($pk::NETWORK_ID){
			/*case ProtocolInfo::CONTAINER_SET_SLOT_PACKET;
					Note: Menuは、偽のインベントリで動いている。
					時々偽のインベントリチェックがはいるため、メニュー使用時はそれを無効にする。
				if(Account::get($player)->getMenu()->isActive()){
					$e->setCancelled(true);
				}
			break; エラー吐く*/
			# クラフトレシピ削除
			case ProtocolInfo::CRAFTING_DATA_PACKET:
				//$pk->clean();
				Recipe::packetFilter($pk, $player);
			break;
		}
	}


	public function I(PlayerInteractEvent $e){
		$player = $e->getPlayer();
		$playerData = Account::get($player);
		$block = $e->getBlock();
		$blockId = $block->getId();
		$blockMeta = $block->getDamage();
		$x = $block->x; $y = $block->y; $z = $block->z;

		// 長押し
		if($e->getAction() == 3 or $e->getAction() == 0){
			if($x && $y && $z){ // 空中でなければ
				if($e->getItem()->getId() == 0){
					if(Connection::getPlace()->isLivingArea()){
						new HelpForm($playerData);
					}
				}
				BlockObjectManager::startBreak($x, $y, $z, $player); // キャンセルとかはさせられないので、表示を出すだけ。
			}
		// 普通にタップ
		}else{

			/*	生活区域
			*/
			if(Connection::getPlace()->isLivingArea()){
				// できないばあい
				if(!AreaProtector::Use($playerData, $x, $y, $z, $blockId)){
					$e->setCancelled(true);
				// できるばあい
				}else{
					$r = BlockObjectManager::tap($block, $player);
					$e->setCancelled( $r );
				}

			/*	資源区域
			*/
			}else{
				if(!AreaProtector::canActivateInResource($blockId)){
					$placename = Connection::getPlace()->getName();
					$player->sendMessage(Chat::SystemToPlayer("§e{$placename}ではそのブロックの使用が制限されています。生活区域でしか使えません！"));
					$e->setCancelled(true);
				}else{
					$r = BlockObjectManager::tap($block, $player);
					$e->setCancelled( $r );
				}
			}


			$itemId = $e->getItem()->getId();
			switch($blockId){
				case 60: // こうち
					switch($itemId){
						case 295: // むぎのたね
						case 361: // かぼちゃ
						case 362: // すいか
						case 458: // ビートルート
						case 392: // じゃがいも
						case 391: // にんじん
							if(!$playerData->hasValidLicense(License::FARMER)){
								$player->sendMessage(Chat::SystemToPlayer("§e「農家」ライセンスがないので使用できません。"));
								$e->setCancelled(true);
							}
						break;
					}
				break;
				case 88: //ソウルサンド
					switch($itemId){
						case 372: // ネザーウォート
							if(!$playerData->hasValidLicense(License::FARMER)){
								$player->sendMessage(Chat::SystemToPlayer("§e「農家」ライセンスがないので使用できません。"));
								$e->setCancelled(true);
							}
						break;
					}
				break;
				case 61: // かまど
					if(!$playerData->hasValidLicense(License::REFINER)){
						$player->sendMessage(Chat::SystemToPlayer("§e「精錬」ライセンスがないので使用できません。"));
						$e->setCancelled(true);
					}
				break;
				case 130: // エンダーチェスト
					$inv = $playerData->getItemBox();
					$player->addWindow($inv);
					$e->setCancelled(true); // 実際のエンダーチェストの効果は使わせない
				break;
				case 116: // エンチャントテーブル(クエストカウンター)
					$nq = $playerData->getNowQuest();
					if($nq === null){
						QuestManager::addQuestsForm($player);
					}else{
						if($nq->getQuestType() === Quest::TYPE_DELIVERY){
							if($nq->checkDelivery($player)){
								$player->sendMessage(Chat::SystemToPlayer("クエストクリア！"));
								//ここで報酬を送り付ける
								$nq->sendReward($player);
								if($playerData->addClearQuest($nq->getQuestId())){
									$player->sendMessage(Chat::SystemToPlayer("初クリア！"));
								}
								$playerData->resetQuest();
							}else{
								QuestManager::sendCanselForm($player);
							}							
						}else{
							QuestManager::sendCanselForm($player);
						}


					}
					$e->setCancelled(true); // 実際のエンチャントテーブルの効果は使わせない
				break;
				default:
					// 手持ちアイテム
					switch($itemId){
						case 259: // うちがね
							if(!$playerData->hasValidLicense(License::DANGEROUS_ITEM_HANDLER)){
								$player->sendMessage(Chat::SystemToPlayer("§e「危険物取扱」ライセンスがないので使用できません。"));
								$e->setCancelled(true);
							}
						break;
						case 338: // さとうきび
							if(!$playerData->hasValidLicense(License::FARMER)){
								$player->sendMessage(Chat::SystemToPlayer("§e「農家」ライセンスがないので使用できません。"));
								$e->setCancelled(true);
							}
						break;
						case 416: // うまよろい
							new MenuForm($playerData);
						break;
					}
				break;
			} // endswitch

		}
	}


	public function Place(BlockPlaceEvent $e){
		$block = $e->getBlock();
		$player = $e->getPlayer();
		$x = $block->x; $y = $block->y; $z = $block->z;

		/*	生活区域
		*/
		if(Connection::getPlace()->isLivingArea()){
			if(AreaProtector::Edit($player, $x, $y, $z)){
				$r = BlockObjectManager::place($block, $player);
				$e->setCancelled( $r );
			}else{
				$e->setCancelled(true);
			}

		/*	資源区域
		*/
		}else{
			$item = $e->getItem();
			$itemId = $item->getId();
			if( !AreaProtector::canPlaceInResource($itemId) && $player->isSurvival()){
				$e->setCancelled(true);
			}else{
				$r = BlockObjectManager::place($block, $player);
				$e->setCancelled( $r );			
			}
		}
	}



	public function Break(BlockBreakEvent $e){
		$block = $e->getBlock();
		$player = $e->getPlayer();
		$level = $player->getLevel();
		$x = $block->x; $y = $block->y; $z = $block->z;

		/*	生活区域
		*/
		if(Connection::getPlace()->isLivingArea()){
			if(AreaProtector::Edit($player, $x, $y, $z)){
				$r = BlockObjectManager::break($block, $player);
				$e->setCancelled( $r );
			}else{
				$e->setCancelled(true);
			}

		/*	資源区域
		*/
		}else{
			// 女王バチがスポーン
			$id = $block->getId();
			$data = $block->getDamage();
			if($id === Block::EMERALD_ORE && $data === 1){
				EnemyRegister::summon($level, EnemyRegister::TYPE_JOOUBATI, $x+0.5, $y-4, $z+0.5);
			}

			// BlockObject壊す処理
			$r = BlockObjectManager::break($block, $player);
			$e->setCancelled( $r );
		}
	}


	public function Chat(PlayerChatEvent $e){
		ChatManager::chat($e->getPlayer(), $e);
	}



	public function Death(PlayerDeathEvent $e){
		$player = $e->getEntity();
		$playerData = Account::get($player);

		// 死んだときのメッセージ
		$cause = $player->getLastDamageCause();
		$name = $e->getPlayer()->getName();
		if($cause instanceof EntityDamageByEntityEvent){
			$en = $cause->getDamager();
			if($en instanceof Player){
				$killername = $en->getDisplayName();
			}elseif($en instanceof Humanoid){
				$killername = $en->getName();
			}else{
				$killername = "???";
			}
			$msg = Chat::System("§c{$name} は {$killername} に殺された");
		}else{
			switch($cause->getCause()){
			case EntityDamageEvent::CAUSE_PROJECTILE:
				if($cause instanceof EntityDamageByEntityEvent){
					$en = $cause->getDamager();
					if($en instanceof Player){
						$message = "{$name} は {$en->getName()} に殺された";
					}elseif($en instanceof Living){
						$message = "{$name} は串刺しにされた";
					}else{
						$message = "何かしらわからないけど爆発したくさい";
					}
				}
				break;
			case EntityDamageEvent::CAUSE_SUICIDE:
				$message = "{$name} は殺された";
				break;
			case EntityDamageEvent::CAUSE_VOID:
				$message = "{$name} な謎の空間へ落ちてしまった";
				break;
			case EntityDamageEvent::CAUSE_FALL:
				if($cause instanceof EntityDamageEvent){
					if($cause->getFinalDamage() > 2){
						$message = "{$name} は高所から落下死した";
						break;
					}
				}
				$message = "{$name} は落ちたっぽい";
				break;
			case EntityDamageEvent::CAUSE_SUFFOCATION:
				$message = "{$name} は埋まって死んだ";
				break;
			case EntityDamageEvent::CAUSE_LAVA:
				$message = "{$name} は溶岩にのまれた";
				break;
			case EntityDamageEvent::CAUSE_FIRE:
				$message = "{$name} は燃えて死んだ";
				break;
			case EntityDamageEvent::CAUSE_FIRE_TICK:
				$message = "{$name} は火だるまになった";
				break;
			case EntityDamageEvent::CAUSE_DROWNING:
				$message = "{$name} は溺死した";
				break;
			case EntityDamageEvent::CAUSE_CONTACT:
				if($cause instanceof EntityDamageByBlockEvent){
					if($cause->getDamager()->getId() === Block::CACTUS){
						$message = "{$name} はサボテンに刺されて死んだ";
					}
				}else{
					$message = "{$name} はサボテンに刺されて死んだ";
				}
				break;
			case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
			case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
				if($cause instanceof EntityDamageByEntityEvent){
					$en = $cause->getDamager();
					if($en instanceof Player){
						$message = "{$name} は爆発四散した";
						break;
					}
				}else{
					$message = "{$name} は爆発四散した";
				}
				break;
			case EntityDamageEvent::CAUSE_MAGIC:
				$message = "{$name} はなんか死んだ";
				break;
			case EntityDamageEvent::CAUSE_CUSTOM:
				$message = "{$name} はなんか死んだ";
				break;
			}
			$msg = $message ? Chat::System("§c{$message}") : Chat::System("§c???");
		}
		$e->setDeathMessage($msg);
	}



	public function Damaged(EntityDamageEvent $e){
		if($e instanceof EntityDamageByEntityEvent){
			$damager = $e->getDamager();//ダメージを与えた人
			$victim = $e->getEntity();//喰らった人

			// プレイヤーに対しての攻撃の場合、キャンセル
			if($victim instanceof Player && $damager instanceof Player){
				$victimData = Account::get($victim);
				if(!$victimData->getAttackSetting()){
					$damager->sendMessage(Chat::SystemToPlayer("§c警告: 殴れません"));
					MainLogger::getLogger()->info(Chat::System($damager->getName(), "§c警告: 殴れません"));
					$e->setCancelled(true);
				}
			}

			if($damager instanceof Humanoid && method_exists($damager, 'attackTo')){
				$damager->attackTo($e);
			}

			if($victim instanceof NPC && $damager instanceof Player){
				/*
				$message = "やぁ!オイラは".$victim->getNameTag()."っていうんだ! よろしくな！";
				$damager->sendMessage(Chat::Format($victim->getNameTag(), "§6個人(".$damager->getDisplayName().")", $message));
				MainLogger::getLogger()->info(Chat::Format($victim->getNameTag(), "§6個人(".$damager->getDisplayName().")", $message));
				*/
				$victim->Tap($damager);
				$e->setCancelled(true);
			}

		}
		return true;
	}


/*
	public function D(DataPacketReceiveEvent $e){
		$pk = $e->getPacket();
		if($pk instanceof ModalFormResponsePacket){
			$playerData = Account::get($e->getPlayer());
			if($obj = $playerData->getFormObject()){
				$obj->Receive($pk->formId, $pk->data);
			}
		}
	}
*/

/*
	// for debug
	public function PacketSend(DataPacketSendEvent $e){
		$pk = $e->getPacket();
		$player = $e->getPlayer();
		$name = $player->getName();
		switch($pk::NETWORK_ID){
			case ProtocolInfo::INVENTORY_ACTION_PACKET;
				echo "InventoryAction\n";
			break;
			case ProtocolInfo::CONTAINER_OPEN_PACKET;
				echo "ContainerOpen\n";
				//echo " {$pk->windowid} {$pk->type} {$pk->x} {$pk->y} {$pk->z} {$pk->entityId}\n \n";
			break;
			case ProtocolInfo::CONTAINER_CLOSE_PACKET;
				echo "ContainerClose\n";
			break;
			case ProtocolInfo::CONTAINER_SET_SLOT_PACKET;
				echo "ContainerSetSlot\n";
			break;
			case ProtocolInfo::CONTAINER_SET_DATA_PACKET;
				echo "ContainerSetData\n";
			break;
			case ProtocolInfo::CONTAINER_SET_CONTENT_PACKET;
				echo "ContainerSetContent\n";
				echo $pk->windowid." ";
				echo $pk->targetEid." ";

				if($pk->windowid === 0){
					echo "slots:\n";
					print_r($pk->slots); echo " ";
					echo "hotbar:\n";
					print_r($pk->hotbar); echo " ";					
				}
				echo "\n";

			break;
			case ProtocolInfo::CONTAINER_SET_DATA_PACKET;
				echo "ContainerSetData\n";
			break;
			case ProtocolInfo::MOB_EQUIPMENT_PACKET:
				echo "MobEquip\n";
			break;
		}
	}
*/


/*
	// for debug
	public function D(DataPacketReceiveEvent $e){
		$packet = $e->getPacket();
		$player = $e->getPlayer();
		$name = $player->getName();
		switch($packet::NETWORK_ID){
			case ProtocolInfo::PLAYER_ACTION_PACKET:
				//echo "ACTION : ",$packet->x,$packet->y,$packet->z, " ", $packet->action, " ", $packet->face,"\n";
				switch($packet->action){
					case PlayerActionPacket::ACTION_START_BREAK:
						//gamemodeを2にして強制的にこわせなくしてやろうとおもったがやめた
						//gamemode 0 : くる xyzあり
						//gamemode 2 : こない
						$x = $packet->x; $y = $packet->y; $z = $packet->z;
						if(!AreaProtector::$allowBreakAnywhere and !AreaProtector::Edit($player, $x, $y, $z)){
							//$player->setGamemode(2);
						}
					break;
					case PlayerActionPacket::ACTION_ABORT_BREAK:
						//gamemode2の時は、こちらのabortしかでず、startbreaakがおくられてこないため、xyzの情報が入っていない
						//gamemode 0 : くる
						//gamemode 2 : くる
					break;
					default:
					break;
				}
			break;
			case ProtocolInfo::INTERACT_PACKET:
				//echo "INTERACT : ",$packet->x,$packet->y,$packet->z, " ", $packet->action, " ", $packet->face,"\n";
				switch($packet->action){
					default:

					break;
				}	
			break;
			case ProtocolInfo::USE_ITEM_PACKET:
				//echo "USE ITEM: ",$packet->x,$packet->y,$packet->z, "  ", $packet->face,"\n";
				if($packet->face >= 0 and $packet->face <= 5){
					//ブロックの設置破壊
					$x = $packet->x; $y = $packet->y; $z = $packet->z;
					if(!AreaProtector::$allowBreakAnywhere and !AreaProtector::Edit($player, $x, $y, $z)){
						$player->setGamemode(2);
					}else{
						$player->setGamemode(0);
					}
				}
			break;
			case ProtocolInfo::MOVE_PLAYER_PACKET:
			break;
		}
	}
*/
}

class Delay extends Task{

	public function __construct($player, $func){
		$this->player = $player;
		$this->func = $func;
	}

	public function onRun($tick){
		$func = $this->func;
		$func($this->player);
	}
}