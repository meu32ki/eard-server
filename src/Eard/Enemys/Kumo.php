<?php

namespace Eard\Enemys;

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
use pocketmine\level\generator\normal\eardbiome\Biome;
use pocketmine\level\particle\TerrainParticle;
use pocketmine\level\particle\SpellParticle;

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
class Kumo extends Humanoid implements Enemy{
	protected $gravity = 0.03;
	public static $ground = false;
	public $rainDamage = false;

	//名前を取得
	public static function getEnemyName(){
		return "クモ";
	}

	//エネミー識別番号を取得
	public static function getEnemyType(){
		return EnemyRegister::TYPE_KUMO;
	}

	//最大HPを取得
	public static function getHP(){
		return 30;
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
					[Item::SPIDER_EYE, 0, 1],
				],
			],
			[100, 2,
				[
					[Item::COBWEB, 0, 1],
					[Item::LEATHER, 0, 1],
				],
			],
			[75, 2,
				[
					[Item::FEATHER, 0, 1],
					[Item::LEATHER, 0, 1],
					[Item::IRON_NUGGET, 0, 1],
					[Item::IRON_NUGGET, 0, 2],
				],
			],
			[60, 1,
				[
					[Item::ENDER_PEARL, 0, 1],
				],
			],
			[25, 1,
				[
					[Item::IRON_NUGGET, 0, 3],
					[Item::ENDER_PEARL, 0, 1],
				],
			],
			[15, 1,
				[
					[Item::IRON_INGOT, 0, 1],
				],
			],
			[10, 1,
				[
					[Item::GOLD_NUGGET , 0, 1],
				],
			],
			[5, 1,
				[
					[Item::EMERALD , 0, 1],
				],
			],
		];
	}

	public static function getMVPTable(){
		return [100, 1,
			[
				[Item::IRON_INGOT, 0, 1],
				[Item::ENDER_PEARL, 0, 1],
				[Item::SPIDER_EYE, 0, 1],
			]
		];
	}

	//召喚時のポータルのサイズを取得
	public static function getSize(){
		return 2;
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
		return 20;
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
				new StringTag("Data", EnemyRegister::loadSkinData('Kumo')),
				new StringTag("Name", 'JTTW_JTTWSpiderDemon')
			]),
		]);
		$custom_name = self::getEnemyName();
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}
		$entity = new Kumo($level, $nbt);
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
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SITTING, true);
		/*$item = Item::get(267);
		$this->getInventory()->setItemInHand($item);*/
	}

	public function onUpdate(int $tick): bool{
		if($this->getHealth() > 0 && AI::getRate($this)){
			if($this->target = AI::searchTarget($this)){
				AI::rangeAttack($this, 2.25, 4);
				AI::setRate($this, 12);
				switch($this->mode){
					case 0:
						AI::setRate($this, 20);
						AI::lookAt($this, $this->target);
						$this->mode = 1;
						$this->charge = 0;
						$this->walk = false;
						$this->pitch += 90;
					break;
					case 1:
						switch($this->charge){
							case 0:
							case 1:
							case 2:
							case 3:
							case 4:
								$this->level->addParticle(new SpellParticle($this, 170, 161, 153));
								AI::lookAt($this, $this->target);
								++$this->charge;
								AI::setRate($this, 7);
								$this->pitch += 90;
							break;

							case 5:
							case 6:
							case 7:
								AI::lookAt($this, $this->target);
								$pos = AI::chargerShot($this, 20, new TerrainParticle($this, Block::get(Block::COBWEB)), new TerrainParticle($this, Block::get(Block::COBWEB)), 4, 15, 0.8);
								$pos->y += 1;
								if($this->level->getBlock($pos)->getId() === 0){
									$this->level->setBlock($pos, Block::get(Block::COBWEB));
								}
								++$this->charge;
								$this->pitch += 90;
								AI::setRate($this, 3);
							break;
							case 8:
								$this->charge = 0;
								$this->mode = 0;
								$this->walk = true;
								AI::lookAt($this, $this->target);
								$this->pitch += 90;
								AI::setRate($this, 60);
							break;
						}
					break;
				}
			}else{
				$this->charge = 0;
				$this->mode = 0;
				$this->walk = true;
				$this->yaw += mt_rand(-30, 30);
			}

		}
		$can = AI::walkFront($this, 0.15);
		if(!$can){
			AI::jump($this);
		}
		return parent::onUpdate($tick);
	}

/*	public function attackTo(EntityDamageEvent $source){
		$victim = $source->getEntity();
		if($this->level->getBlock($victim)->getId() === 0){
			$this->level->setBlock($victim, Block::get(Block::COBWEB));
		}
	}*/

		public function attack(EntityDamageEvent $source){
		$damage = $source->getDamage();// 20170928 src変更による書き換え
		parent::attack($source);
	}
	
	public function getName() : string{
		return self::getEnemyName();
	}
}