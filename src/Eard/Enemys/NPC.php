<?php
namespace Eard\Enemys;


# Basic
use pocketmine\level\Level;
use pocketmine\entity\Human;
use pocketmine\entity\Entity;
use pocketmine\Player;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

# Eard
use Eard\Event\BlockObject\ChatInput;


/***
*
*	NPCの設置だけできるやつ
*/
class NPC extends Human implements ChatInput {

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
				new StringTag("geometryData", EnemyRegister::loadModelData('humanoid')),
				new StringTag("geometryName", 'geometry.humanoid'),
				new StringTag("capeData", ''),
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

	public function Tap(Player $player){
		// ここで条件分岐
		// とりあえず実装 20170908
		if(true){
			$this->singleTap($player);
		}else{
			$this->doubleTap($player);
		}

	}

	// NPC会話フレームワーク
	public function singleTap(){

	}

	// NPC会話フレームワーク
	public function doubleTap(){

	}

	// @ChatInput
	public function Chat(Player $player, String $txt){

	}
}
