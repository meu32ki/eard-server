<?php
namespace Eard;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\inventory\InventoryType;
use pocketmine\inventory\BaseInventory;

use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;

use pocketmine\nbt\NBT;
use pocketmine\network\protocol\BlockEntityDataPacket;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;


class ItemBox extends BaseInventory {
	
	/*
		継承
		Inventory
		↓
		BaseInventory
		↓
		ContainerInventory
		↓　　　　　　　↓
		Chestinventory ItemBox
	*/

	/**
	*	@param Int $maxStack 最大値
	*	@param Array $itemArray [ [$id, $meta,$stack], [$id,$meta,$stack]...]
	*/
	
	public function __construct($playerData){
		$player = $playerData->getPlayer();
		$itemArray = $playerData->getItemArray();
		//$maxStack = count($itemArray) === 1 ? 10 : count($itemArray);
		$maxStack = 27;

		// $itemArray を Items[]にへんかん
		$items  = [];
		for($i = 0; $i < $maxStack; ++$i){
			$d = isset($itemArray[$i]) ? $itemArray[$i] : [0,0,0];
			$id = $d[0];
			$meta = $d[1];
			$stack = $d[2];
			$items[] = Item::get($id, $meta, $stack);
		}
		//echo "itembox: const;\n";

		$inventoryType = InventoryType::get(InventoryType::CHEST);
		parent::__construct($player, $inventoryType, $items, $maxStack, "Item Box"); //BaseInventoryにメソッド
		//Holderに当たる部分は、InventoryHolderをimplementsしてるclassである必要がある→playerでおｋ

		$this->playerData = $playerData;
		
	}


	public function write(){
		$itemArray = [];
		foreach($this->getContents() as $item){
			$itemArray[] = [$item->getId(), $item->getDamage(), $item->getCount()];
		}
		$this->playerData->setItemArray($itemArray);
	}

	/**
	*	AddWindowすると、Openを経由して実行される
	*/
	public function onOpen(Player $who){

		// まずはダミーのチェスト置く座標作る
		$x = round($who->getX());
		$y = round($who->getY()) + 3;
		$z = round($who->getZ());
		$id = 54;
		$meta = 0;

		// ダミーチェスト送る
		$pk = new UpdateBlockPacket();
		$pk->x = $x;
		$pk->y = $y;
		$pk->z = $z;
		$pk->blockId = $id;
		$pk->blockData = $meta;
		$pk->flags = UpdateBlockPacket::FLAG_NONE;//読み込まれていないチャンクに送り付ける時は注意が必要
		$who->dataPacket($pk);

		// ダミーチェスト送ったと記録
		$blocks = [[$x, $y, $z, $id, $meta]];
		$this->playerData->setSentBlock($blocks);

		// NBT送る(チェスト開けたときのインベントリ名変更)　from pocketmine\tile\spawnable
		$str = $who->getName()."専用 アイテムボックス";
		$nbt = new NBT(NBT::LITTLE_ENDIAN);
		$c = new CompoundTag("", [
			new StringTag("id", "Chest"),
			new IntTag("x", $x),
			new IntTag("y", $y),
			new IntTag("z", $z),
			new StringTag("CustomName", $str)
		]);
		$nbt->setData($c);
		$pk = new BlockEntityDataPacket();
		$pk->x = $x;
		$pk->y = $y;
		$pk->z = $z;
		$pk->namedtag = $nbt->write(true);
		$who->dataPacket($pk);

		// コンテナあける
		parent::onOpen($who);
		$pk = new ContainerOpenPacket();
		$pk->windowid = $who->getWindowId($this);
		$pk->type = $this->getType()->getNetworkType();
		$pk->x = $x;
		$pk->y = $y;
		$pk->z = $z;
		$who->dataPacket($pk);
		//echo "ItemBox: open {$pk->windowid}\n";

		// ContainerSetContentPacket 入ってるアイテムを送る
		$this->sendContents($who);

		/*
			$this->getHolderは、ブロックの座標からブロックを特定するのにつかわれている(?)
			ダミーのチェストを送らねばならないのは、MCPE側での処理のため
		*/
	}


	public function onClose(Player $who){
		// はいってるアイテムをセーブする
		$this->write();

		// コンテナ閉じる
		$pk = new ContainerClosePacket();
		$pk->windowid = $who->getWindowId($this);
		$who->dataPacket($pk);
		parent::onClose($who);
		//echo "ItemBox: close {$pk->windowid}\n";

		// 記録からダミーチェストけす
		$level = $who->getLevel();
		$old = $this->playerData->getSentBlock();
		foreach($old as $d){
			$pk = new UpdateBlockPacket();
			$pk->x = (int) $d[0];
			$pk->z = (int) $d[2];
			$pk->y = (int) $d[1];
			$pk->blockId = $level->getBlockIdAt($d[0], $d[1], $d[2]);
			$pk->blockData = $level->getBlockDataAt($d[0], $d[1], $d[2]);
			$who->dataPacket($pk);
		}


	}

}