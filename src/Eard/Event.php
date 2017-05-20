<?php

namespace Eard;

# Basic
//use pocketmine\Player;

# Event
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

# NetWork
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\PlayerActionPacket;

# Item
use pocketmine\item\Item;

# Muni
use Eard\AreaProtector;
use Eard\Settings;
use Eard\Menu;

class Event implements Listener{

	public function C(PlayerLoginEvent $e){
		$player = $e->getPlayer();
		$playerData = Account::get($player);

		//playerData関連の準備
		$playerData->setPlayer($player);//	touitusuruna 
		$playerData->loadData(true);
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
/*
		if(!$player->getInventory()->contains($item = Menu::getMenuItem())){
			$player->getInventory()->addItem($item);
		}
*/
	}

	public function Q(PlayerQuitEvent $e){
		if($e->getPlayer()->spawned){ //すぽーんしていたら→whtelistでひっかかっていなかったら
			$playerData = Account::get($e->getPlayer());
			if($playerData->getMenu()->isActive()){
				$playerData->getMenu()->close();
			}
			$playerData->onUpdateTime();
			$playerData->updateData(true);//最後
		}
	}

	public function I(PlayerInteractEvent $e){
		$item = $e->getItem();
		$id = $item->getId();
		$player = $e->getPlayer();
		$playerData = Account::get($player);
		switch($id){
			case Menu::$menuItem:
				$playerData->getMenu()->useMenu($e);
			break;
			case Item::BOOK: //dev用
				Account::get($player)->dumpData();
			break;
		}
	}

	public function IE(PlayerItemHeldEvent $e){
		$player = $e->getPlayer();
		$playerData = Account::get($player);
		$id = $e->getItem()->getId();
		switch($id){
			case Menu::$selectItem:
				$playerData->getMenu()->useSelect($e->getItem()->getDamage());
				break;
			case Menu::$menuItem:
				$playerData->getMenu()->useMenu($e);
				break;
		}
	}

	public function D(DataPacketReceiveEvent $e){
		$packet = $e->getPacket();
		$player = $e->getPlayer();
		$name = $player->getName();
		switch($packet::NETWORK_ID){
			case ProtocolInfo::PLAYER_ACTION_PACKET:
				//壊し始めたとき
				if($packet->action === PlayerActionPacket::ACTION_START_BREAK){
					$x = $packet->x; $y = $packet->y; $z = $packet->z;
					if(!Settings::$allowBreakAnywhere and !AreaProtector::Edit($player, $x, $y, $z)){

					}					
				}
			break;
		}
	}

	public function BP(BlockPlaceEvent $e){
		$block = $e->getBlock();
		$player = $e->getPlayer();
		$x = $block->x; $y = $block->y; $z = $block->z;
		if(!Settings::$allowBreakAnywhere and !AreaProtector::Edit($player, $x, $y, $z)){
			$e->setCancelled(true);
		}
	}
	public function BB(BlockBreakEvent $e){
		$block = $e->getBlock();
		$player = $e->getPlayer();
		$x = $block->x; $y = $block->y; $z = $block->z;
		if(!Settings::$allowBreakAnywhere and !AreaProtector::Edit($player, $x, $y, $z)){
			$e->setCancelled(true);
		}
	}

/*
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
						if(!Settings::$allowBreakAnywhere and !AreaProtector::Edit($player, $x, $y, $z)){
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
					if(!Settings::$allowBreakAnywhere and !AreaProtector::Edit($player, $x, $y, $z)){
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