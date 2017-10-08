<?php

namespace Eard\Enemys;

use Eard\Main;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\networks\protocol\AddEntityPacket;
use pocketmine\networks\protocol\MobArmorEquipmentPacket;
use pocketmine\networks\protocol\AnimatePacket;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\level\Explosion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\format\FullChunk;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\SpellParticle;
use pocketmine\level\particle\TerrainParticle;
use pocketmine\level\particle\RedstoneParticle;

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
use pocketmine\block\Block;

use pocketmine\math\Vector3;
class Deffenian extends Humanoid implements Enemy{

	//名前を取得
	public static function getEnemyName(){
		return "のなめ";
	}

	//エネミー識別番号を取得
	public static function getEnemyType(){
		return EnemyRegister::TYPE_DEFFENIAN;
	}

	//最大HPを取得
	public static function getHP(){
		return 100;
	}

	//ドロップするアイテムIDの配列を取得 [[ID, data, amount, percent], [ID, data, amount, percent], ...]
	public static function getAllDrops(){
		//火薬 骨粉 種(スイカ/かぼちゃ/麦になるやつ)
		//5%の確率で鉄インゴットも
/*
		return [
			[Item::GUNPOWDER, 0, 1, 70],
			[Item::DYE, 15, 1, 40],//骨粉
			[Item::PUMPKIN_SEEDS, 0, 1, 20],
			[Item::MELON_SEEDS, 0, 1, 20],
			[Item::WHEAT_SEEDS, 0, 1, 20],
			[Item::IRON_INGOT , 0, 1, 5],
		];
		*/
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
					[Item::GUNPOWDER, 0, 1],
				],
			]
		];
	}

	public static function getMVPTable(){
		return [100, 1,
			[
				[Item::IRON_INGOT, 0, 1]
			]
		];
	}

	//召喚時のポータルのサイズを取得
	public static function getSize(){
		return 2.5;
	}

	//召喚時ポータルアニメーションタイプを取得
	public static function getAnimationType(){
		return EnemySpawn::TYPE_COMMON;
	}

	//召喚時のポータルアニメーションの中心座標を取得
	public static function getCentralPosition(){
		return new Vector3(0, 0.7, 0);
	}

	//スポーンするバイオームの配列　[ID => true, ...]
	public static function getBiomes() : array{
		//コピペ用全種類を置いておく
		return [
			//雨なし
			Biome::HELL => true, 
			Biome::END => true,
			Biome::DESERT => true,
			Biome::DESERT_HILLS => true,
			Biome::MESA => true,
			Biome::MESA_PLATEAU_F => true,
			Biome::MESA_PLATEAU => true,
			//雨あり
			Biome::OCEAN => true,
			Biome::PLAINS => true,
			Biome::MOUNTAINS => true,
			Biome::FOREST => true,
			Biome::TAIGA => true,
			Biome::SWAMP => true,
			Biome::RIVER => true,
			Biome::ICE_PLAINS => true,
			Biome::SMALL_MOUNTAINS => true,
			Biome::BIRCH_FOREST => true,
		];
	}

	//スポーンする頻度を返す(大きいほどスポーンしにくい)
	public static function getSpawnRate() : int{
		return 5;
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
				new StringTag("geometryData", EnemyRegister::loadModelData('Golem')),
				new StringTag("geometryName", 'skin.Redstone.golem'),
				new StringTag("capeData", ''),
				new StringTag("Data", EnemyRegister::loadSkinData('Deffenian')),
				new StringTag("Name", 'Redstone_Redstone_Golem')
			]),
		]);
		$custom_name = self::getEnemyName();
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}
		$entity = new Deffenian($level, $nbt);
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
		$this->beamYaw = 0;
		$this->mode = 0;
	}

	public function onUpdate(int $tick): bool{
		if($this->getHealth() > 0 && AI::getRate($this)){
			$this->timings->startTiming();
			if(!$this->target) $this->target = AI::searchTarget($this, 900);
			if($this->target && ($disq = $this->distanceSquared($this->target)) <= 750){
				//AI::ElementBurstBomb($this, Magic::POISON, 14, 3);
				switch($this->charge){
					case 0:
						AI::setRate($this, 20);
						AI::lookAt($this, $this->target);
						$this->walk = true;
						$this->walkSpeed = -0.025;
						$this->float = -1;
						$this->charge = 1;
						$this->beamYaw = 0;
					break;
					case 1:
						AI::setRate($this, 50);
						AI::lookAt($this, $this->target);
						$this->walk = false;
						$this->walkSpeed = 0.01;
						$this->yaw += 75;
						$this->charge = 2;
					break;
					case 2:
						AI::setRate($this, 30);
						AI::lookAt($this, $this->target);
						$this->walk = true;
						$this->walkSpeed = 0.2;
						$this->float = mt_rand(0, 1);
						$this->charge = 0;
					break;
				}
			}else if($this->target && ($disq = $this->distanceSquared($this->target)) < 1000){
				AI::setRate($this, 9);
				AI::lookAt($this, $this->target);
				$this->walk = true;
				$this->float = mt_rand(0, 1);
			}else{
				AI::setRate($this, 25);
				$this->target = false;
				$this->yaw += mt_rand(-40, 40);
				$this->walk = true;
				$this->walkSpeed = 0.2;
				$this->float = mt_rand(0, 1);
				$this->pitch = 0;
				$this->charge = 0;		
			}
		}else if($this->getHealth() > 0){
			switch($this->charge){
				case 0:
					;
				break;
				case 1:
					//$this->level->addSound(new GhastSound($this, 2));
					$p = AI::getFrontVector($this, true)->multiply(2)->add($this);
					$this->level->addParticle(new SpellParticle($this, 41, 234, 229));
				break;
				case 2:
					//$p = AI::getFrontVector($this, true)->multiply(2)->add($this);
					AI::chargerShot($this, 35, new RedstoneParticle($this, 1), new SpellParticle($this, 41, 234, 229), 11, 0, 1.6, false);
					$this->yaw -= $this->beamYaw;
					$this->beamYaw += 0.35;
				break;
			}
		}

		if($this->walk){
			$can = AI::walkFront($this, $this->walkSpeed);
			if(!$can){
				$this->yaw = mt_rand(1, 360);
			}
		}
		//AI::walkFront($this, 0.08);
		return parent::onUpdate($tick);
	}

	public function attack(EntityDamageEvent $source){
		$damage = $source->getDamage();// 20170928 src変更による書き換え
		parent::attack($source);
	}
	
	public function getName() : string{
		return self::getEnemyName();
	}
}