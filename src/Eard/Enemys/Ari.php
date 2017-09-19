<?php

namespace Eard\Enemys;

use Eard\Main;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\AnimatePacket;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\level\Explosion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\format\FullChunk;
use pocketmine\level\generator\biome\Biome;

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
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

use pocketmine\math\Vector3;
class Ari extends Humanoid implements Enemy{
	public static $ground = false;
	public $rainDamage = false;

	//名前を取得
	public static function getEnemyName(){
		return "ミドリアリ";
	}

	//エネミー識別番号を取得
	public static function getEnemyType(){
		return EnemyRegister::TYPE_ARI;
	}

	//最大HPを取得
	public static function getHP(){
		return 10;
	}

	//ドロップするアイテムIDの配列を取得 [[ID, data, amount, percent], [ID, data, amount, percent], ...]
	public static function getAllDrops(){
		//火薬 骨粉 種(スイカ/かぼちゃ/麦になるやつ)
		//5%の確率で鉄インゴットも
		/*return [
			[Item::GUNPOWDER, 0, 1, 70],
			[Item::DYE, 15, 1, 40],//骨粉
			[Item::PUMPKIN_SEEDS, 0, 1, 20],
			[Item::MELON_SEEDS, 0, 1, 20],
			[Item::WHEAT_SEEDS, 0, 1, 20],
			[Item::GOLD_INGOT , 0, 1, 5],
		];*/
		return [
			/*
			[テーブルのドロップ率, ドロップ判定回数,
				[
					[アイテムID, データ値, 個数],
					[...]
				],
			],
			*/
			[100, 1,
				[
					[Item::NETHER_WART, 0, 1],
				],
			],
			[100, 2,
				[
					[Item::LOG, 0, 1],
					[Item::QUARTZ, 0, 1],
				],
			],
			[40, 2,
				[
					[Item::IRON_NUGGET, 0, 2],
					[Item::DYE, 2, 1],//サボテンの染料
				],
			],
			[3, 1,
				[
					[Item::GOLD_NUGGET , 0, 1],
				],
			],
			[1, 1,
				[
					[Item::EMERALD , 0, 1],
				],
			],
		];
	}

	public static function getMVPTable(){
		return [100, 1,
			[
				[Item::IRON_NUGGET, 0, 1],
				[Item::LOG, 0, 1],
				[Item::NETHER_WART, 0, 1],
			]
		];
	}

	//召喚時のポータルのサイズを取得
	public static function getSize(){
		return 0.5;
	}

	//召喚時ポータルアニメーションタイプを取得
	public static function getAnimationType(){
		return EnemySpawn::TYPE_COMMON;
	}

	//召喚時のポータルアニメーションの中心座標を取得
	public static function getCentralPosition(){
		return new Vector3(0, 0.7, 0);
	}

	public static function getBiomes() : array{
		return [
			//雨なし
			//Biome::HELL => true, 
			//Biome::END => true,
			Biome::DESERT => true,
			//Biome::DESERT_HILLS => true,
			//Biome::MESA => true,
			//Biome::MESA_PLATEAU_F => true,
			//Biome::MESA_PLATEAU => true,
			//雨あり
			//Biome::OCEAN => true,
			Biome::PLAINS => true,
			//Biome::MOUNTAINS => true,
			//Biome::FOREST => true,
			//Biome::TAIGA => true,
			//Biome::SWAMP => true,
			//Biome::RIVER => true,
			Biome::ICE_PLAINS => true,
			//Biome::SMALL_MOUNTAINS => true,
			//Biome::BIRCH_FOREST => true,
		];
	}

	public static function getSpawnRate() : int{
		return 3;
	}

	public static function summon($level, $x, $y, $z){
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
				new StringTag("Data", EnemyRegister::loadSkinData('Ari')),
				new StringTag("Name", 'MagicTheGathering_MagicTheGatheringNicolBolas')
			]),
		]);
		$custom_name = self::getEnemyName();
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}
		$entity = new Ari($level, $nbt);
		$entity->setMaxHealth(self::getHP());
		$entity->setHealth(self::getHP());
		AI::setSize($entity, self::getSize());
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
		$this->pitch = 90;
		$this->setSneaking(1);
		/*$item = Item::get(267);
		$this->getInventory()->setItemInHand($item);*/
	}

	public function onUpdate($tick){
		if($this->getHealth() > 0 && AI::getRate($this)){
			if($this->target = AI::searchTarget($this)){
				AI::lookAt($this, $this->target);
				$this->pitch += 90;
				AI::rangeAttack($this, 1.8, 2);
				AI::setRate($this, 12);
			}else{
				$this->yaw += mt_rand(-60, 60);
				AI::setRate($this, 40);
			}
		}
		$can = AI::walkFront($this, 0.15);
		if(!$can){
			AI::jump($this);
		}
		parent::onUpdate($tick);
	}

	public function attackTo(EntityDamageEvent $source){
		$victim = $source->getEntity();
		$source->setKnockBack(0.2);
		if(!$victim->isSneaking()) $source->setKnockBack(0.4);
		$pk = new AnimatePacket();
		$pk->eid = $this->getId();
		$pk->action = 1;//ArmSwing
		Server::getInstance()->broadcastPacket($this->getViewers(), $pk);
	}

	public function attack($damage, EntityDamageEvent $source){
		parent::attack($damage, $source);
	}
	
	public function getName(){
		return self::getEnemyName();
	}
}