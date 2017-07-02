<?php

namespace Eard\Enemys;

use Eard\Enemy;
use Eard\Main;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\level\Explosion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\format\FullChunk;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteTag;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;

use pocketmine\math\Vector3;
class Dummy extends Humanoid implements Enemy{

	//名前を取得
	public static function getEnemyName(){
		return "ダミーエネミー";
	}

	//エネミー識別番号を取得
	public static function getEnemyType(){
		return EnemyRegister::TYPE_DUMMY;
	}

	//最大HPを取得
	public static function getHP(){
		return 40;
	}

	//ドロップするアイテムIDの配列を取得 [[ID, data, amount, percent], [ID, data, amount, percent], ...]
	public static function getAllDrops(){
		//火薬 骨粉 種(スイカ/かぼちゃ/麦になるやつ)
		//5%の確率で鉄インゴットも
		return [
			[Item::GUNPOWDER, 0, 1, 70],
			[Item::DYE, 15, 1, 40],//骨粉
			[Item::PUMPKIN_SEEDS, 0, 1, 20],
			[Item::MELON_SEEDS, 0, 1, 20],
			[Item::WHEAT_SEEDS, 0, 1, 20],
			[Item::IRON_INGOT , 0, 1, 5],
		];
	}

	public static function summon($level, $x, $y, $z){
		$nbt = new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $x + 0.5),
				new DoubleTag("", $y),
				new DoubleTag("", $z + 0.5)
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
				new StringTag("Data", Main::loadSkinData('moyasan083')),
				new StringTag("Name", 'GreekMythology_GreekMythologySiren')
			]),
		]);
		$custom_name = self::getEnemyName();
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}
		$entity = new Dummy($level, $nbt);
		$entity->setMaxHealth(self::getHP());
		$entity->setHealth(self::getHP());
		AI::setSize($entity, 2);
		if($entity instanceof Entity){
			$entity->spawnToAll();
			return $entity;
		}
		echo $custom_name." is Not Entity\n";
		return false;
	}

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->cooltime = 0;
		$this->target = false;
		$this->charge = 0;
		$this->mode = 0;
		/*$item = Item::get(267);
		$this->getInventory()->setItemInHand($item);*/
	}

	public function onUpdate($tick){
		if(AI::getRate($this)){
			$this->yaw += mt_rand(-60, 60);
			AI::setRate($this, 40);
		}
		AI::walkFront($this);
		parent::onUpdate($tick);
	}

	public function getName(){
		return self::getEnemyName();
	}
}