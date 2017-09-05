<?php
namespace Eard\Enemys;

use pocketmine\entity\Human;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\entity\Entity;

/***
*
*	NPCの設置だけできるやつ
*/
class NPC extends Human{

	public static function summon($level, $x, $y, $z, $skinData, $skinId, $custom_name = null){
		$nbt = new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $x),
				new DoubleTag("", $y),
				new DoubleTag("", $z)
			]),
			"Motion" => new ListTag("Motion", [
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
			]),
			"Rotation" => new ListTag("Rotation", [
				new FloatTag("", lcg_value() * 360),
				new FloatTag("", 0)
			]),
			"Skin" => new CompoundTag("Skin", [
				new StringTag("Data", $skinData),
				new StringTag("Name", $skinId)
			]),
		]);
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}
		$entity = new NPC($level, $nbt);

		if($entity instanceof Entity){
			$entity->spawnToAll();
			return $entity;
		}
		echo $custom_name." is not Entity\n";
		return false;
	}

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_CAN_SHOW_NAMETAG, true);
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG, true);
	}
}
