<?php
namespace Eard\Account;

# Basic
use pocketmine\Player;

# Eard
use Eard\ChestIO;


class ItemBox extends ChestIO {
	
	/*
		継承
		Inventory
		↓
		BaseInventory      
		↓                  ↓
		ContainerInventory　ChestIO
		↓　　　　　　　　　　　　　　　　↓     ↓
		Chestinventory ItemBox　Shopのなかでつかう
	*/
	
	public function __construct($playerData){
		$player = $playerData->getPlayer();
		parent::__construct($player);

		// インヴェントリの中身をいれる
		$this->setItemArray($playerData->getItemArray());

		// 後のために記録
		$this->playerData = $playerData;
	}

	public function getName(){
		return $this->playerData->getPlayer()->getName()."専用 アイテムボックス";
	}
}