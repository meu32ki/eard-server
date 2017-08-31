<?php
namespace Eard\Event;


# Basic
use pocketmine\Player;
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

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

# NetWork
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\PlayerActionPacket;

# Eard
use Eard\Main;
use Eard\DBCommunication\Connection;
use Eard\Event\AreaProtector;
use Eard\Event\ChatManager;
use Eard\Event\BlockObject\BlockObjectManager;
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\Menu;
use Eard\Utils\Chat;
use Eard\MeuHandler\Account\License\Recipe;

# Enemy
use Eard\Enemys\EnemyRegister;
use Eard\Enemys\Humanoid;


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
		$playerData->initMenu();
		$playerData->initItemBox();
		$playerData->onLoadTime();

		#権限関係
		$main = Main::getInstance();
		$perms = [
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

		// 資源に来た時に携帯配布
		if(Connection::getPlace()->isResourceArea()){
			$inv = $player->getInventory();
			$inv->addItem(Item::get(416));
		}
	}



	public function Q(PlayerQuitEvent $e){
		$player = $e->getPlayer();
		if($player->spawned){ //whitelistでひっかかっていなかったら

			// Menuが開いていたら閉じる処理
			$playerData = Account::get($player);
			if($playerData->getMenu()->isActive()){
				$playerData->getMenu()->close();
			}

			//　オンラインテーブルから記録消す
			Connection::getPlace()->recordLogout($player->getName());

			// 退出時メッセージ
			$msg = $playerData->isNowTransfering() ? Chat::getTransferMessage($player->getDisplayName()) : Chat::getQuitMessage($player->getDisplayName());
			$e->setQuitMessage($msg);

			$playerData->onUpdateTime();
			$playerData->updateData(true);//quitの最後に持ってくること。他の処理をこの後に入れない。セーブされないデータが出てきてしまうかもしれないから。
		}
	}



	public function PacketReceive(DataPacketReceiveEvent $e){
		$packet = $e->getPacket();
		$player = $e->getPlayer();
		$name = $player->getName();
		switch($packet::NETWORK_ID){
			case ProtocolInfo::USE_ITEM_PACKET:
				// Menu::染料タップ
				$itemId = $packet->item->getId();
				if($itemId === Menu::$selectItem || $itemId === Menu::$menuItem){
					$playerData = Account::get($player);
					if($itemId === Menu::$selectItem){
						$playerData->getMenu()->useSelect($packet->item->getDamage());
					}else{
						$playerData->getMenu()->useMenu();
					}
				}
			break;
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
		}
	}


	public function PacketSend(DataPacketSendEvent $e){
		$pk = $e->getPacket();
		$player = $e->getPlayer();
		switch($pk::NETWORK_ID){
			case ProtocolInfo::CONTAINER_SET_SLOT_PACKET;
				/*
					Note: Menuは、偽のインベントリで動いている。
					時々偽のインベントリチェックがはいるため、メニュー使用時はそれを無効にする。
				*/
				if(Account::get($player)->getMenu()->isActive()){
					$e->setCancelled(true);
				}
			break;
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

		switch($blockId){
			case 130: // エンダーチェスト
				//if(Connection::getPlace()->isLivingArea()){
					$inv = $playerData->getItemBox();
					$player->addWindow($inv);
				//}
				$e->setCancelled(true); // 実際のエンダーチェストの効果は使わせない
			break;
/*			case 218: // シュルカーボックス
				$inv = $player->getInventory();
				$inv->addItem(Item::get(416));
				$player->sendMessage(Chat::SystemToPlayer("「携帯」を送りました。ベータテスト中限定だよ～。"));
			break;*/
			default: // それいがい
				$x = $block->x; $y = $block->y; $z = $block->z;

				// 長押し
				if($e->getAction() == 3 or $e->getAction() == 0){
					if($x && $y && $z){ // 空中でなければ
						BlockObjectManager::startBreak($x, $y, $z, $player); // キャンセルとかはさせられないので、表示を出すだけ。
					}

				// 普通にタップ
				}else{

					/*	生活区域
					*/
					if(Connection::getPlace()->isLivingArea()){
						// editができるか？
						// できないばあい
						if(!AreaProtector::Edit($player, $x, $y, $z, true)){
							if(!AreaProtector::canActivateInLivingProtected($blockId)){
								$e->setCancelled(true);
							}else{
								$r = BlockObjectManager::tap($block, $player);
								$e->setCancelled( $r );
							}

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
				}
			break;
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
			if( !AreaProtector::canPlaceInResource($itemId) ){
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
		
		// メニューを見ていたら、消す
		if($playerData->getMenu()->isActive()){
			$playerData->getMenu()->close();
		}

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
				$damager->sendMessage(Chat::SystemToPlayer("§c警告: 殴れません"));
				MainLogger::getLogger()->info(Chat::System($damager->getName(), "§c警告: 殴れません"));
				$e->setCancelled(true);
			}

			if($damager instanceof Humanoid && method_exists($damager, 'attackTo')){
				$damager->attackTo($e);
			}
		}
		return true;
	}


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